<?php

namespace App\Console\Commands;

use App\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;

class ProcessNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:process';

    /**
     * The console command description.
     */
    protected $description = 'Process pending notifications in the queue';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionNotificationService $notificationService)
    {
        $this->info('Processing notification queue...');
        
        $sentCount = $notificationService->processNotificationQueue();
        
        $this->info("Processed {$sentCount} notifications successfully");
        
        return Command::SUCCESS;
    }
}
