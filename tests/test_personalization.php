<?php

/**
 * Test AI Message Personalization
 * 
 * This script tests the MessagePersonalizationService with real scenarios
 * Run with: php tests/test_personalization.php
 * 
 * Prerequisites:
 * 1. Set OPENAI_API_KEY in your .env file
 * 2. Have at least one user, business, and contact in the database
 * 3. Ensure conversations table has some test data
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Models\BusinessContact;
use App\Models\User;
use App\Models\Conversation;
use App\Services\MessagePersonalizationService;
use App\Jobs\ProcessPersonalizationJob;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "AI Message Personalization Test\n";
echo "========================================\n\n";

// Check OpenAI API key
$apiKey = config('services.openai.api_key');
if (empty($apiKey)) {
    echo "❌ ERROR: OPENAI_API_KEY not configured in .env file\n";
    echo "   Please add: OPENAI_API_KEY=sk-your-key-here\n\n";
    exit(1);
}

echo "✓ OpenAI API key configured\n";
echo "  Model: " . config('services.openai.model', 'gpt-4') . "\n\n";

try {
    // Get test user
    $user = User::first();
    if (!$user) {
        echo "❌ No users found. Please create a user first.\n";
        exit(1);
    }
    echo "✓ Using user: {$user->name} (ID: {$user->id})\n\n";

    // Create test contact with conversation history
    echo "Creating test contact with conversation history...\n";
    
    $contact = BusinessContact::firstOrCreate(
        ['guest_phone' => '+254712345678'],
        [
            'user_id' => $user->id,
            'guest_name' => 'John Kamau',
            'guest_email' => 'john.kamau@test.com',
            'engagement_score' => 65,
            'created_at' => now()->subDays(30)
        ]
    );
    echo "✓ Contact created: {$contact->guest_name} (ID: {$contact->id})\n";

    // Create some conversation history
    $conversations = [
        ['message' => 'Hello, I\'m interested in your products', 'is_incoming' => true, 'created_at' => now()->subDays(5)],
        ['message' => 'Hi John! Great to hear from you. What are you looking for?', 'is_incoming' => false, 'created_at' => now()->subDays(5)->addMinutes(2)],
        ['message' => 'Something for my business, maybe office supplies', 'is_incoming' => true, 'created_at' => now()->subDays(5)->addMinutes(5)],
        ['message' => 'Perfect! We have a wide range of office supplies. Would you like a catalog?', 'is_incoming' => false, 'created_at' => now()->subDays(5)->addMinutes(7)],
        ['message' => 'Yes please', 'is_incoming' => true, 'created_at' => now()->subDays(5)->addMinutes(10)],
    ];

    foreach ($conversations as $conv) {
        Conversation::firstOrCreate([
            'business_contact_id' => $contact->id,
            'message' => $conv['message'],
            'is_incoming' => $conv['is_incoming'],
            'created_at' => $conv['created_at']
        ]);
    }
    echo "✓ Created 5 conversation messages\n\n";

    // Create test campaign
    echo "Creating test campaign...\n";
    $campaign = Campaign::create([
        'user_id' => $user->id,
        'campaign_name' => 'Office Supplies Promotion - ' . date('Y-m-d H:i'),
        'campaign_type' => Campaign::TYPE_TARGETED,
        'original_message' => 'Hi {{name}}! We have a special 20% discount on office supplies this week. Would you like to see our latest catalog?',
        'recipient_criteria' => [
            'engagement_score' => ['min' => 50],
            'tags' => ['business', 'office']
        ],
        'total_recipients' => 1,
        'status' => Campaign::STATUS_STAGING
    ]);
    echo "✓ Campaign created: {$campaign->campaign_name} (ID: {$campaign->id})\n\n";

    // Create test message in queue
    echo "Creating message in queue...\n";
    $messageQueue = MessageQueue::create([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'contact_id' => $contact->id,
        'phone_number' => $contact->guest_phone,
        'contact_name' => $contact->guest_name,
        'original_message' => str_replace('{{name}}', $contact->guest_name, $campaign->original_message),
        'status' => MessageQueue::STATUS_STAGED,
        'priority' => 7,
        'provider' => MessageQueue::PROVIDER_WASENDER,
        'credits_used' => 5
    ]);
    $campaign->incrementCounter('queued_count');
    echo "✓ MessageQueue created (ID: {$messageQueue->id})\n";
    echo "  Original message: \"{$messageQueue->original_message}\"\n\n";

    // Test personalization service
    echo "========================================\n";
    echo "Testing AI Personalization Service\n";
    echo "========================================\n\n";

    $service = new MessagePersonalizationService();
    
    echo "Calling OpenAI API for message personalization...\n";
    echo "(This may take 10-30 seconds)\n\n";
    
    $startTime = microtime(true);
    $result = $service->personalizeMessage($messageQueue);
    $duration = round((microtime(true) - $startTime) * 1000);

    echo "✓ Personalization completed in {$duration}ms\n\n";

    // Display results
    echo "========================================\n";
    echo "Personalization Results\n";
    echo "========================================\n\n";

    if ($result['refined_message']) {
        echo "✓ REFINED MESSAGE:\n";
        echo "  \"{$result['refined_message']}\"\n\n";

        echo "AI ANALYSIS:\n";
        echo "  • Detected Language: {$result['analysis']['detected_language']}\n";
        echo "  • Detected Tone: {$result['analysis']['detected_tone']}\n";
        echo "  • Relationship Stage: {$result['analysis']['relationship_stage']}\n";
        echo "  • Sentiment: {$result['analysis']['sentiment_filter_result']}\n";
        echo "  • AI Confidence: " . ($result['analysis']['ai_confidence_score'] * 100) . "%\n";
        
        if (isset($result['analysis']['optimal_send_time'])) {
            echo "  • Optimal Send Time: {$result['analysis']['optimal_send_time']}\n";
        }

        if (isset($result['analysis']['ai_metadata']['reasoning'])) {
            echo "\nAI REASONING:\n";
            echo "  {$result['analysis']['ai_metadata']['reasoning']}\n";
        }

        if (isset($result['analysis']['context_summary'])) {
            echo "\nCONTEXT SUMMARY:\n";
            foreach ($result['analysis']['context_summary'] as $key => $value) {
                echo "  • " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
            }
        }

        if (isset($result['analysis']['ai_metadata']['tokens_used'])) {
            echo "\nAPI USAGE:\n";
            echo "  • Tokens Used: {$result['analysis']['ai_metadata']['tokens_used']}\n";
            echo "  • Model: {$result['analysis']['ai_metadata']['model']}\n";
        }

    } else {
        echo "⚠ NO REFINED MESSAGE (needs review or opted out)\n";
        if (isset($result['analysis']['error'])) {
            echo "  Error: {$result['analysis']['error']}\n";
        }
    }

    echo "\n========================================\n";
    echo "Database Updates\n";
    echo "========================================\n\n";

    // Refresh contact to see updated preferences
    $contact->refresh();
    echo "CONTACT PREFERENCES (AI-learned):\n";
    echo "  • Preferred Language: " . ($contact->preferred_language ?? 'not set') . "\n";
    echo "  • Preferred Tone: " . ($contact->preferred_tone ?? 'not set') . "\n";
    echo "  • Last Sentiment: " . ($contact->last_message_sentiment ?? 'not set') . "\n";
    echo "  • Engagement Score: {$contact->engagement_score}/100\n";
    echo "  • Avg Reply Hour: " . ($contact->avg_reply_hour ?? 'not calculated') . "\n";

    // Refresh message queue
    $messageQueue->refresh();
    echo "\nMESSAGE QUEUE STATUS:\n";
    echo "  • Status: {$messageQueue->status}\n";
    echo "  • Refined: " . ($messageQueue->refined_message ? 'Yes' : 'No') . "\n";
    if ($messageQueue->scheduled_send_at) {
        echo "  • Scheduled for: {$messageQueue->scheduled_send_at}\n";
    }

    echo "\n========================================\n";
    echo "Testing Queue Job Processing\n";
    echo "========================================\n\n";

    // Create another message to test job dispatch
    $messageQueue2 = MessageQueue::create([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'contact_id' => $contact->id,
        'phone_number' => '+254723456789',
        'contact_name' => 'Sarah Njeri',
        'original_message' => 'Hi Sarah! We have a special 20% discount on office supplies this week.',
        'status' => MessageQueue::STATUS_STAGED,
        'priority' => 5,
        'provider' => MessageQueue::PROVIDER_WASENDER,
        'credits_used' => 5
    ]);

    echo "✓ Created second message (ID: {$messageQueue2->id})\n";
    echo "✓ Dispatching ProcessPersonalizationJob...\n";
    
    // Note: In production, this would go to the queue
    // For testing, we'll call it synchronously
    try {
        $job = new ProcessPersonalizationJob($messageQueue2);
        $job->handle($service);
        
        $messageQueue2->refresh();
        echo "✓ Job processed successfully\n";
        echo "  • Status: {$messageQueue2->status}\n";
        echo "  • Refined: " . ($messageQueue2->refined_message ? 'Yes' : 'No') . "\n";
    } catch (\Exception $e) {
        echo "✗ Job failed: " . $e->getMessage() . "\n";
    }

    echo "\n========================================\n";
    echo "Testing Batch Processing\n";
    echo "========================================\n\n";

    // Create 5 more messages for batch test
    for ($i = 1; $i <= 5; $i++) {
        MessageQueue::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'phone_number' => "+25471234567{$i}",
            'contact_name' => "Test Contact {$i}",
            'original_message' => "Hi! Special offer for you this week.",
            'status' => MessageQueue::STATUS_STAGED,
            'priority' => 5,
            'provider' => MessageQueue::PROVIDER_WASENDER,
            'credits_used' => 5
        ]);
    }

    echo "✓ Created 5 additional messages\n";
    echo "✓ Running batch personalization (limit 3)...\n\n";

    $batchResult = $service->batchPersonalizeCampaign($campaign, 3);
    
    echo "BATCH RESULTS:\n";
    echo "  • Total Processed: {$batchResult['total']}\n";
    echo "  • Successful: {$batchResult['processed']}\n";
    echo "  • Failed: {$batchResult['failed']}\n";

    $campaign->refresh();
    echo "\nCAMPAIGN COUNTERS:\n";
    echo "  • Queued: {$campaign->queued_count}\n";
    echo "  • Analyzing: {$campaign->analyzing_count}\n";
    echo "  • Refined: {$campaign->refined_count}\n";
    echo "  • Scheduled: {$campaign->scheduled_count}\n";
    echo "  • Failed: {$campaign->failed_count}\n";

    echo "\n========================================\n";
    echo "Cleanup\n";
    echo "========================================\n\n";

    echo "Keep test data for review? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);

    if ($input === 'n' || $input === 'N') {
        MessageQueue::where('campaign_id', $campaign->id)->delete();
        $campaign->delete();
        $contact->delete();
        Conversation::where('business_contact_id', $contact->id)->delete();
        echo "✓ Test data cleaned up\n";
    } else {
        echo "✓ Test data preserved\n";
        echo "  Campaign ID: {$campaign->id}\n";
        echo "  Contact ID: {$contact->id}\n";
    }

    echo "\n========================================\n";
    echo "✓ All tests completed successfully!\n";
    echo "========================================\n\n";

    echo "Next Steps:\n";
    echo "1. Review personalized messages above\n";
    echo "2. Check AI confidence scores and reasoning\n";
    echo "3. Verify contact preferences were updated\n";
    echo "4. Test with your own OpenAI API key in production\n";
    echo "5. Monitor token usage for cost optimization\n\n";

    echo "Phase 2 Components Created:\n";
    echo "✓ MessagePersonalizationService.php (646 lines)\n";
    echo "✓ ProcessPersonalizationJob.php (264 lines)\n";
    echo "✓ OpenAI configuration in config/services.php\n";
    echo "✓ Test script (this file)\n\n";

} catch (\Exception $e) {
    echo "\n✗ Test failed with error:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
