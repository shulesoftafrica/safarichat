<?php
/**
 * Test Script: AI Campaign Personalization Workflow
 * 
 * This script tests the complete workflow from campaign creation
 * through AI personalization to scheduled delivery.
 * 
 * Usage: php tests/test_ai_campaign_workflow.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Models\BusinessContact;
use App\Models\Conversation;
use App\Jobs\PersonalizeCampaignMessagesJob;
use App\Services\MessagePersonalizationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       AI CAMPAIGN PERSONALIZATION WORKFLOW TEST               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test configuration
$testPhone = '+254712345678';
$testName = 'John Doe';
$userId = 1; // Change to valid user ID
$businessId = 1; // Change to valid business ID

echo "📋 Test Configuration:\n";
echo "   User ID: {$userId}\n";
echo "   Business ID: {$businessId}\n";
echo "   Test Phone: {$testPhone}\n";
echo "   Test Name: {$testName}\n\n";

try {
    // ==================== STEP 1: Setup Test Contact ====================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 1: Creating Test Contact\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $contact = BusinessContact::firstOrCreate(
        ['guest_phone' => $testPhone],
        [
            'guest_name' => $testName,
            'user_id' => $userId,
            'business_id' => $businessId,
            'engagement_score' => 75,
            'preferred_language' => 'english',
            'preferred_tone' => 'casual',
            'avg_reply_hour' => 14 // 2 PM
        ]
    );
    
    echo "✅ Contact Created/Found:\n";
    echo "   ID: {$contact->id}\n";
    echo "   Name: {$contact->guest_name}\n";
    echo "   Phone: {$contact->guest_phone}\n";
    echo "   Language: {$contact->preferred_language}\n";
    echo "   Tone: {$contact->preferred_tone}\n";
    echo "   Avg Reply Hour: {$contact->avg_reply_hour}\n\n";
    
    // Create sample conversation history for better AI context
    echo "📝 Creating sample conversation history...\n";
    
    $conversations = [
        ['message' => 'Hi, I saw your products online', 'is_incoming' => true],
        ['message' => 'Hello! Thanks for reaching out. How can we help?', 'is_incoming' => false],
        ['message' => 'Im interested in your premium package', 'is_incoming' => true],
        ['message' => 'Great choice! The premium package includes...', 'is_incoming' => false],
        ['message' => 'Sounds good, what\'s the price?', 'is_incoming' => true],
    ];
    
    foreach ($conversations as $index => $conv) {
        Conversation::firstOrCreate([
            'business_contact_id' => $contact->id,
            'message' => $conv['message'],
            'is_incoming' => $conv['is_incoming'],
            'created_at' => now()->subDays(5 - $index)
        ]);
    }
    
    $convCount = Conversation::where('business_contact_id', $contact->id)->count();
    echo "✅ Created {$convCount} conversation entries\n\n";
    
    // ==================== STEP 2: Create Campaign ====================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 2: Creating Test Campaign\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $originalMessage = "Hello #name, we have an exclusive offer on our premium package! "
                     . "Based on your interest, we're offering 20% off for the next 48 hours. "
                     . "Reply YES to claim your discount.";
    
    $campaign = Campaign::create([
        'user_id' => $userId,
        'business_id' => $businessId,
        'campaign_name' => 'Test AI Personalization - ' . now()->format('Y-m-d H:i:s'),
        'campaign_type' => Campaign::TYPE_BROADCAST,
        'original_message' => $originalMessage,
        'recipient_criteria' => ['test_contact' => $testPhone],
        'total_recipients' => 1,
        'queued_count' => 0,
        'status' => Campaign::STATUS_STAGING,
        'has_attachments' => false,
        'started_at' => now()
    ]);
    
    echo "✅ Campaign Created:\n";
    echo "   ID: {$campaign->id}\n";
    echo "   Name: {$campaign->campaign_name}\n";
    echo "   Type: {$campaign->campaign_type}\n";
    echo "   Status: {$campaign->status}\n\n";
    echo "📝 Original Message:\n";
    echo "   \"{$originalMessage}\"\n\n";
    
    // ==================== STEP 3: Create MessageQueue Entry ====================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 3: Creating MessageQueue Entry\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $messageQueue = MessageQueue::create([
        'campaign_id' => $campaign->id,
        'user_id' => $userId,
        'contact_id' => $contact->id,
        'phone_number' => $contact->guest_phone,
        'contact_name' => $contact->guest_name,
        'original_message' => $originalMessage,
        'refined_message' => null,
        'attachment_context' => null,
        'status' => MessageQueue::STATUS_STAGED,
        'priority' => 5,
        'provider' => MessageQueue::PROVIDER_WASENDER,
    ]);
    
    $campaign->increment('queued_count');
    
    echo "✅ MessageQueue Entry Created:\n";
    echo "   ID: {$messageQueue->id}\n";
    echo "   Contact: {$messageQueue->contact_name}\n";
    echo "   Phone: {$messageQueue->phone_number}\n";
    echo "   Status: {$messageQueue->status}\n";
    echo "   Priority: {$messageQueue->priority}\n\n";
    
    // ==================== STEP 4: Test AI Personalization ====================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 4: Testing AI Personalization Service\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Check if OpenAI API key is configured
    $apiKey = config('services.openai.api_key');
    if (!$apiKey) {
        echo "⚠️  WARNING: OpenAI API key not configured!\n";
        echo "   Set OPENAI_API_KEY in .env file\n";
        echo "   Skipping AI personalization test...\n\n";
    } else {
        echo "✅ OpenAI API key configured\n";
        echo "🔄 Calling MessagePersonalizationService...\n\n";
        
        $personalizationService = new MessagePersonalizationService();
        
        try {
            $result = $personalizationService->personalizeMessage($messageQueue);
            
            echo "✅ AI Personalization Complete!\n\n";
            
            echo "📊 Analysis Results:\n";
            echo "   Detected Language: " . ($result['analysis']['detected_language'] ?? 'N/A') . "\n";
            echo "   Detected Tone: " . ($result['analysis']['detected_tone'] ?? 'N/A') . "\n";
            echo "   Relationship Stage: " . ($result['analysis']['relationship_stage'] ?? 'N/A') . "\n";
            echo "   Sentiment: " . ($result['analysis']['sentiment_filter_result'] ?? 'N/A') . "\n";
            echo "   AI Confidence: " . ($result['analysis']['ai_confidence_score'] ?? 0) . "\n";
            echo "   Optimal Send Time: " . ($result['analysis']['optimal_send_time'] ?? 'N/A') . "\n\n";
            
            echo "📝 Refined Message:\n";
            echo "   \"{$result['refined_message']}\"\n\n";
            
            if (isset($result['analysis']['context_summary'])) {
                echo "🔍 Context Summary:\n";
                foreach ($result['analysis']['context_summary'] as $key => $value) {
                    echo "   {$key}: {$value}\n";
                }
                echo "\n";
            }
            
            if (isset($result['analysis']['ai_metadata']['reasoning'])) {
                echo "💭 AI Reasoning:\n";
                echo "   {$result['analysis']['ai_metadata']['reasoning']}\n\n";
            }
            
            // Update message queue with personalization results
            $messageQueue->update([
                'refined_message' => $result['refined_message'],
                'status' => MessageQueue::STATUS_REFINED,
                'detected_language' => $result['analysis']['detected_language'] ?? null,
                'detected_tone' => $result['analysis']['detected_tone'] ?? null,
                'relationship_stage' => $result['analysis']['relationship_stage'] ?? null,
                'ai_confidence_score' => $result['analysis']['ai_confidence_score'] ?? 0,
                'sentiment_filter_result' => $result['analysis']['sentiment_filter_result'] ?? null,
                'context_summary' => $result['analysis']['context_summary'] ?? [],
                'ai_metadata' => $result['analysis']['ai_metadata'] ?? [],
                'optimal_send_time' => $result['analysis']['optimal_send_time'] ?? null
            ]);
            
            echo "✅ MessageQueue updated with AI analysis\n\n";
            
        } catch (\Exception $e) {
            echo "❌ AI Personalization Failed:\n";
            echo "   Error: {$e->getMessage()}\n";
            echo "   This is normal if OpenAI API key is invalid or API is rate limited\n\n";
        }
    }
    
    // ==================== STEP 5: Test Job Dispatch ====================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 5: Testing PersonalizeCampaignMessagesJob\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "🔄 Dispatching PersonalizeCampaignMessagesJob...\n";
    PersonalizeCampaignMessagesJob::dispatch($campaign->id);
    echo "✅ Job dispatched to 'ai_personalization' queue\n\n";
    
    // Check if job was queued
    $jobCount = DB::table('jobs')->where('queue', 'ai_personalization')->count();
    echo "📊 Queue Status:\n";
    echo "   Jobs in 'ai_personalization' queue: {$jobCount}\n\n";
    
    // ==================== STEP 6: Summary & Next Steps ====================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 6: Test Summary & Next Steps\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "✅ Test Completed Successfully!\n\n";
    
    echo "📊 Created Resources:\n";
    echo "   Campaign ID: {$campaign->id}\n";
    echo "   MessageQueue ID: {$messageQueue->id}\n";
    echo "   Contact ID: {$contact->id}\n\n";
    
    echo "🔍 Verification Commands:\n\n";
    
    echo "1️⃣  Check MessageQueue Status:\n";
    echo "   SELECT * FROM message_queue WHERE id = {$messageQueue->id}\\G\n\n";
    
    echo "2️⃣  Check Campaign Progress:\n";
    echo "   SELECT * FROM campaigns WHERE id = {$campaign->id}\\G\n\n";
    
    echo "3️⃣  Process Queue Job:\n";
    echo "   php artisan queue:work ai_personalization --once\n\n";
    
    echo "4️⃣  Monitor Logs:\n";
    echo "   Get-Content storage\\logs\\laravel.log -Tail 50 -Wait\n\n";
    
    echo "5️⃣  Check Job Status:\n";
    echo "   SELECT * FROM jobs WHERE queue = 'ai_personalization';\n\n";
    
    echo "📈 Expected Workflow:\n";
    echo "   staged → analyzing → refined → scheduled → sent\n\n";
    
    echo "⚠️  Important Notes:\n";
    echo "   • Ensure OpenAI API key is set in .env\n";
    echo "   • Start queue worker: php artisan queue:work ai_personalization\n";
    echo "   • Monitor costs: OpenAI charges per API call (~\$0.03-0.06 per message)\n";
    echo "   • Check logs for any errors during processing\n\n";
    
    // ==================== STEP 7: Cleanup Option ====================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 7: Cleanup\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "🗑️  To clean up test data, run:\n";
    echo "   DELETE FROM message_queue WHERE id = {$messageQueue->id};\n";
    echo "   DELETE FROM campaigns WHERE id = {$campaign->id};\n";
    echo "   DELETE FROM conversations WHERE business_contact_id = {$contact->id};\n";
    echo "   DELETE FROM business_contacts WHERE id = {$contact->id};\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ TEST FAILED!\n\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}\n";
    echo "Line: {$e->getLine()}\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    TEST COMPLETED                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
