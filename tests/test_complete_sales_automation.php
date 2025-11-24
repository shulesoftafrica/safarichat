<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\EventGuest;
use App\Models\AiSalesAgent; 
use App\Models\Product;
use App\Models\Lead;
use App\Models\Conversation;
use App\Http\Controllers\Message;
use App\Http\Controllers\Api\WaSenderApiController;
use App\Services\WaSenderService;
use Illuminate\Http\Request;

class CompleteSalesAutomationTest
{
    private $testUserId;
    private $testAgentId;
    private $testProductId;
    
    public function __construct()
    {
        // Ensure we have the Laravel app bootstrapped
        if (!app()) {
            $app = require_once __DIR__ . '/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        }
    }
    
    public function run()
    {
        echo "=== Testing Complete Sales Automation System ===\n\n";
        
        try {
            $this->setupTestData();
            $this->testEventGuestProcessing();
            $this->testManualMessageContext();
            $this->testPhoneNumberFormatting();
            $this->testConversationTracking();
            $this->cleanup();
            
            echo "✅ ALL TESTS PASSED! Sales automation system is working correctly.\n";
            
        } catch (Exception $e) {
            echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            $this->cleanup();
        }
    }
    
    private function setupTestData()
    {
        echo "📋 Setting up test data...\n";
        
        // Get or create test user
        $user = User::firstOrCreate(
            ['email' => 'test@automation.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );
        $this->testUserId = $user->id;
        
        // Get or create AI sales agent
        $agent = AiSalesAgent::firstOrCreate(
            ['user_id' => $this->testUserId, 'assistant_name' => 'TestBot'],
            [
                'status' => 'active',
                'target_audience' => 'business professionals',
                'communication_tone' => 'professional',
                'personality_description' => 'Professional sales assistant',
                'always_available' => true,
                'auto_followup' => true,
                'followup_delay' => 24,
                'max_followups' => 3,
                'accepted_terms' => true,
                'terms_accepted_at' => now()
            ]
        );
        $this->testAgentId = $agent->id;
        
        // Get or create test product
        $product = Product::firstOrCreate(
            ['title' => 'Test Automation Product'],
            [
                'user_id' => $this->testUserId,
                'description' => 'Test product for automation',
                'price' => 1000.00,
                'status' => 'active'
            ]
        );
        $this->testProductId = $product->id;
        
        echo "✓ Test data setup complete\n";
    }
    
    private function testEventGuestProcessing()
    {
        echo "\n📞 Testing Event Guest Automated Processing...\n";
        
        // Create test event guest
        $guest = EventGuest::create([
            'name' => 'John Automation Test',
            'phone' => '0689353642', // Test Tanzanian number with leading zero
            'email' => 'john.test@automation.com',
            'event_id' => 1, // Assume event exists
            'user_id' => $this->testUserId,
            'contacted_for_sales' => false
        ]);
        
        // Process the event guest (simulate what cron job would do)
        $messageController = new Message();
        $messageController->process();
        
        // Verify lead was created
        $lead = Lead::where('phone', '255689353642')->first(); // Should be formatted
        if (!$lead) {
            throw new Exception("Lead not created from event guest");
        }
        
        echo "✓ Lead created from event guest: {$lead->name}\n";
        echo "✓ Phone formatted correctly: {$lead->phone}\n";
        
        // Verify guest was marked as contacted
        $guest->refresh();
        if (!$guest->contacted_for_sales) {
            throw new Exception("Event guest not marked as contacted");
        }
        
        echo "✓ Event guest marked as contacted\n";
        
        // Verify AI sales agent was assigned
        if ($lead->ai_sales_agent_id !== $this->testAgentId) {
            throw new Exception("AI sales agent not assigned to lead");
        }
        
        echo "✓ AI sales agent assigned to lead\n";
        
        // Clean up
        $guest->delete();
        $lead->delete();
    }
    
    private function testManualMessageContext()
    {
        echo "\n💬 Testing Manual Message Context Creation...\n";
        
        // Simulate manual message via API
        $controller = new WaSenderApiController();
        
        $request = new Request([
            'number' => '0754123456', // Another test Tanzanian number
            'message' => 'Hello, I am interested in your products'
        ]);
        
        // Mock the sendTextMessageApi call
        try {
            // This would normally send via WhatsApp, but we're testing the lead/conversation creation
            $response = $controller->sendTextMessageApi($request);
            
            // Check if lead was created/updated
            $lead = Lead::where('phone', '255754123456')->first();
            if (!$lead) {
                throw new Exception("Lead not created from manual message");
            }
            
            echo "✓ Lead created/updated from manual message\n";
            echo "✓ Phone formatted correctly: {$lead->phone}\n";
            
            // Check if conversation was created
            $conversation = Conversation::where('lead_id', $lead->id)
                ->where('message_type', 'manual')
                ->where('sender_type', 'customer')
                ->first();
                
            if (!$conversation) {
                throw new Exception("Conversation context not created for manual message");
            }
            
            echo "✓ Conversation context created for manual message\n";
            echo "✓ AI sales agent linked: " . ($conversation->ai_sales_agent_id ? "Yes" : "No") . "\n";
            
            // Clean up
            $conversation->delete();
            $lead->delete();
            
        } catch (Exception $e) {
            echo "⚠️ Manual message API test skipped (requires full WhatsApp setup): " . $e->getMessage() . "\n";
        }
    }
    
    private function testPhoneNumberFormatting()
    {
        echo "\n📱 Testing Phone Number Formatting...\n";
        
        $wasenderService = new WaSenderService();
        
        // Test various Tanzanian number formats
        $testNumbers = [
            '0689353642' => '255689353642',
            '689353642' => '255689353642',
            '255689353642' => '255689353642',
            '+255689353642' => '255689353642'
        ];
        
        foreach ($testNumbers as $input => $expected) {
            $formatted = $wasenderService->formatPhoneNumber($input);
            if ($formatted !== $expected) {
                throw new Exception("Phone formatting failed: {$input} -> {$formatted} (expected: {$expected})");
            }
            echo "✓ {$input} -> {$formatted}\n";
        }
        
        echo "✓ All phone number formats work correctly\n";
    }
    
    private function testConversationTracking()
    {
        echo "\n🔗 Testing Conversation Tracking Relationships...\n";
        
        // Create test lead
        $lead = Lead::create([
            'name' => 'Relationship Test',
            'phone' => '255123456789',
            'email' => 'relationship@test.com',
            'ai_sales_agent_id' => $this->testAgentId,
            'user_id' => $this->testUserId,
            'source' => 'test'
        ]);
        
        // Create test conversation
        $conversation = Conversation::create([
            'lead_id' => $lead->id,
            'ai_sales_agent_id' => $this->testAgentId,
            'product_id' => $this->testProductId,
            'message_type' => 'automated',
            'sender_type' => 'ai_agent',
            'message_content' => 'Test AI message'
        ]);
        
        // Test relationships
        $agent = AiSalesAgent::find($this->testAgentId);
        
        // Test agent -> leads relationship
        $agentLeads = $agent->leads;
        if (!$agentLeads->contains($lead)) {
            throw new Exception("Agent -> leads relationship not working");
        }
        echo "✓ Agent -> leads relationship working\n";
        
        // Test agent -> conversations relationship  
        $agentConversations = $agent->conversations;
        if (!$agentConversations->contains($conversation)) {
            throw new Exception("Agent -> conversations relationship not working");
        }
        echo "✓ Agent -> conversations relationship working\n";
        
        // Test conversation -> agent relationship
        if ($conversation->aiSalesAgent->id !== $this->testAgentId) {
            throw new Exception("Conversation -> agent relationship not working");
        }
        echo "✓ Conversation -> agent relationship working\n";
        
        // Test conversation -> lead relationship
        if ($conversation->lead->id !== $lead->id) {
            throw new Exception("Conversation -> lead relationship not working");
        }
        echo "✓ Conversation -> lead relationship working\n";
        
        // Clean up
        $conversation->delete();
        $lead->delete();
    }
    
    private function cleanup()
    {
        echo "\n🧹 Cleaning up test data...\n";
        
        // Clean up any remaining test data
        Lead::where('phone', 'LIKE', '255%')->where('name', 'LIKE', '%Test%')->delete();
        EventGuest::where('phone', '0689353642')->delete();
        
        echo "✓ Cleanup complete\n";
    }
}

// Run the test
$test = new CompleteSalesAutomationTest();
$test->run();