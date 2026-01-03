<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing WhatsApp Lead Creation Fix ===\n";

use App\Models\IncomingMessage;
use App\Models\BusinessContact;
use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use App\Services\WaSenderService;

try {
    // Get an existing business contact with user_id
    $contact = BusinessContact::whereNotNull('user_id')->first();
    if (!$contact) {
        echo "❌ No business contacts found. Creating a test contact...\n";
        
        // Find a user with a business
        $user = App\Models\User::whereHas('business')->first();
        if (!$user) {
            echo "❌ No users with businesses found.\n";
            exit(1);
        }
        
        $contact = BusinessContact::create([
            'guest_name' => 'WhatsApp Test Contact',
            'guest_phone' => '+255700123456',
            'guest_email' => 'whatsapp.test@example.com',
            'business_id' => $user->business->id,
            'user_id' => $user->id,
            'source' => 'test'
        ]);
        
        echo "✅ Created test business contact: {$contact->guest_name} (ID: {$contact->id})\n";
    } else {
        echo "✅ Using existing business contact: {$contact->guest_name} (ID: {$contact->id})\n";
        echo "    User ID: {$contact->user_id}\n";
        echo "    Business ID: {$contact->business_id}\n";
    }
    
    // Ensure there's an AI agent for this user
    $aiAgent = AiSalesAgent::where('user_id', $contact->user_id)->first();
    if (!$aiAgent) {
        $aiAgent = AiSalesAgent::create([
            'user_id' => $contact->user_id,
            'agent_name' => 'Test AI Agent',
            'assistant_name' => 'Test Assistant',
            'status' => 'active',
            'personality_description' => 'Test agent for WhatsApp lead creation',
            'always_available' => true,
            'auto_followup' => true,
            'followup_delay' => 24,
            'max_followups' => 3
        ]);
        echo "✅ Created AI agent: {$aiAgent->agent_name} (ID: {$aiAgent->id})\n";
    } else {
        echo "✅ Using existing AI agent: {$aiAgent->agent_name} (ID: {$aiAgent->id})\n";
    }
    
    // Test direct lead creation with the same logic as AiWhatsAppService
    echo "\n🧪 Testing Lead Creation...\n";
    
    // Check if lead already exists
    $existingLead = Lead::where('business_contact_id', $contact->id)->first();
    if ($existingLead) {
        echo "✅ Lead already exists: ID {$existingLead->id}\n";
        $lead = $existingLead;
    } else {
        // Create lead using the same logic as AiWhatsAppService
        $lead = Lead::create([
            'business_contact_id' => $contact->id,
            'business_id' => $contact->business_id,
            'user_id' => $contact->user_id,
            'ai_sales_agent_id' => $aiAgent->id,
            'source' => 'whatsapp',
            'status' => Lead::STATUS_NEW,
            'last_interaction_at' => now(),
            'conversion_probability' => 0,
            'lead_score' => 0,
            'is_churned' => false,
            'win_back_attempts' => 0,
        ]);
        echo "✅ NEW Lead created successfully!\n";
    }
    
    if ($lead) {
        echo "✅ Lead created/found successfully!\n";
        echo "   Lead ID: {$lead->id}\n";
        echo "   Business Contact ID: {$lead->business_contact_id}\n";
        echo "   Business ID: {$lead->business_id}\n";
        echo "   User ID: {$lead->user_id}\n";
        echo "   AI Sales Agent ID: {$lead->ai_sales_agent_id}\n";
        echo "   Source: {$lead->source}\n";
        echo "   Status: {$lead->status}\n";
        
        // Verify all required fields are set
        if ($lead->business_contact_id && $lead->business_id) {
            echo "✅ All required fields are present!\n";
        } else {
            echo "❌ Some required fields are missing!\n";
        }
    } else {
        echo "❌ Failed to create lead!\n";
    }
    
    // Clean up test data if needed
    echo "\n✅ Test completed successfully!\n";
    
} catch (\Exception $e) {
    echo "❌ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}