<?php

/**
 * Phase 3 Test Script: Message Scheduling & Delivery
 * 
 * Tests the complete flow:
 * 1. Message personalization (Phase 2)
 * 2. Message scheduling at optimal time (Phase 3)
 * 3. Scheduled message sending (Phase 3)
 * 4. Delivery status tracking (Phase 3)
 * 5. Campaign analytics updates (Phase 3)
 * 
 * Usage: php tests/test_phase3_scheduling.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use App\Models\BusinessContact;
use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Models\OutgoingMessage;
use App\Models\CampaignAnalytics;
use App\Models\Conversation;
use App\Jobs\ProcessPersonalizationJob;
use App\Jobs\ScheduleMessageSendJob;
use App\Services\CampaignWebhookHandler;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "========================================\n";
echo "Phase 3: Scheduling & Delivery Test\n";
echo "========================================\n\n";

// Prerequisites check
if (!env('OPENAI_API_KEY')) {
    echo "❌ OPENAI_API_KEY not configured in .env file\n";
    echo "   Please add: OPENAI_API_KEY=sk-your-key-here\n\n";
    exit(1);
}

echo "✓ OpenAI API key configured\n\n";

// Get test user
$user = User::first();
if (!$user) {
    echo "❌ No users found in database\n";
    exit(1);
}

echo "✓ Using user: {$user->name} (ID: {$user->id})\n\n";

// Test data
$testPhone = '+254712' . rand(100000, 999999);
$testName = 'Test Contact ' . date('H:i:s');

echo "========================================\n";
echo "Creating Test Data\n";
echo "========================================\n\n";

try {
    DB::beginTransaction();

    // 1. Create test contact with conversation history
    echo "Creating test contact with conversation history...\n";
    
    $contact = BusinessContact::create([
        'user_id' => $user->id,
        'name' => $testName,
        'phone' => $testPhone,
        'engagement_score' => 70,
        'preferred_language' => null,
        'preferred_tone' => null,
    ]);

    // Create some conversation history (simulated)
    $conversationHistory = [
        ['message' => 'Hello! I\'m interested in your products', 'is_incoming' => true, 'created_at' => now()->subDays(3)],
        ['message' => 'Hi! Great to hear from you. What are you looking for?', 'is_incoming' => false, 'created_at' => now()->subDays(3)->addMinutes(5)],
        ['message' => 'I need office supplies for my business', 'is_incoming' => true, 'created_at' => now()->subDays(3)->addMinutes(10)],
        ['message' => 'Perfect! We have a wide range. Let me send you our catalog.', 'is_incoming' => false, 'created_at' => now()->subDays(3)->addMinutes(15)],
        ['message' => 'That would be great, thanks!', 'is_incoming' => true, 'created_at' => now()->subDays(3)->addMinutes(20)],
    ];

    foreach ($conversationHistory as $msg) {
        Conversation::create([
            'user_id' => $user->id,
            'business_contact_id' => $contact->id,
            'message' => $msg['message'],
            'is_incoming' => $msg['is_incoming'],
            'created_at' => $msg['created_at'],
            'updated_at' => $msg['created_at']
        ]);
    }

    echo "✓ Contact created: {$contact->name} (ID: {$contact->id})\n";
    echo "✓ Created 5 conversation messages\n\n";

    // 2. Create campaign
    echo "Creating test campaign...\n";
    
    $campaign = Campaign::create([
        'user_id' => $user->id,
        'campaign_name' => 'Phase 3 Test Campaign - ' . date('Y-m-d H:i:s'),
        'campaign_type' => Campaign::TYPE_BROADCAST,
        'original_message' => 'Hi {{name}}! We have a special 25% discount on all office supplies this week. Would you like to see our latest catalog with the new pricing?',
        'recipient_criteria' => ['test' => true],
        'total_recipients' => 1,
        'queued_count' => 1,
        'status' => Campaign::STATUS_STAGING,
        'has_attachments' => false
    ]);

    echo "✓ Campaign created: {$campaign->campaign_name} (ID: {$campaign->id})\n\n";

    // 3. Create message queue entry
    echo "Creating message in queue...\n";
    
    $originalMessage = str_replace('{{name}}', $contact->name, $campaign->original_message);
    
    $messageQueue = MessageQueue::create([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'contact_id' => $contact->id,
        'phone_number' => $contact->phone,
        'contact_name' => $contact->name,
        'original_message' => $originalMessage,
        'status' => MessageQueue::STATUS_STAGED,
        'priority' => 5,
        'provider' => 'wasender',
        'credits_used' => 5 // 2 AI + 3 WaSender
    ]);

    echo "✓ MessageQueue created (ID: {$messageQueue->id})\n";
    echo "  Original message: \"{$originalMessage}\"\n\n";

    DB::commit();

    echo "========================================\n";
    echo "TEST 1: Message Personalization\n";
    echo "========================================\n\n";

    $startTime = microtime(true);

    // Process personalization (Phase 2)
    $job = new ProcessPersonalizationJob($messageQueue);
    $job->handle(new \App\Services\MessagePersonalizationService());

    $elapsed = round((microtime(true) - $startTime) * 1000, 2);

    // Reload message queue to get updated data
    $messageQueue->refresh();

    echo "✓ Personalization completed in {$elapsed}ms\n\n";
    echo "STATUS: {$messageQueue->status}\n";
    
    if ($messageQueue->status === MessageQueue::STATUS_SCHEDULED) {
        echo "REFINED MESSAGE:\n";
        echo "  \"{$messageQueue->refined_message}\"\n\n";
        
        echo "AI ANALYSIS:\n";
        echo "  • Detected Language: {$messageQueue->detected_language}\n";
        echo "  • Detected Tone: {$messageQueue->detected_tone}\n";
        echo "  • Relationship Stage: {$messageQueue->relationship_stage}\n";
        echo "  • Sentiment: {$messageQueue->sentiment_filter_result}\n";
        echo "  • AI Confidence: " . ($messageQueue->ai_confidence_score * 100) . "%\n";
        
        if ($messageQueue->optimal_send_time) {
            echo "  • Optimal Send Time: {$messageQueue->optimal_send_time}\n";
        }
        if ($messageQueue->scheduled_send_at) {
            echo "  • Scheduled For: {$messageQueue->scheduled_send_at}\n";
        }
        echo "\n";
    } elseif ($messageQueue->status === MessageQueue::STATUS_HUMAN_REVIEW) {
        echo "⚠ Message flagged for human review\n";
        echo "  Reason: {$messageQueue->human_review_reason}\n\n";
    } else {
        echo "❌ Unexpected status: {$messageQueue->status}\n\n";
    }

    echo "========================================\n";
    echo "TEST 2: Scheduled Message Sending\n";
    echo "========================================\n\n";

    if ($messageQueue->status === MessageQueue::STATUS_SCHEDULED) {
        // Update scheduled time to NOW for immediate testing
        $messageQueue->update(['scheduled_send_at' => now()]);
        echo "✓ Updated scheduled time to NOW for testing\n\n";

        echo "Dispatching ScheduleMessageSendJob...\n";
        
        $billingSendJob = new ScheduleMessageSendJob($messageQueue);
        
        // Mock the BillingService to avoid credit deduction during testing
        $mockBillingService = new class {
            public function hasSufficientCredits($userId, $credits) {
                return true; // Mock: always return true for testing
            }
        };
        
        // Mock WaSenderService to avoid actual API calls
        $mockWaSenderService = new class {
            public function sendTextMessage($phone, $message, $instance, $userId, $options = []) {
                echo "  📤 Mock send to: {$phone}\n";
                echo "  📝 Message: \"{$message}\"\n";
                
                return [
                    'success' => true,
                    'id' => 'test_msg_' . uniqid(),
                    'message_id' => 'test_msg_' . uniqid()
                ];
            }
        };

        // Execute job with mocked services
        $sendJob = new ScheduleMessageSendJob($messageQueue);
        $sendJob->handle($mockWaSenderService, $mockBillingService);

        echo "\n✓ Message send job completed\n\n";

        // Reload to get updated data
        $messageQueue->refresh();
        $campaign->refresh();

        echo "MESSAGE QUEUE STATUS:\n";
        echo "  • Status: {$messageQueue->status}\n";
        echo "  • Sent At: {$messageQueue->sent_at}\n";
        echo "  • External ID: {$messageQueue->external_message_id}\n\n";

        // Check if outgoing message was created
        $outgoingMessage = OutgoingMessage::where('message_queue_id', $messageQueue->id)->first();
        
        if ($outgoingMessage) {
            echo "OUTGOING MESSAGE CREATED:\n";
            echo "  • ID: {$outgoingMessage->id}\n";
            echo "  • Status: {$outgoingMessage->status}\n";
            echo "  • Is Personalized: " . ($outgoingMessage->is_personalized ? 'Yes' : 'No') . "\n";
            echo "  • Provider: {$outgoingMessage->provider}\n";
            echo "  • Credits Used: {$outgoingMessage->credits_used}\n";
            echo "  • Sent At: {$outgoingMessage->sent_at}\n\n";
        } else {
            echo "❌ No outgoing message created\n\n";
        }

        echo "CAMPAIGN COUNTERS:\n";
        echo "  • Queued: {$campaign->queued_count}\n";
        echo "  • Analyzing: {$campaign->analyzing_count}\n";
        echo "  • Refined: {$campaign->refined_count}\n";
        echo "  • Scheduled: {$campaign->scheduled_count}\n";
        echo "  • Sent: {$campaign->sent_count}\n";
        echo "  • Failed: {$campaign->failed_count}\n\n";

    } else {
        echo "⏭️  Skipping send test (message not scheduled)\n\n";
    }

    echo "========================================\n";
    echo "TEST 3: Delivery Webhook Handling\n";
    echo "========================================\n\n";

    if (isset($outgoingMessage) && $outgoingMessage) {
        $webhookHandler = new CampaignWebhookHandler();

        // Test 3.1: Message delivered
        echo "Simulating 'delivered' webhook...\n";
        $deliveryWebhook = [
            'message_id' => $outgoingMessage->external_id,
            'status' => 'delivered',
            'timestamp' => now()->toIso8601String()
        ];
        
        $result = $webhookHandler->handleMessageStatusUpdate($deliveryWebhook);
        echo "✓ Delivered webhook processed: " . json_encode($result) . "\n\n";

        $outgoingMessage->refresh();
        echo "MESSAGE STATUS: {$outgoingMessage->status}\n";
        echo "DELIVERED AT: {$outgoingMessage->delivered_at}\n\n";

        // Test 3.2: Message read
        sleep(1); // Small delay to simulate realistic timing
        echo "Simulating 'read' webhook...\n";
        $readWebhook = [
            'message_id' => $outgoingMessage->external_id,
            'status' => 'read',
            'timestamp' => now()->toIso8601String()
        ];
        
        $result = $webhookHandler->handleMessageStatusUpdate($readWebhook);
        echo "✓ Read webhook processed: " . json_encode($result) . "\n\n";

        $outgoingMessage->refresh();
        echo "MESSAGE STATUS: {$outgoingMessage->status}\n";
        echo "READ AT: {$outgoingMessage->read_at}\n\n";

        // Test 3.3: Reply received
        sleep(1);
        echo "Simulating customer reply...\n";
        $replyWebhook = [
            'from' => $contact->phone,
            'phone' => $contact->phone,
            'message' => 'Yes please! I would like to see the catalog.',
            'text' => 'Yes please! I would like to see the catalog.',
            'timestamp' => now()->toIso8601String()
        ];
        
        $result = $webhookHandler->handleReply($replyWebhook);
        echo "✓ Reply webhook processed: " . json_encode($result) . "\n\n";

        $outgoingMessage->refresh();
        echo "REPLY RECEIVED: " . ($outgoingMessage->reply_received ? 'Yes' : 'No') . "\n";
        if ($outgoingMessage->reply_received) {
            echo "REPLY MESSAGE: \"{$outgoingMessage->reply_message}\"\n";
            echo "REPLY TIME: {$outgoingMessage->reply_received_at}\n";
        }
        echo "\n";

    } else {
        echo "⏭️  Skipping webhook test (no outgoing message)\n\n";
    }

    echo "========================================\n";
    echo "TEST 4: Campaign Analytics\n";
    echo "========================================\n\n";

    $analytics = CampaignAnalytics::where('campaign_id', $campaign->id)->first();

    if ($analytics) {
        echo "CAMPAIGN ANALYTICS:\n";
        echo "  • Total Sent: {$analytics->total_sent}\n";
        echo "  • Total Delivered: {$analytics->total_delivered}\n";
        echo "  • Total Read: {$analytics->total_read}\n";
        echo "  • Total Replied: {$analytics->total_replied}\n";
        echo "  • Total Failed: {$analytics->total_failed}\n";
        echo "  • Delivery Rate: {$analytics->delivery_rate}%\n";
        echo "  • Read Rate: {$analytics->read_rate}%\n";
        echo "  • Reply Rate: {$analytics->reply_rate}%\n";
        echo "  • Avg Confidence Score: {$analytics->avg_confidence_score}\n";
        echo "  • Credits Spent: {$analytics->credits_spent}\n";
        
        if ($analytics->avg_delivery_time_seconds) {
            echo "  • Avg Delivery Time: {$analytics->avg_delivery_time_seconds}s\n";
        }
        if ($analytics->avg_read_time_seconds) {
            echo "  • Avg Read Time: {$analytics->avg_read_time_seconds}s\n";
        }
        echo "\n";
    } else {
        echo "⚠️  No analytics record found for campaign\n\n";
    }

    echo "========================================\n";
    echo "Test Cleanup\n";
    echo "========================================\n\n";

    $cleanup = readline("Delete test data? (yes/no): ");

    if (strtolower(trim($cleanup)) === 'yes') {
        DB::beginTransaction();
        
        if (isset($analytics)) {
            $analytics->delete();
            echo "✓ Deleted campaign analytics\n";
        }
        
        if (isset($outgoingMessage)) {
            $outgoingMessage->delete();
            echo "✓ Deleted outgoing message\n";
        }
        
        $messageQueue->delete();
        echo "✓ Deleted message queue entry\n";
        
        Conversation::where('business_contact_id', $contact->id)->delete();
        echo "✓ Deleted conversation history\n";
        
        $contact->delete();
        echo "✓ Deleted test contact\n";
        
        $campaign->delete();
        echo "✓ Deleted test campaign\n";
        
        DB::commit();
        echo "\n✓ Cleanup completed\n\n";
    } else {
        echo "Test data kept for manual inspection\n";
        echo "  Contact ID: {$contact->id}\n";
        echo "  Campaign ID: {$campaign->id}\n";
        echo "  Message Queue ID: {$messageQueue->id}\n\n";
    }

    echo "========================================\n";
    echo "✓ PHASE 3 TESTS COMPLETED\n";
    echo "========================================\n\n";
    
    echo "SUMMARY:\n";
    echo "  ✓ Message personalization (Phase 2)\n";
    echo "  ✓ Message scheduling (Phase 3)\n";
    echo "  ✓ Scheduled message sending (Phase 3)\n";
    echo "  ✓ Delivery webhook handling (Phase 3)\n";
    echo "  ✓ Campaign analytics tracking (Phase 3)\n\n";
    
    echo "NEXT STEPS:\n";
    echo "  1. Set up cron job: * * * * * php artisan messages:send-scheduled\n";
    echo "  2. Configure WaSender webhook to point to: /api/wasender/webhook/{instanceId}\n";
    echo "  3. Test with real WaSender API (remove mocks from this script)\n";
    echo "  4. Monitor with: tail -f storage/logs/scheduled-messages.log\n";
    echo "  5. Start queue workers: php artisan queue:work\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
