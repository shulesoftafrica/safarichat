<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\Message;
use DB;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ProcessFailedMessagesCommand::class,
        Commands\ManageAgentsCommand::class,
        Commands\DailyOutreachCommand::class,
        Commands\ConversationEngineCommand::class,
        Commands\WinBackOutreachCommand::class,
        Commands\NoReplyChaseCommand::class,
        Commands\SlaMonitorCommand::class,
    ];
    public $emails;

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        $schedule->command('inspire')
                ->hourly();
        $schedule->call(function () {
            (new Message())->process();
        })->everyMinute();
        
        // AI Sales Agent scheduled tasks
        $this->scheduleAiTasks($schedule);
    }

    /**
     * Schedule AI Sales Agent tasks
     */
    protected function scheduleAiTasks(Schedule $schedule)
    {
        // Process failed messages every 5 minutes
        $schedule->command('ai:process-failed-messages --limit=100')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-failed-messages.log'));

        // Agent health check every hour
        $schedule->command('ai:manage-agents --agent-health-check')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-health-check.log'));

        // Update lead scores daily at 2 AM
        $schedule->command('ai:manage-agents --update-lead-scores')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-lead-scores.log'));

        // Generate product descriptions weekly (Sundays at 3 AM)
        $schedule->command('ai:manage-agents --generate-descriptions')
            ->weeklyOn(0, '03:00') // 0 = Sunday
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-descriptions.log'));

        // Clean up old conversations weekly (Sundays at 4 AM)
        $schedule->command('ai:manage-agents --cleanup-old-conversations')
            ->weeklyOn(0, '04:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-cleanup.log'));

        // Check for overdue handoffs every 30 minutes during business hours
        $schedule->call(function () {
            $handoffService = app(\App\Services\HandoffService::class);
            $overdueHandoffs = $handoffService->getOverdueHandoffs();
            
            if ($overdueHandoffs->isNotEmpty()) {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyOverdueHandoffs($overdueHandoffs->toArray());
            }
        })->everyThirtyMinutes()->between('06:00', '20:00');

        // Auto-assign pending handoffs every 15 minutes during business hours
        $schedule->call(function () {
            $handoffService = app(\App\Services\HandoffService::class);
            $assigned = $handoffService->autoAssignHandoffs();
            
            if ($assigned > 0) {
                \Illuminate\Support\Facades\Log::info("Auto-assigned {$assigned} handoffs");
            }
        })->everyFifteenMinutes()->between('07:00', '19:00');

        // Send daily handoff summaries to agent owners (8 AM)
        $schedule->call(function () {
            $users = \App\Models\User::whereHas('aiSalesAgents')->get();
            $notificationService = app(\App\Services\NotificationService::class);
            
            foreach ($users as $user) {
                $notificationService->sendDailyHandoffSummary($user);
            }
        })->dailyAt('08:00');

        // Monitor system health and send alerts if needed (every 10 minutes)
        $schedule->call(function () {
            $this->monitorSystemHealth();
        })->everyTenMinutes();

        // Process scheduled followups (every minute)
        $schedule->call(function () {
            $this->processScheduledFollowups();
        })->everyMinute();

        // === New AI Sales Agent Commands ===
        
        // Daily outreach campaign - twice daily (9 AM and 2 PM)
        $schedule->command('ai-agent:daily-outreach --limit=50')
            ->twiceDaily(9, 14)
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/daily-outreach.log'));

        // Process conversation queue every 5 minutes
        $schedule->command('ai-agent:process-conversations --limit=100 --timeout=30')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/conversation-engine.log'));

        // Win-back campaigns - weekly on Wednesdays at 10 AM
        $schedule->command('ai-agent:win-back --limit=30 --days-inactive=30')
            ->weeklyOn(3, '10:00') // Wednesday
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/win-back.log'));

        // No-reply chase follow-ups - daily at 11 AM and 4 PM
        $schedule->command('ai-agent:chase-no-reply --limit=50 --hours=48 --max-chases=3')
            ->twiceDaily(11, 16)
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/chase-no-reply.log'));

        // SLA monitoring - every 15 minutes during business hours
        $schedule->command('ai-agent:sla-monitor --alert-threshold=15 --escalation-threshold=60')
            ->everyFifteenMinutes()
            ->between('07:00', '20:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/sla-monitor.log'));
    }

    /**
     * Monitor AI system health and send alerts
     */
    protected function monitorSystemHealth()
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $firstUser = \App\Models\User::first();
            if (!$firstUser) return;
            
            $stats = app(\App\Services\WebhookProcessorService::class)->getProcessingStats($firstUser, 1);

            // Check failure rate
            if ($stats['instant_success_rate'] < (config('ai_sales_agent.monitoring.failure_rate_threshold', 0.1) * 100)) {
                $notificationService->sendSystemAlert('High Failure Rate', [
                    'success_rate' => $stats['instant_success_rate'],
                    'threshold' => config('ai_sales_agent.monitoring.failure_rate_threshold', 0.1) * 100,
                    'total_messages' => $stats['total_messages'],
                    'failed_messages' => $stats['failed_instant'],
                ]);
            }

            // Check queue backlog
            $queueSize = \Illuminate\Support\Facades\Queue::size('ai_standard');
            if ($queueSize > config('ai_queues.monitoring.max_queue_size', 1000)) {
                $notificationService->sendSystemAlert('Queue Backlog', [
                    'queue_size' => $queueSize,
                    'max_size' => config('ai_queues.monitoring.max_queue_size', 1000),
                ]);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('System health monitoring failed: ' . $e->getMessage());
        }
    }

    /**
     * Process scheduled followups
     */
    protected function processScheduledFollowups()
    {
        try {
            $dueFollowups = \App\Models\Conversation::where('followup_scheduled_at', '<=', now())
                ->whereNotNull('followup_scheduled_at')
                ->where('followup_sent', false)
                ->with(['lead', 'product'])
                ->limit(50)
                ->get();

            $aiWhatsAppService = app(\App\Services\AiWhatsAppService::class);

            foreach ($dueFollowups as $conversation) {
                try {
                    $followupMessage = $conversation->followup_message ?: 
                        "Hi! Following up on our conversation. Any questions I can help with?";

                    // Create a mock incoming message for context
                    $mockMessage = new \App\Models\IncomingMessage([
                        'phone_number' => $conversation->lead->phone_number,
                        'message_body' => 'FOLLOWUP_TRIGGER',
                        'user_id' => 1, // Default user - adjust as needed
                        'instance_id' => 'default',
                        'message_id' => 'followup_' . uniqid(),
                        'chat_id' => $conversation->lead->phone_number . '@c.us',
                        'message_timestamp' => now(),
                        'status' => 'received',
                    ]);

                    $sent = $aiWhatsAppService->sendResponse($followupMessage, $mockMessage);

                    if ($sent) {
                        $conversation->update([
                            'followup_sent' => true,
                            'followup_sent_at' => now(),
                        ]);
                    }

                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send followup', [
                        'conversation_id' => $conversation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Followup processing failed: ' . $e->getMessage());
        }
    }



    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

}
