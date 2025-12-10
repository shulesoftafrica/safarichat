<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use App\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;

class CheckSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:check';

    /**
     * The console command description.
     */
    protected $description = 'Check subscription statuses and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(
        SubscriptionService $subscriptionService,
        SubscriptionNotificationService $notificationService
    ) {
        $this->info('Checking subscription statuses...');

        // Check for expiring subscriptions and send warnings
        $subscriptionService->checkExpiryAndNotify();
        $this->info('Expiry warnings processed');

        // Process expired subscriptions
        $subscriptionService->processExpiredSubscriptions();
        $this->info('Expired subscriptions processed');

        $this->info('Subscription check completed successfully');
        return Command::SUCCESS;
    }
}
