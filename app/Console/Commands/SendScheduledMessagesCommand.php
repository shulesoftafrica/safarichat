<?php

namespace App\Console\Commands;

use App\Models\MessageQueue;
use App\Models\Campaign;
use App\Jobs\ScheduleMessageSendJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Check for scheduled messages that are ready to send
 * 
 * This command runs every minute via cron to:
 * - Find messages with scheduled_send_at <= now()
 * - Dispatch ScheduleMessageSendJob for each message
 * - Update campaign status if needed
 * 
 * Usage:
 * php artisan messages:send-scheduled
 * 
 * Cron:
 * * * * * * php /path/to/artisan messages:send-scheduled >> /dev/null 2>&1
 */
class SendScheduledMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:send-scheduled 
                            {--limit=100 : Maximum messages to process per run}
                            {--campaign= : Process only specific campaign ID}
                            {--dry-run : Show what would be sent without actually dispatching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for scheduled messages and dispatch send jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);

        $this->info('Checking for scheduled messages...');

        try {
            // Build query for ready-to-send messages
            $query = MessageQueue::readyToSend();

            // Filter by campaign if specified
            if ($campaignId = $this->option('campaign')) {
                $query->where('campaign_id', $campaignId);
                $this->info("Filtering for campaign ID: {$campaignId}");
            }

            // Get messages with limit
            $limit = (int) $this->option('limit');
            $messages = $query->with(['campaign', 'contact'])
                              ->orderBy('scheduled_send_at', 'asc')
                              ->orderBy('priority', 'desc')
                              ->limit($limit)
                              ->get();

            if ($messages->isEmpty()) {
                $this->info('No messages ready to send.');
                return Command::SUCCESS;
            }

            $this->info("Found {$messages->count()} message(s) ready to send");

            // Group by campaign for better logging
            $messageByCampaign = $messages->groupBy('campaign_id');

            foreach ($messageByCampaign as $campaignId => $campaignMessages) {
                $campaign = $campaignMessages->first()->campaign;
                $campaignName = $campaign ? $campaign->campaign_name : 'No Campaign';

                $this->line("Campaign: {$campaignName} ({$campaignMessages->count()} messages)");

                // Update campaign status to 'sending' if it's scheduled
                if ($campaign && $campaign->status === Campaign::STATUS_SCHEDULED) {
                    if (!$this->option('dry-run')) {
                        $campaign->update(['status' => Campaign::STATUS_SENDING]);
                        Log::info('Campaign status updated to sending', [
                            'campaign_id' => $campaign->id
                        ]);
                    }
                }
            }

            // Dispatch jobs for each message
            $dispatched = 0;
            $skipped = 0;

            foreach ($messages as $message) {
                // Display message info
                $info = sprintf(
                    "  [%s] %s (%s) - Scheduled for: %s",
                    $message->id,
                    $message->contact_name ?? 'Unknown',
                    $message->phone_number,
                    $message->scheduled_send_at->format('Y-m-d H:i:s')
                );

                if ($this->option('dry-run')) {
                    $this->line($info . ' [DRY-RUN]');
                    $skipped++;
                } else {
                    $this->line($info);

                    try {
                        // Dispatch the job
                        ScheduleMessageSendJob::dispatch($message);
                        $dispatched++;

                        Log::info('Dispatched scheduled message job', [
                            'message_queue_id' => $message->id,
                            'campaign_id' => $message->campaign_id,
                            'contact' => $message->contact_name,
                            'scheduled_for' => $message->scheduled_send_at
                        ]);

                    } catch (\Exception $e) {
                        $this->error("  Failed to dispatch: {$e->getMessage()}");
                        
                        Log::error('Failed to dispatch scheduled message job', [
                            'message_queue_id' => $message->id,
                            'error' => $e->getMessage()
                        ]);

                        // Mark message as failed
                        $message->update([
                            'status' => MessageQueue::STATUS_FAILED,
                            'error_message' => 'Failed to dispatch job: ' . $e->getMessage()
                        ]);
                    }
                }
            }

            // Summary
            $elapsed = round((microtime(true) - $startTime) * 1000, 2);

            if ($this->option('dry-run')) {
                $this->info("\n✓ Dry run completed: {$skipped} message(s) would be dispatched");
            } else {
                $this->info("\n✓ Dispatched {$dispatched} message(s) in {$elapsed}ms");

                if ($dispatched > 0) {
                    $this->comment("Messages are now in queue and will be sent based on priority.");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error processing scheduled messages: {$e->getMessage()}");
            
            Log::error('Scheduler command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
