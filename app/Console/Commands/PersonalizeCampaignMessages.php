<?php

namespace App\Console\Commands;

use App\Jobs\PersonalizeCampaignMessagesJob;
use App\Models\MessageQueue;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Console command to process campaign message personalization
 * 
 * This command finds all staged messages in the message queue and
 * dispatches jobs to personalize them using AI analysis.
 * 
 * Usage:
 *   php artisan campaigns:personalize              # Process all campaigns
 *   php artisan campaigns:personalize --campaign=5 # Process specific campaign
 *   php artisan campaigns:personalize --limit=100  # Process max 100 messages
 *   php artisan campaigns:personalize --force      # Force reprocess all
 */
class PersonalizeCampaignMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:personalize
                            {--campaign= : Specific campaign ID to process}
                            {--limit=200 : Maximum number of messages to process}
                            {--batch=50 : Batch size for each job}
                            {--force : Force reprocess all messages (including already processed)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Personalize campaign messages using AI analysis of conversation history';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Starting campaign message personalization...');
        
        $campaignId = $this->option('campaign');
        $limit = (int) $this->option('limit');
        $batchSize = (int) $this->option('batch');
        $force = $this->option('force');

        // Build query
        $query = MessageQueue::where('status', MessageQueue::STATUS_STAGED);
        
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
            $this->info("📋 Processing campaign ID: {$campaignId}");
        } else {
            $this->info("📋 Processing all campaigns");
        }

        // Get count of messages
        $totalMessages = $query->count();

        if ($totalMessages === 0) {
            $this->warn('⚠️  No staged messages found for personalization');
            
            // Show stats of other statuses
            $this->showQueueStats($campaignId);
            
            return self::SUCCESS;
        }

        $this->info("✅ Found {$totalMessages} staged message(s)");
        
        // Apply limit
        $messagesToProcess = min($totalMessages, $limit);
        
        if ($messagesToProcess < $totalMessages) {
            $this->warn("⚠️  Limiting to {$messagesToProcess} messages (use --limit to adjust)");
        }

        // Calculate number of batches needed
        $batchCount = ceil($messagesToProcess / $batchSize);
        
        $this->info("📦 Will dispatch {$batchCount} job(s) with batch size of {$batchSize}");
        
        // Ask for confirmation only in interactive terminal sessions, never in cron
        if ($messagesToProcess > 100 && $this->input->isInteractive()) {
            if (!$this->confirm("Process {$messagesToProcess} messages?")) {
                $this->warn('❌ Cancelled by user');
                return self::SUCCESS;
            }
        }

        // Dispatch personalization job
        try {
            $this->info('🎯 Dispatching personalization job...');
            
            PersonalizeCampaignMessagesJob::dispatch($campaignId, $batchSize);
            
            $this->newLine();
            $this->info('✅ Successfully dispatched personalization job');
            $this->info('💡 Messages will be processed asynchronously via queue workers');
            $this->newLine();
            
            // Show what happens next
            $this->line('📊 <fg=cyan>Processing Pipeline:</>');
            $this->line('   1️⃣  Messages marked as "analyzing"');
            $this->line('   2️⃣  AI analyzes conversation history');
            $this->line('   3️⃣  Messages personalized with language/tone detection');
            $this->line('   4️⃣  Optimal send time calculated');
            $this->line('   5️⃣  Messages scheduled for delivery');
            $this->newLine();
            
            // Show monitoring commands
            $this->line('🔍 <fg=cyan>Monitor Progress:</>');
            $this->line('   • Watch queue: <fg=yellow>php artisan queue:work ai_personalization</>');
            $this->line('   • Check logs: <fg=yellow>tail -f storage/logs/laravel.log</>');
            $this->line('   • View stats: <fg=yellow>php artisan campaigns:stats</>');
            $this->newLine();
            
            Log::info('Personalization job dispatched via command', [
                'campaign_id' => $campaignId,
                'total_messages' => $totalMessages,
                'batch_size' => $batchSize,
                'dispatched_by' => 'console_command'
            ]);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch personalization job');
            $this->error($e->getMessage());
            
            Log::error('Failed to dispatch personalization job from command', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return self::FAILURE;
        }
    }

    /**
     * Show queue statistics
     *
     * @param int|null $campaignId
     * @return void
     */
    protected function showQueueStats($campaignId = null)
    {
        $this->newLine();
        $this->line('📊 <fg=cyan>Current Queue Status:</>');
        
        $statuses = [
            MessageQueue::STATUS_STAGED => '⏳ Staged (Waiting)',
            MessageQueue::STATUS_ANALYZING => '🔍 Analyzing',
            MessageQueue::STATUS_REFINED => '✨ Refined',
            MessageQueue::STATUS_SCHEDULED => '📅 Scheduled',
            MessageQueue::STATUS_SENT => '✅ Sent',
            MessageQueue::STATUS_FAILED => '❌ Failed',
            MessageQueue::STATUS_HUMAN_REVIEW => '👤 Human Review',
            MessageQueue::STATUS_OPTED_OUT => '🚫 Opted Out'
        ];

        $query = MessageQueue::query();
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        foreach ($statuses as $status => $label) {
            $count = (clone $query)->where('status', $status)->count();
            if ($count > 0) {
                $this->line("   {$label}: <fg=yellow>{$count}</>");
            }
        }

        $this->newLine();
    }
}
