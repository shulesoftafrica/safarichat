<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendWhatsAppMessage;
use App\Jobs\ProcessIncomingMessage;
use Illuminate\Support\Facades\DB;

class TestQueueSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:test-system {--type=all}';

    /**
     * The console command description.
     */
    protected $description = 'Test the queue system functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('🚀 Starting Queue System Test');
        $this->line('');
        
        // Test 1: Basic queue stats
        $this->testQueueStats();
        
        if ($type === 'all' || $type === 'outgoing') {
            // Test 2: Outgoing message
            $this->testOutgoingMessage();
        }
        
        if ($type === 'all' || $type === 'incoming') {
            // Test 3: Incoming message processing
            $this->testIncomingMessage();
        }
        
        $this->line('');
        $this->info('✅ Queue system test completed!');
        $this->line('');
        $this->comment('To start queue worker:');
        $this->line('php artisan queue:work --queue=high_priority,messages,bulk_messages,default');
        $this->line('');
        $this->comment('To monitor queue in real-time:');
        $this->line('php artisan queue:monitor database:default,database:high_priority,database:messages');
    }

    private function testQueueStats()
    {
        $this->comment('📊 Testing Queue Statistics...');
        
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            
            $queueBreakdown = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->get();
            
            $this->line("Pending Jobs: {$pendingJobs}");
            $this->line("Failed Jobs: {$failedJobs}");
            
            if ($queueBreakdown->count() > 0) {
                $this->line("Queue Breakdown:");
                foreach ($queueBreakdown as $queue) {
                    $this->line("  - {$queue->queue}: {$queue->count}");
                }
            }
            
            $this->info('✓ Queue statistics retrieved successfully');
            
        } catch (\Exception $e) {
            $this->error('✗ Error getting queue stats: ' . $e->getMessage());
        }
        
        $this->line('');
    }

    private function testOutgoingMessage()
    {
        $this->comment('📤 Testing Outgoing Message Queue...');
        
        try {
            // Dispatch test message
            SendWhatsAppMessage::dispatch(
                'Test message from queue system test - ' . now(),
                '+255123456789',
                'whatsapp',
                1,
                null,
                'test_instance'
            );
            
            $this->info('✓ Outgoing message dispatched to queue');
            
            // Check if job was created
            $recentJob = DB::table('jobs')
                ->where('created_at', '>=', now()->subMinutes(1)->timestamp)
                ->orderBy('id', 'desc')
                ->first();
            
            if ($recentJob) {
                $this->line("Job ID: {$recentJob->id}");
                $this->line("Queue: {$recentJob->queue}");
                $this->line("Attempts: {$recentJob->attempts}");
            }
            
        } catch (\Exception $e) {
            $this->error('✗ Error testing outgoing message: ' . $e->getMessage());
        }
        
        $this->line('');
    }

    private function testIncomingMessage()
    {
        $this->comment('📥 Testing Incoming Message Processing...');
        
        try {
            // Create test incoming message data
            $messageData = [
                'id' => 'test_' . uniqid(),
                'chatId' => '+255987654321@c.us',
                'fromMe' => false,
                'body' => 'Hello, this is a test incoming message - ' . now(),
                'timestamp' => time(),
                'senderName' => 'Test User',
                'isGroup' => false
            ];
            
            // Create fake WhatsApp instance for testing
            $whatsappInstance = (object) [
                'id' => 1,
                'instance_id' => 'test_instance',
                'user_id' => 1,
                'ai_enabled' => true
            ];
            
            // Dispatch to incoming message processing queue
            ProcessIncomingMessage::dispatch(
                $messageData,
                'test_instance',
                $whatsappInstance
            );
            
            $this->info('✓ Incoming message dispatched to processing queue');
            
            // Check if job was created
            $recentJob = DB::table('jobs')
                ->where('created_at', '>=', now()->subMinutes(1)->timestamp)
                ->orderBy('id', 'desc')
                ->first();
            
            if ($recentJob) {
                $this->line("Job ID: {$recentJob->id}");
                $this->line("Queue: {$recentJob->queue}");
            }
            
        } catch (\Exception $e) {
            $this->error('✗ Error testing incoming message: ' . $e->getMessage());
        }
        
        $this->line('');
    }
}
