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

        $schedule->call(function () {
            $this->reminders();
        })->dailyAt('08:40'); // Eq to 07:40 AM 
        
        // AI Sales Agent scheduled tasks
        $this->scheduleAiTasks($schedule);
    }

  
    public function checkSchedule() {

        $schedules = DB::table('reminders')->get();
        $timestamp = time() + 60*60*3;
        $time = date('H', $timestamp);
        //$current_time = date('H', strtotime(date('H:i')) + (60 * 60 * 3 - 60 * 2)); // plus +3 GMT hours to match with Tanzania time
        //   $current_time = date('H:i');
        $current_time=$time;
        foreach ($schedules as $schedule) {


            if (in_array(date('l'), explode(',', $schedule->days)) && $current_time ==date('H', strtotime($schedule->time))) {
                //execute command;
                $this->sendReminder($schedule);
            }

            if (strlen($schedule->days) < 4) {
                $day = $schedule->date;
                if (date('dmY', strtotime($day)) == date('dmY') && $current_time == date('H', strtotime($schedule->time))) {
                    $this->sendReminder($schedule);
                }
            }
        }
    }

    public function sendReminder($schedule) {

        if (!empty(explode(',', $schedule->user_id)) > 0) {
            $users_list = empty(explode(',', $schedule->user_id)) ? [0] : explode(',', $schedule->user_id);
            //switch criteria to see how the best we can allign as follows
            if ($schedule->criteria == 6 || (int) $schedule->criteria == 0) {
                //This is custom selection, so take users in the array lists
                $users = \App\Models\EventsGuest::whereIn('id', $users_list)->get();
            } else {
                //take event guest lists, and then excluse what is in the array 
                $event_id = \App\Models\User::find($schedule->user_id)->usersEvents()->orderBy('id', 'desc')->first()->event_id;
                $users = $this->getUserByCriteria($schedule->criteria, $event_id, $users_list);
            }
            foreach ($users as $guest) {
                $datediff = time() - strtotime($guest->event->date);
                $paid_amount = isset($guest->custom) ? 0 : $guest->payments()->sum('amount');
                $replacements = array(
                    $guest->guest_name, $guest->guest_pledge, $paid_amount, ((float) $paid_amount - (float) $guest->guest_pledge), round($datediff / (60 * 60 * 24))
                );
                $sms = (new Message())->getCleanSms($replacements, $schedule->message, array(
                    '/#name/i', '/#pledge/i', '/#paid_amount/i', '/#balance/i', '/#days_remain/i'
                ));
                $chat_id = validate_phone_number($guest->guest_phone)[1] . '@c.us';
                $sources = explode(',', $schedule->channels);
                foreach ($sources as $source) {
                    (new Message())->storeMessage($sms, $chat_id, $source);
                }
            }
        }
    }

    public function getUserByCriteria($criteria, $event_id, $exclude_lists = null) {
        switch ($criteria) {
            case 1:
                //All
                $users = \App\Models\EventsGuest::where('event_id', $event_id);
                break;
            case 3:
                //Full Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereIn('id', \App\Models\Payment::get(['events_guests_id']))->whereNotIn('id', $exclude_lists);
                break;
            case 4:
                //Non Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']))->whereNotIn('id', $exclude_lists);
                break;
            case 5:
                //Partially Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']))->whereNotIn('id', $exclude_lists);
                break;
            default:
                break;
        }
        return $users->get();
    }

    /**
     * 
     * @param type $fields
     */
    private function curlServer($fields, $url) {
// Open connection
        $ch = curl_init();
// Set the url, number of POST vars, POST data

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'application/x-www-form-urlencoded'
        ));

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function getCleanSms($replacements, $message, $pattern = null) {
        $sms = preg_replace($pattern != null ? $pattern : array(
            '/#name/i', '/#pledge/i', '/#paid_amount/i', '/#balance/i', '/#days_remain/i'
                ), $replacements, $message);
        if (preg_match('/#/', $sms)) {
            //try to replace that character
            return preg_replace('/\#[a-zA-Z]+/i', '', $sms);
        } else {
            return $sms;
        }
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
