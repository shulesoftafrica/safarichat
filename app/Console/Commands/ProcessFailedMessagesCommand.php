<?php

namespace App\Console\Commands;

use App\Models\IncomingMessage;
use App\Models\Lead;
use App\Services\AiWhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessFailedMessagesCommand extends Command
{
    protected $signature = 'ai:process-failed-messages {--limit=50 : Maximum number of messages to process} {--max-age=24 : Maximum age of messages to process in hours} {--dry-run : Show what would be processed without actually processing}';

    protected $description = 'Process failed instant WhatsApp messages through AI system';

    private $aiWhatsAppService;

    public function __construct(AiWhatsAppService $aiWhatsAppService)
    {
        parent::__construct();
        $this->aiWhatsAppService = $aiWhatsAppService;
    }

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $maxAge = (int) $this->option('max-age');
        $dryRun = $this->option('dry-run');

        $this->info("Processing failed messages - Limit: {$limit}, Max Age: {$maxAge}h, Dry Run: " . ($dryRun ? 'Yes' : 'No'));

        // Get failed messages that need processing
        $failedMessages = $this->getFailedMessages($limit, $maxAge);

        if ($failedMessages->isEmpty()) {
            $this->info('No failed messages found to process.');
            return 0;
        }

        $this->info("Found {$failedMessages->count()} failed messages to process.");

        $processed = 0;
        $errors = 0;
        $successes = 0;

        foreach ($failedMessages as $message) {
            try {
                $this->line("Processing message ID: {$message->id} from {$message->phone_number}");

                if ($dryRun) {
                    $this->info("  [DRY RUN] Would process: " . substr($message->message_body, 0, 50) . '...');
                    $processed++;
                    continue;
                }

                // Increment processing attempts
                $message->increment('processing_attempts');

                // Process with AI
                $result = $this->aiWhatsAppService->processIncomingWhatsAppMessageWithAI($message);

                if ($result['success']) {
                    // Send response if available
                    if (isset($result['response'])) {
                        $sent = $this->aiWhatsAppService->sendResponse($result['response'], $message);
                        
                        if ($sent) {
                            $message->update([
                                'status' => 'replied',
                                'processing_method' => 'cron_fallback',
                            ]);
                            
                            $this->info("  ✓ Processed and replied successfully");
                            $successes++;
                        } else {
                            $message->update([
                                'status' => 'processed',
                                'processing_method' => 'cron_fallback',
                                'failure_reason' => 'Failed to send response',
                            ]);
                            
                            $this->warn("  ⚠ Processed but failed to send response");
                        }
                    } else {
                        $message->update([
                            'status' => 'processed',
                            'processing_method' => 'cron_fallback',
                        ]);
                        
                        $this->info("  ✓ Processed (no response needed)");
                        $successes++;
                    }
                } else {
                    $message->update([
                        'failure_reason' => $result['error'] ?? 'AI processing failed',
                    ]);

                    $this->error("  ✗ Processing failed: " . ($result['error'] ?? 'Unknown error'));
                    $errors++;
                }

                $processed++;

            } catch (\Exception $e) {
                $message->update([
                    'failure_reason' => $e->getMessage(),
                ]);

                $this->error("  ✗ Exception: " . $e->getMessage());
                $errors++;
                
                Log::error('Failed message processing exception', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Add a small delay to prevent overwhelming the system
            usleep(100000); // 0.1 seconds
        }

        $this->newLine();
        $this->info("Processing complete:");
        $this->info("  Total processed: {$processed}");
        $this->info("  Successes: {$successes}");
        $this->info("  Errors: {$errors}");

        // Report on still-failing messages
        if (!$dryRun) {
            $stillFailing = $this->getFailedMessages(100, $maxAge)->count();
            if ($stillFailing > 0) {
                $this->warn("  Still failing: {$stillFailing} messages");
            }
        }

        return 0;
    }

    /**
     * Get failed messages that need processing
     */
    private function getFailedMessages(int $limit, int $maxAge)
    {
        $cutoffTime = now()->subHours($maxAge);

        return IncomingMessage::where('status', 'received')
            ->where(function ($query) use ($cutoffTime) {
                $query->whereNotNull('failed_instant_at')
                      ->orWhere('processing_method', 'cron_fallback')
                      ->orWhere(function ($q) use ($cutoffTime) {
                          // Messages older than 5 minutes that haven't been processed
                          $q->where('created_at', '<', now()->subMinutes(5))
                            ->where('created_at', '>=', $cutoffTime)
                            ->whereNull('failed_instant_at')
                            ->where('status', 'received');
                      });
            })
            ->where('processing_attempts', '<', 5) // Don't retry more than 5 times
            ->where('created_at', '>=', $cutoffTime) // Don't process very old messages
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get processing statistics
     */
    private function getProcessingStats(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        $stats = IncomingMessage::where('created_at', '>=', $since)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN processing_method = 'webhook' THEN 1 ELSE 0 END) as instant,
                SUM(CASE WHEN processing_method = 'cron_fallback' THEN 1 ELSE 0 END) as cron_processed,
                AVG(processing_attempts) as avg_attempts
            ')
            ->first();

        return [
            'total_messages' => $stats->total ?? 0,
            'replied' => $stats->replied ?? 0,
            'failed' => $stats->failed ?? 0,
            'instant_processed' => $stats->instant ?? 0,
            'cron_processed' => $stats->cron_processed ?? 0,
            'success_rate' => $stats->total > 0 ? round(($stats->replied / $stats->total) * 100, 2) : 0,
            'avg_attempts' => round($stats->avg_attempts ?? 0, 2),
        ];
    }
}