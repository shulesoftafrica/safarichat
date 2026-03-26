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

        // ── Step 1b: Pre-fetch Unified Notification API sessions ──────────────────────────────
        $notificationBaseUrl  = config('services.unified_notification.base_url', 'https://notifications.shulesoft.africa/api');
        $notificationToken    = config('notifications.unified_api.bearer_token');
        $notificationSessions = collect();  // indexed by schema_name

        if ($notificationToken) {
            $this->info('Pre-fetching sessions from Unified Notification API...');
            try {
                $notifResp = Http::timeout(20)->withHeaders([
                    'Authorization' => 'Bearer ' . $notificationToken,
                    'Accept'        => 'application/json',
                ])->get($notificationBaseUrl . '/wasender/sessions');

                if ($notifResp->successful()) {
                    $notificationSessions = collect($notifResp->json('data') ?? [])
                        ->keyBy('schema_name');
                    $this->info("  Unified Notification API returned {$notificationSessions->count()} session(s).");
                } else {
                    $this->warn("  Unified Notification API responded {$notifResp->status()} — health checks will be skipped.");
                }
            } catch (Exception $e) {
                $this->warn('  Unified Notification API unreachable: ' . $e->getMessage() . ' — health checks will be skipped.');
            }
        } else {
            $this->warn('  UNIFIED_NOTIFICATION_BEARER_TOKEN not configured — notification registration checks will be skipped.');
        }

        // ── Step 2: Load local instances ────────────────────────────────────────────────────
        $query = WhatsappInstance::with('user')
            ->whereNotNull('instance_id')
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
            $this->warn('Dry run complete — no database changes were made. Remove --dry-run to apply.');
        }

        // ── Step 4: Health checks for all currently-connected instances ──────────────────────
        // Runs only for sessions WaSender reports as 'ready'. For each, we verify:
        //   a) The webhook is correctly configured in WaSender (right URL, enabled, all events)
        //   b) The session is registered in the Unified Notification API (remote GET check)
        // If either is missing/wrong, we fix it — unless --dry-run is set.
        $connectedInstances = $instances->filter(function ($inst) use ($wasenderSessions) {
            $raw = $wasenderSessions->get($inst->instance_id)['status'] ?? '';
            return $this->mapToConnectStatus($raw) === 'ready';
        });

        if ($connectedInstances->isNotEmpty()) {
            $this->newLine();
            $this->info("=== Health Checks for {$connectedInstances->count()} Connected Instance(s) ===");

            $healthHeaders  = ['Instance ID', 'Phone', 'WaSender Webhook', 'Notification API', 'Actions Taken'];
            $healthRows     = [];
            $failureDetails = [];   // collected and printed as warnings below the table
            $healthStats    = [
                'webhook_ok'     => 0, 'webhook_fixed'    => 0, 'webhook_failed' => 0,
                'notif_ok'       => 0, 'notif_registered' => 0, 'notif_failed'   => 0,
            ];

            foreach ($connectedInstances as $instance) {
                $wasenderData = $wasenderSessions->get($instance->instance_id);
                $actions      = [];

                // ── a) WaSender webhook check ─────────────────────────────────────────────
                $expectedWebhookUrl = url('/api/wasender/webhook/' . $instance->instance_id);
                $requiredEvents     = ['messages.received', 'session.status', 'messages.update'];
                $actualUrl          = $wasenderData['webhook_url']     ?? '';
                $actualEnabled      = $wasenderData['webhook_enabled'] ?? false;
                $actualEvents       = (array) ($wasenderData['webhook_events'] ?? []);
                $missingEvents      = array_diff($requiredEvents, $actualEvents);

                $webhookHealthy = ($actualUrl === $expectedWebhookUrl)
                               && $actualEnabled
                               && empty($missingEvents);

                // Build a human-readable description of what is wrong (used in dry-run and error output)
                $webhookIssues = [];
                if ($actualUrl !== $expectedWebhookUrl) {
                    $webhookIssues[] = 'wrong URL (has: ' . ($actualUrl ?: 'none') . ', expected: ' . $expectedWebhookUrl . ')';
                }
                if (!$actualEnabled) {
                    $webhookIssues[] = 'webhook_enabled=false';
                }
                if (!empty($missingEvents)) {
                    $webhookIssues[] = 'missing events: ' . implode(', ', $missingEvents);
                }

                if ($webhookHealthy) {
                    $webhookStatus = '✓ OK';
                    $healthStats['webhook_ok']++;
                } elseif ($dryRun) {
                    $webhookStatus = '✗ ' . implode('; ', $webhookIssues) . ' (dry)';
                    $actions[]     = 'Would fix webhook';
                    $healthStats['webhook_fixed']++;
                } else {
                    $fixResult = $this->fixWasenderWebhook((string) $instance->instance_id, $apiKey);
                    if ($fixResult['success']) {
                        $webhookStatus = '↑ Fixed';
                        $actions[]     = 'Webhook re-configured';
                        $healthStats['webhook_fixed']++;
                    } else {
                        $webhookStatus = '✗ Fix failed';
                        $actions[]     = 'Webhook fix FAILED';
                        $healthStats['webhook_failed']++;
                        $failureDetails[] = [
                            'instance_id' => $instance->instance_id,
                            'phone'       => $instance->phone_number,
                            'type'        => 'WaSender webhook PATCH',
                            'issues'      => $webhookIssues,
                            'http_status' => $fixResult['http_status'] ?? null,
                            'http_body'   => $fixResult['http_body']   ?? null,
                            'error'       => $fixResult['error']       ?? null,
                        ];
                    }
                }

                // ── b) Unified Notification API registration check ────────────────────────
                $user        = $instance->user;
                $schemaName  = $user ? ($user->uuid ?? 'user_' . $user->id) : null;
                $notifStatus = 'skipped (no token)';

                if ($notificationToken && $schemaName) {
                    $notifRecord = $notificationSessions->get($schemaName);

                    if ($notifRecord) {
                        $notifStatus = '✓ Registered';
                        $healthStats['notif_ok']++;
                    } elseif ($dryRun) {
                        $notifStatus = '✗ Not registered (dry)';
                        $actions[]   = 'Would register with Notification API';
                        $healthStats['notif_registered']++;
                    } else {
                        // Not found remotely — re-register now
                        $regResult = $this->registerWithNotificationApi(
                            $instance, $schemaName, $notificationBaseUrl, $notificationToken
                        );

                        if ($regResult['success']) {
                            $notifStatus = '↑ Registered';
                            $actions[]   = 'Registered with Notification API';
                            $healthStats['notif_registered']++;

                            // Persist timestamp so the idempotent guard triggers on next run
                            $existing = is_array($instance->metadata)
                                ? $instance->metadata
                                : (json_decode($instance->metadata ?? '{}', true) ?? []);

                            $instance->update([
                                'unified_api_registered_at' => now(),
                                'metadata' => array_merge($existing, [
                                    'unified_api_registration' => [
                                        'registered_at' => now()->toISOString(),
                                        'schema_name'   => $schemaName,
                                        'response'      => $regResult['data'] ?? [],
                                    ],
                                ]),
                            ]);
                        } else {
                            $notifStatus = '✗ Reg. failed';
                            $actions[]   = 'Notification API reg. FAILED';
                            $healthStats['notif_failed']++;
                            $failureDetails[] = [
                                'instance_id' => $instance->instance_id,
                                'phone'       => $instance->phone_number,
                                'type'        => 'Notification API registration POST',
                                'issues'      => ['Session not found in Notification API (schema: ' . $schemaName . ')'],
                                'http_status' => $regResult['status'] ?? null,
                                'http_body'   => $regResult['body']   ?? null,
                                'error'       => $regResult['error']  ?? null,
                            ];
                        }
                    }
                } elseif (!$schemaName) {
                    $notifStatus = 'skipped (no user)';
                }

                $healthRows[] = [
                    $instance->instance_id,
                    $instance->phone_number,
                    $webhookStatus,
                    $notifStatus,
                    empty($actions) ? '—' : implode('; ', $actions),
                ];
            }

            $this->table($healthHeaders, $healthRows);

            // ── Print failure details so the operator knows exactly what went wrong ────────
            if (!empty($failureDetails)) {
                $this->newLine();
                $this->warn('=== Failure Details ===');
                foreach ($failureDetails as $fail) {
                    $this->warn("  [{$fail['instance_id']}] {$fail['phone']} — {$fail['type']}");
                    foreach ($fail['issues'] as $issue) {
                        $this->line("    • Issue   : {$issue}");
                    }
                    if ($fail['http_status']) {
                        $this->line("    • HTTP    : {$fail['http_status']}");
                    }
                    if ($fail['http_body']) {
                        // Trim to first 300 chars so large HTML error pages don't flood the terminal
                        $bodySnippet = mb_substr(trim($fail['http_body']), 0, 300);
                        $this->line("    • Response: {$bodySnippet}");
                    }
                    if ($fail['error']) {
                        $this->line("    • Exception: {$fail['error']}");
                    }
                    $this->newLine();
                }
            }

            $this->info('=== Health Check Summary ===');
            $this->line("  Webhook   — OK: {$healthStats['webhook_ok']}  Fixed: {$healthStats['webhook_fixed']}  Failed: {$healthStats['webhook_failed']}");
            $this->line("  Notif API — OK: {$healthStats['notif_ok']}  Registered: {$healthStats['notif_registered']}  Failed: {$healthStats['notif_failed']}");
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
        return "~ {$from} → {$to}";
    }

    /**
     * PATCH WaSender to set the correct webhook URL, enable it, and subscribe all three events.
     * Mirrors the logic of WaSenderController::updateSessionWebhook().
     */
    /**
     * Returns ['success' => bool, 'http_status' => int|null, 'http_body' => string|null, 'error' => string|null]
     */
    private function fixWasenderWebhook(string $sessionId, string $apiKey): array
    {
        try {
            $webhookUrl = url('/api/wasender/webhook/' . $sessionId);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->patch("{$this->wasenderBaseUrl}/whatsapp-sessions/{$sessionId}", [
                'webhook_url'     => $webhookUrl,
                'webhook_enabled' => true,
                'webhook_events'  => ['messages.received', 'session.status', 'messages.update'],
            ]);

            if ($response->successful()) {
                Log::info('whatsapp:check-instances — webhook fixed on WaSender', [
                    'session_id'  => $sessionId,
                    'webhook_url' => $webhookUrl,
                ]);
                return ['success' => true];
            }

            Log::warning('whatsapp:check-instances — webhook PATCH failed', [
                'session_id' => $sessionId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            return [
                'success'     => false,
                'http_status' => $response->status(),
                'http_body'   => $response->body(),
            ];

        } catch (Exception $e) {
            Log::error('whatsapp:check-instances — exception fixing webhook', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * POST to the Unified Notification API to register (or re-register) a WaSender session.
     * Uses the same endpoint and payload shape as WaSenderController::registerWithUnifiedNotificationApi().
     * Remote check (GET /wasender/sessions) already confirmed the record is absent before this is called.
     */
    private function registerWithNotificationApi(
        WhatsappInstance $instance,
        string $schemaName,
        string $baseUrl,
        string $token
    ): array {
        try {
            $payload = [
                'schema_name'         => $schemaName,
                'wasender_session_id' => (string) $instance->instance_id,
                'api_key'             => $instance->api_key,
                'phone_number'        => $instance->phone_number,
                'instance_name'       => $instance->instance_name,
                'webhook_url'         => url('/api/wasender/webhook/' . $instance->instance_id),
                'webhook_enabled'     => true,
                'webhook_events'      => ['messages.received', 'session.status', 'messages.update'],
                'status'              => 'connected',
                'connected_at'        => now()->toISOString(),
                'account_protection'  => true,
                'log_messages'        => true,
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post($baseUrl . '/wasender/sessions/register', $payload);

            if ($response->successful()) {
                Log::info('whatsapp:check-instances — registered with Unified Notification API', [
                    'instance_id'  => $instance->instance_id,
                    'schema_name'  => $schemaName,
                    'phone_number' => $instance->phone_number,
                ]);
                return ['success' => true, 'data' => $response->json()];
            }

            Log::warning('whatsapp:check-instances — Notification API registration failed', [
                'instance_id' => $instance->instance_id,
                'schema_name' => $schemaName,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);
            return ['success' => false, 'status' => $response->status(), 'body' => $response->body()];

        } catch (Exception $e) {
            Log::error('whatsapp:check-instances — exception during Notification API registration', [
                'instance_id' => $instance->instance_id,
                'error'       => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

