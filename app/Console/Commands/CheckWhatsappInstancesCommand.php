<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class CheckWhatsappInstancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:check-instances
                            {--user= : Only check instances for a specific user ID}
                            {--dry-run : Show what would change without updating the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync WhatsApp instance connection status with WaSender API';

    private string $wasenderBaseUrl = 'https://www.wasenderapi.com/api';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $userId  = $this->option('user');
        $apiKey  = config('services.wasender.access_token');

        if (!$apiKey) {
            $this->error('WASENDER_ACCESS_TOKEN is not configured in .env â€” cannot check real status.');
            return 1;
        }

        if ($dryRun) {
            $this->warn('[DRY RUN] No database changes will be made.');
        }

        $this->info('Fetching all sessions from WaSender API...');

        // â”€â”€ Step 1: Pull the full session list from WaSender once â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept'        => 'application/json',
            ])->get("{$this->wasenderBaseUrl}/whatsapp-sessions");

            if (!$response->successful()) {
                $this->error("WaSender API error {$response->status()}: " . $response->body());
                return 1;
            }

            $wasenderSessions = collect($response->json('data') ?? [])
                ->keyBy('id');   // index by session ID for O(1) lookups

            $this->info("  WaSender returned {$wasenderSessions->count()} session(s).");

        } catch (Exception $e) {
            $this->error('Failed to reach WaSender API: ' . $e->getMessage());
            return 1;
        }

        // â”€â”€ Step 2: Load local instances â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $query = WhatsappInstance::whereNotNull('instance_id')
            ->where('is_system_default', false);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $instances = $query->get()->filter(fn($i) => is_numeric($i->instance_id));

        if ($instances->isEmpty()) {
            $this->info('No local WhatsApp instances found to check.');
            return 0;
        }

        $this->info("Comparing {$instances->count()} local instance(s) against WaSender...\n");

        // â”€â”€ Step 3: Compare and sync â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $stats = ['synced' => 0, 'already_ok' => 0, 'not_in_wasender' => 0, 'errors' => 0];

        $headers = ['Instance ID', 'Phone', 'WaSender Status', 'Local Before', 'Local After', 'Action'];
        $rows    = [];

        foreach ($instances as $instance) {
            $wasenderData   = $wasenderSessions->get($instance->instance_id);
            $localConnectStatus = $instance->connect_status ?? 'unknown';
            $localStatus        = $instance->status         ?? 'unknown';

            // â”€â”€ Instance not found in WaSender at all â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            if (!$wasenderData) {
                $stats['not_in_wasender']++;
                $rows[] = [
                    $instance->instance_id,
                    $instance->phone_number,
                    'NOT FOUND',
                    $localConnectStatus,
                    $dryRun ? 'disconnected (dry)' : 'disconnected',
                    'âš  Not in WaSender',
                ];

                if (!$dryRun) {
                    $instance->update([
                        'connect_status'  => 'disconnected',
                        'status'          => 'disconnected',
                        'disconnected_at' => now(),
                    ]);
                    Cache::forget('whatsapp_disconnected_' . $instance->user_id);
                }
                continue;
            }

            // â”€â”€ Map live WaSender status â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $apiRawStatus      = $wasenderData['status'] ?? 'unknown';
            $newConnectStatus  = $this->mapToConnectStatus($apiRawStatus);
            $newStatus         = $this->mapToStatus($apiRawStatus);

            // â”€â”€ Already in sync â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            if ($localConnectStatus === $newConnectStatus) {
                $stats['already_ok']++;
                $rows[] = [
                    $instance->instance_id,
                    $instance->phone_number,
                    $apiRawStatus,
                    $localConnectStatus,
                    $newConnectStatus,
                    'âœ“ In sync',
                ];
                continue;
            }

            // â”€â”€ Out of sync â€” update local to match WaSender â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $direction = $this->syncDirection($localConnectStatus, $newConnectStatus);
            $rows[]    = [
                $instance->instance_id,
                $instance->phone_number,
                $apiRawStatus,
                $localConnectStatus,
                $dryRun ? "$newConnectStatus (dry)" : $newConnectStatus,
                $direction,
            ];
            $stats['synced']++;

            if (!$dryRun) {
                $update = [
                    'connect_status' => $newConnectStatus,
                    'status'         => $newStatus,
                    'last_seen'      => now(),
                ];

                if ($newConnectStatus === 'disconnected' || $newConnectStatus === 'error') {
                    $update['disconnected_at'] = now();
                }

                $instance->update($update);

                // Always clear cache so the warning banner re-evaluates immediately
                Cache::forget('whatsapp_disconnected_' . $instance->user_id);

                Log::info('WhatsApp instance status synced from WaSender', [
                    'instance_id'     => $instance->instance_id,
                    'user_id'         => $instance->user_id,
                    'wasender_status' => $apiRawStatus,
                    'old_local'       => $localConnectStatus,
                    'new_local'       => $newConnectStatus,
                ]);
            }
        }

        // â”€â”€ Output table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $this->table($headers, $rows);

        // â”€â”€ Summary â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $this->newLine();
        $this->info("=== Sync Summary ===");
        $this->line("  Already in sync : {$stats['already_ok']}");
        $this->line("  Updated (synced): {$stats['synced']}");
        $this->line("  Not in WaSender : {$stats['not_in_wasender']}");

        if ($stats['errors'] > 0) {
            $this->error("  Errors          : {$stats['errors']}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run complete â€” no database changes were made. Remove --dry-run to apply.');
        }

        return 0;
    }

    /**
     * Map WaSender API status â†’ connect_status enum
     * DB enum: disconnected | connecting | ready | error
     */
    private function mapToConnectStatus(string $apiStatus): string
    {
        return match (strtolower($apiStatus)) {
            'connected', 'ready', 'open' => 'ready',
            'connecting', 'initializing', 'starting' => 'connecting',
            'disconnected', 'closed', 'logged_out', 'offline' => 'disconnected',
            'failed', 'error', 'timeout' => 'error',
            default => 'disconnected',
        };
    }

    /**
     * Map WaSender API status â†’ status enum
     * DB enum: connecting | connected | disconnected | error
     */
    private function mapToStatus(string $apiStatus): string
    {
        return match (strtolower($apiStatus)) {
            'connected', 'ready', 'open' => 'connected',
            'connecting', 'initializing', 'starting' => 'connecting',
            'disconnected', 'closed', 'logged_out', 'offline' => 'disconnected',
            'failed', 'error', 'timeout' => 'error',
            default => 'disconnected',
        };
    }

    /**
     * Human-readable description of the sync change direction.
     */
    private function syncDirection(string $from, string $to): string
    {
        $fromConnected = in_array($from, ['ready', 'connecting']);
        $toConnected   = in_array($to,   ['ready', 'connecting']);

        if (!$fromConnected && $toConnected) {
            return 'â†‘ Local updated â†’ CONNECTED';
        }
        if ($fromConnected && !$toConnected) {
            return 'â†“ Local updated â†’ DISCONNECTED';
        }
        return "~ {$from} â†’ {$to}";
    }
}

