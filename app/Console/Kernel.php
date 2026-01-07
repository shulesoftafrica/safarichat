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
        Commands\CronMonitorCommand::class,
        Commands\SendDailySummaries::class,
        Commands\SyncCreditsCommand::class,
        Commands\ProcessNotifications::class,
        Commands\SmartFollowupCommand::class,
        Commands\UpdateContactPrioritiesCommand::class,
    ];
    public $emails;

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        // Log cron scheduler start
        $this->logCronActivity($schedule, 'Cron scheduler started');
        $schedule->call(function () {
            $this->logCronActivity(null, 'Current datetime: ' . now()->toDateTimeString());
        })->everyMinute()->name('log-current-datetime');

        $schedule->command('inspire')
                ->hourly()
                ->onSuccess(function () {
                    $this->logCronActivity(null, 'Inspire command completed successfully');
                })
                ->onFailure(function () {
                    $this->logCronActivity(null, 'Inspire command failed', 'error');
                });
                
        $schedule->call(function () {
            try {
                $this->logCronActivity(null, 'Message processing started');
                (new Message())->process();
                $this->logCronActivity(null, 'Message processing completed successfully');
            } catch (\Exception $e) {
                $this->logCronActivity(null, 'Message processing failed: ' . $e->getMessage(), 'error');
                throw $e;
            }
        })->everyMinute()
          ->name('message-processing')
          ->withoutOverlapping();
        
        // AI Sales Agent scheduled tasks
        $this->scheduleAiTasks($schedule);
        
        // Add cron health monitoring - every 30 minutes
        $schedule->command('cron:monitor --action=health')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cron-health.log'));

        // Clear old logs weekly (Sundays at 5 AM)  
        $schedule->command('cron:monitor --action=logs --clear-logs')
            ->weeklyOn(0, '05:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cron-cleanup.log'));
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
            ->appendOutputTo(storage_path('logs/ai-failed-messages.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'AI failed messages processing completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'AI failed messages processing failed', 'error');
            });

        // Agent health check every hour
        $schedule->command('ai:manage-agents --agent-health-check')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-health-check.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'AI health check completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'AI health check failed', 'error');
            });

        // Update lead scores daily at 2 AM
        $schedule->command('ai:manage-agents --update-lead-scores')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-lead-scores.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Lead scores update completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Lead scores update failed', 'error');
            });

        // Update contact priorities daily at 2:30 AM (after lead scores)
        $schedule->command('contacts:update-priorities')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/contact-priorities.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Contact priorities update completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Contact priorities update failed', 'error');
            });

        // Generate product descriptions weekly (Sundays at 3 AM)
        $schedule->command('ai:manage-agents --generate-descriptions')
            ->weeklyOn(0, '03:00') // 0 = Sunday
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-descriptions.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Product descriptions generation completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Product descriptions generation failed', 'error');
            });

        // Clean up old conversations weekly (Sundays at 4 AM)
        $schedule->command('ai:manage-agents --cleanup-old-conversations')
            ->weeklyOn(0, '04:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai-cleanup.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Conversations cleanup completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Conversations cleanup failed', 'error');
            });

        // Check for overdue handoffs every 30 minutes during business hours
        $schedule->call(function () {
            try {
                $this->logCronActivity(null, 'Overdue handoffs check started');
                $handoffService = app(\App\Services\HandoffService::class);
                $overdueHandoffs = $handoffService->getOverdueHandoffs();
                
                if ($overdueHandoffs->isNotEmpty()) {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->notifyOverdueHandoffs($overdueHandoffs->toArray());
                    $this->logCronActivity(null, 'Overdue handoffs notifications sent: ' . $overdueHandoffs->count());
                } else {
                    $this->logCronActivity(null, 'No overdue handoffs found');
                }
            } catch (\Exception $e) {
                $this->logCronActivity(null, 'Overdue handoffs check failed: ' . $e->getMessage(), 'error');
            }
        })->everyThirtyMinutes()->between('06:00', '20:00')
          ->name('overdue-handoffs-check');

        // Auto-assign pending handoffs every 15 minutes during business hours
        $schedule->call(function () {
            try {
                $this->logCronActivity(null, 'Auto-assign handoffs started');
                $handoffService = app(\App\Services\HandoffService::class);
                $assigned = $handoffService->autoAssignHandoffs();
                
                if ($assigned > 0) {
                    $this->logCronActivity(null, "Auto-assigned {$assigned} handoffs");
                } else {
                    $this->logCronActivity(null, 'No handoffs to auto-assign');
                }
            } catch (\Exception $e) {
                $this->logCronActivity(null, 'Auto-assign handoffs failed: ' . $e->getMessage(), 'error');
            }
        })->everyFifteenMinutes()->between('07:00', '19:00')
          ->name('auto-assign-handoffs');

        // Send daily handoff summaries to agent owners (8 AM)
        $schedule->call(function () {
            try {
                $this->logCronActivity(null, 'Daily handoff summaries started');
                $users = \App\Models\User::whereHas('aiSalesAgents')->get();
                $notificationService = app(\App\Services\NotificationService::class);
                
                foreach ($users as $user) {
                    $notificationService->sendDailyHandoffSummary($user);
                }
                $this->logCronActivity(null, 'Daily handoff summaries sent to ' . $users->count() . ' users');
            } catch (\Exception $e) {
                $this->logCronActivity(null, 'Daily handoff summaries failed: ' . $e->getMessage(), 'error');
            }
        })->dailyAt('08:00')
          ->name('daily-handoff-summaries');

        // Monitor system health and send alerts if needed (every 10 minutes)
        $schedule->call(function () {
            try {
                $this->logCronActivity(null, 'System health monitoring started');
                $this->monitorSystemHealth();
                $this->logCronActivity(null, 'System health monitoring completed');
            } catch (\Exception $e) {
                $this->logCronActivity(null, 'System health monitoring failed: ' . $e->getMessage(), 'error');
            }
        })->everyTenMinutes()
          ->name('system-health-monitor');

        // Process scheduled followups (every minute)
        $schedule->call(function () {
            try {
                $this->logCronActivity(null, 'Scheduled followups processing started');
                $this->processScheduledFollowups();
                $this->logCronActivity(null, 'Scheduled followups processing completed');
            } catch (\Exception $e) {
                $this->logCronActivity(null, 'Scheduled followups processing failed: ' . $e->getMessage(), 'error');
            }
        })->everyMinute()
          ->name('scheduled-followups');

        // === New AI Sales Agent Commands ===
        
        // Daily outreach campaign - twice daily (9 AM and 2 PM)
        $schedule->command('ai-agent:daily-outreach --limit=50')
            ->twiceDaily(9, 14)
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/daily-outreach.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Daily outreach campaign completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Daily outreach campaign failed', 'error');
            });

        // Process conversation queue every 5 minutes
        $schedule->command('ai-agent:process-conversations --limit=100 --timeout=30')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/conversation-engine.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Conversation processing completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Conversation processing failed', 'error');
            });

        // Win-back campaigns - weekly on Wednesdays at 10 AM
        $schedule->command('ai-agent:win-back --limit=30 --days-inactive=30')
            ->weeklyOn(3, '10:00') // Wednesday
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/win-back.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Win-back campaign completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Win-back campaign failed', 'error');
            });

        // No-reply chase follow-ups - daily at 11 AM and 4 PM
        $schedule->command('ai-agent:chase-no-reply --limit=50 --hours=48 --max-chases=3')
            ->twiceDaily(11, 16)
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/chase-no-reply.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'No-reply chase completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'No-reply chase failed', 'error');
            });

        // SLA monitoring - every 15 minutes during business hours
        $schedule->command('ai-agent:sla-monitor --alert-threshold=15 --escalation-threshold=60')
            ->everyFifteenMinutes()
            ->between('07:00', '20:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/sla-monitor.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'SLA monitoring completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'SLA monitoring failed', 'error');
            });

        // === Business Operations Commands ===
        
        // Send daily summaries to inactive users - daily at 7 AM
        $schedule->command('summaries:send-daily')
            ->dailyAt('07:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/daily-summaries.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Daily summaries sent successfully');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Daily summaries failed', 'error');
            });

        // Sync credits with billing system - every hour
        $schedule->command('billing:sync-credits')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/credit-sync.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Credit synchronization completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Credit synchronization failed', 'error');
            });

        // Process notification queue - every 10 minutes
        $schedule->command('notifications:process')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/notifications.log'))
            ->onSuccess(function () {
                $this->logCronActivity(null, 'Notification processing completed');
            })
            ->onFailure(function () {
                $this->logCronActivity(null, 'Notification processing failed', 'error');
            });
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
     * Process scheduled followups using smart AI service
     */
    protected function processScheduledFollowups()
    {
        try {
            $this->logCronActivity(null, "Starting smart followup processing");
            
            $smartFollowupService = app(\App\Services\SmartFollowupService::class);
            $smartFollowupService->processSmartFollowups();
            
            $this->logCronActivity(null, "Smart followup processing completed successfully");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Smart followup processing failed: ' . $e->getMessage());
            $this->logCronActivity(null, "Smart followup processing failed: {$e->getMessage()}");
        }
    }

    /**
     * Log cron activity to dedicated log file
     */
    protected function logCronActivity($schedule, $message, $level = 'info', $context = [])
    {
        $logFile = storage_path('logs/cron-monitor.log');
        $timestamp = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        
        $logEntry = "[$timestamp] $level: $message$contextStr" . PHP_EOL;
        
        \Illuminate\Support\Facades\File::append($logFile, $logEntry);
        
        // Also log to Laravel's default logger with cron prefix
        \Illuminate\Support\Facades\Log::$level("[CRON] $message", $context);
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
