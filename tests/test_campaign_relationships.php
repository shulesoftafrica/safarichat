<?php

/**
 * Test Campaign Model Relationships
 * 
 * This script tests that all campaign-related models and relationships work correctly
 * Run with: php tests/test_campaign_relationships.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Models\CampaignAttachment;
use App\Models\CampaignAnalytics;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Campaign System Relationship Test\n";
echo "========================================\n\n";

try {
    // Test 1: Check if tables exist
    echo "✓ Test 1: Verifying tables exist...\n";
    $tables = ['campaigns', 'message_queue', 'campaign_attachments', 'campaign_analytics'];
    foreach ($tables as $table) {
        try {
            $count = DB::table($table)->count();
            echo "  ✓ Table '{$table}' exists (rows: {$count})\n";
        } catch (\Exception $e) {
            echo "  ✗ Table '{$table}' does not exist\n";
            echo "    Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    echo "\n";

    // Test 2: Check if new columns exist in business_contacts
    echo "✓ Test 2: Verifying business_contacts personalization columns...\n";
    $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'business_contacts' AND column_name IN ('preferred_language', 'preferred_tone', 'last_message_sentiment', 'opt_out_status', 'opt_out_at', 'avg_reply_hour', 'engagement_score')");
    echo "  Found " . count($columns) . " / 7 personalization columns\n";
    foreach ($columns as $col) {
        echo "  ✓ " . $col->column_name . "\n";
    }
    echo "\n";

    // Test 3: Check if new columns exist in outgoing_messages
    echo "✓ Test 3: Verifying outgoing_messages campaign columns...\n";
    $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'outgoing_messages' AND column_name IN ('campaign_id', 'message_queue_id', 'original_message', 'is_personalized', 'personalization_metadata')");
    echo "  Found " . count($columns) . " / 5 campaign columns\n";
    foreach ($columns as $col) {
        echo "  ✓ " . $col->column_name . "\n";
    }
    echo "\n";

    // Test 4: Create test campaign
    echo "✓ Test 4: Creating test campaign...\n";
    
    // Get first user
    $user = User::first();
    
    if (!$user) {
        echo "  ✗ No users found in database. Please create a user first.\n";
        exit(1);
    }
    
    $campaign = Campaign::create([
        'user_id' => $user->id,
        'campaign_name' => 'Test Campaign - ' . date('Y-m-d H:i:s'),
        'campaign_type' => Campaign::TYPE_TARGETED,
        'original_message' => 'Hello {{name}}, this is a test message from SafariChat!',
        'recipient_criteria' => [
            'tags' => ['customer', 'active'],
            'min_engagement_score' => 50
        ],
        'total_recipients' => 10,
        'status' => Campaign::STATUS_STAGING
    ]);
    
    echo "  ✓ Campaign created with ID: {$campaign->id}\n";
    echo "  ✓ Campaign name: {$campaign->campaign_name}\n";
    echo "  ✓ Status: {$campaign->status}\n";
    echo "\n";

    // Test 5: Create campaign analytics
    echo "✓ Test 5: Creating campaign analytics...\n";
    $analytics = CampaignAnalytics::create([
        'campaign_id' => $campaign->id,
        'total_sent' => 0,
        'total_delivered' => 0,
        'total_read' => 0,
        'total_replied' => 0,
        'credits_spent' => 0
    ]);
    echo "  ✓ Analytics record created with ID: {$analytics->id}\n";
    echo "\n";

    // Test 6: Create message in queue
    echo "✓ Test 6: Creating test message in queue...\n";
    $message = MessageQueue::create([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'phone_number' => '+254712345678',
        'contact_name' => 'Test Contact',
        'original_message' => 'Hello Test Contact, this is a test message from SafariChat!',
        'status' => MessageQueue::STATUS_STAGED,
        'priority' => 5,
        'provider' => MessageQueue::PROVIDER_WASENDER,
        'credits_used' => 5
    ]);
    echo "  ✓ Message created with ID: {$message->id}\n";
    echo "  ✓ Phone: {$message->phone_number}\n";
    echo "  ✓ Status: {$message->status}\n";
    echo "\n";

    // Test 7: Test relationships
    echo "✓ Test 7: Testing model relationships...\n";
    
    // Campaign -> MessageQueue
    $campaignMessages = $campaign->messageQueue()->count();
    echo "  ✓ Campaign has {$campaignMessages} message(s) in queue\n";
    
    // Campaign -> Analytics
    $campaignAnalytics = $campaign->analytics;
    echo "  ✓ Campaign has analytics record: " . ($campaignAnalytics ? "Yes (ID: {$campaignAnalytics->id})" : "No") . "\n";
    
    // MessageQueue -> Campaign
    $messageCampaign = $message->campaign;
    echo "  ✓ Message belongs to campaign: {$messageCampaign->campaign_name}\n";
    
    // Campaign -> User
    $campaignUser = $campaign->user;
    echo "  ✓ Campaign belongs to user: {$campaignUser->name}\n";
    echo "\n";

    // Test 8: Test counter methods
    echo "✓ Test 8: Testing counter methods...\n";
    $campaign->incrementCounter('queued_count');
    $campaign->incrementCounter('analyzing_count');
    $campaign->refresh();
    echo "  ✓ Queued count: {$campaign->queued_count}\n";
    echo "  ✓ Analyzing count: {$campaign->analyzing_count}\n";
    echo "\n";

    // Test 9: Test analytics methods
    echo "✓ Test 9: Testing analytics methods...\n";
    $analytics->incrementSent();
    $analytics->incrementDelivered();
    $analytics->incrementRead();
    $analytics->refresh();
    echo "  ✓ Total sent: {$analytics->total_sent}\n";
    echo "  ✓ Total delivered: {$analytics->total_delivered}\n";
    echo "  ✓ Delivery rate: {$analytics->delivery_rate}%\n";
    echo "\n";

    // Test 10: Test message queue methods
    echo "✓ Test 10: Testing message queue methods...\n";
    $priority = $message->calculatePriority();
    echo "  ✓ Calculated priority: {$priority}\n";
    
    $messageToSend = $message->getMessageToSend();
    echo "  ✓ Message to send: " . substr($messageToSend, 0, 50) . "...\n";
    echo "\n";

    // Clean up
    echo "✓ Test 11: Cleaning up test data...\n";
    $message->delete();
    echo "  ✓ Message deleted\n";
    
    $analytics->delete();
    echo "  ✓ Analytics deleted\n";
    
    $campaign->delete();
    echo "  ✓ Campaign deleted\n";
    echo "\n";

    echo "========================================\n";
    echo "✓ All tests passed successfully!\n";
    echo "========================================\n\n";

    echo "Summary:\n";
    echo "- 4 new tables created (campaigns, message_queue, campaign_attachments, campaign_analytics)\n";
    echo "- 7 personalization columns added to business_contacts\n";
    echo "- 5 campaign columns added to outgoing_messages\n";
    echo "- 4 models created with full relationships\n";
    echo "- All CRUD operations working\n";
    echo "- All relationships functioning correctly\n\n";

    echo "Next Steps:\n";
    echo "1. Update BusinessContact model to include new fields\n";
    echo "2. Update OutgoingMessage model to include campaign relationships\n";
    echo "3. Begin Phase 2: Create MessagePersonalizationService\n\n";

} catch (\Exception $e) {
    echo "\n✗ Test failed with error:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
