<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;

class SendDailySummaries extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'summaries:send-daily';

    /**
     * The console command description.
     */
    protected $description = 'Send daily summaries to inactive users about missed automations';

    /**
     * Execute the console command.
     */
    public function handle(
        SubscriptionService $subscriptionService,
        SubscriptionNotificationService $notificationService
    ) {
        $this->info('Sending daily summaries to inactive users...');
        
        // Get inactive users with missed automations from yesterday
        $inactiveUsers = User::where('subscription_status', 'inactive')
            ->whereHas('missedAutomations', function($query) {
                $query->whereDate('created_at', yesterday());
            })
            ->get();
        
        $sentCount = 0;
        
        foreach ($inactiveUsers as $user) {
            try {
                $notificationService->sendDailySummary($user);
                $sentCount++;
                
                $this->info("Sent daily summary to user: {$user->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send summary to {$user->email}: {$e->getMessage()}");
            }
        }
        
        $this->info("Daily summaries sent to {$sentCount} users");
        
        return Command::SUCCESS;
    }
}
