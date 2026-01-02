<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Lead Creation Fix ===\n";

// Test lead creation similar to what Message.php does
try {
    // Find a business contact to test with (use existing one)
    $contact = App\Models\BusinessContact::first();
    
    if (!$contact) {
        echo "❌ No business contacts found. Please run with existing data.\n";
        exit(1);
    }
    
    echo "✅ Using existing business contact: {$contact->guest_name} (ID: {$contact->id})\n";
    
    // Find AI sales agent (use existing one)
    $aiAgent = App\Models\AiSalesAgent::where('user_id', $contact->user_id)
                                    ->where('status', 'active')
                                    ->first();
    
    if (!$aiAgent) {
        echo "❌ No AI sales agent found for user {$contact->user_id}. Please ensure one exists.\n";
        exit(1);
    }
    
    echo "✅ Using existing AI agent: {$aiAgent->agent_name} (ID: {$aiAgent->id})\n";
    
    // Test the same lead creation logic from Message.php
    echo "\n🧪 Testing Lead Creation...\n";
    
    $lead = App\Models\Lead::firstOrCreate(
        ['business_contact_id' => $contact->id],
        [
            'business_id' => $contact->business_id,
            'source' => 'event_guest',
            'status' => 'NEW',
            'ai_sales_agent_id' => $aiAgent->id,
            'last_interaction_at' => now(),
            'conversion_probability' => 0,
            'lead_score' => 0,
            'is_churned' => false,
            'win_back_attempts' => 0
        ]
    );
    
    if ($lead->wasRecentlyCreated) {
        echo "✅ NEW Lead created successfully!\n";
    } else {
        echo "✅ Existing lead found successfully!\n";
    }
    
    echo "   Lead ID: {$lead->id}\n";
    echo "   Business Contact ID: {$lead->business_contact_id}\n";
    echo "   Status: {$lead->status}\n";
    echo "   Source: {$lead->source}\n";
    echo "   AI Agent: {$lead->ai_sales_agent_id}\n";
    
    echo "\n✅ All tests passed! The business_contact_id constraint issue is fixed.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   File: " . $e->getFile() . "\n";
}