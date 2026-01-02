<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== AI Credits Diagnostic ===\n";

try {
    // Check conversations that are failing
    $conversations = DB::table('conversations')
        ->whereIn('id', [6, 7, 8, 9, 10])
        ->get();
    
    echo "Found " . count($conversations) . " conversations\n\n";
    
    foreach ($conversations as $conversation) {
        echo "=== Conversation #$conversation->id ===\n";
        
        // Get the lead and user info
        $lead = App\Models\Lead::find($conversation->lead_id);
        if (!$lead) {
            echo "  ❌ Lead not found for conversation\n";
            continue;
        }
        
        echo "  Lead ID: $lead->id\n";
        echo "  User ID: $lead->user_id\n";
        
        // Get user and check credits
        $user = App\Models\User::find($lead->user_id);
        if (!$user) {
            echo "  ❌ User not found\n";
            continue;
        }
        
        echo "  User Name: $user->name\n";
        echo "  User AI Credits: " . ($user->ai_credits ?? 'NULL') . "\n";
        
        // Get business credits
        if ($user->business) {
            echo "  Business AI Credits: " . ($user->business->ai_credits ?? 'NULL') . "\n";
        }
        
        // Check billing status
        $customerId = $user->customer_id ?? $user->id;
        $billingStatus = App\Services\BillingService::getCachedStatus($customerId);
        
        echo "  Billing Status:\n";
        echo "    - Use AI Permission: " . ($billingStatus['permissions']['use_ai'] ? 'YES' : 'NO') . "\n";
        echo "    - AI Credits Balance: " . ($billingStatus['limits']['ai_credits']['balance'] ?? 'N/A') . "\n";
        echo "    - Subscription Plan: " . ($billingStatus['subscription']['plan'] ?? 'Unknown') . "\n";
        echo "    - Subscription Active: " . ($billingStatus['subscription']['active'] ? 'YES' : 'NO') . "\n";
        
        // Check what's needed for this conversation
        $messageBody = $conversation->message_content ?? 'Test message';
        $estimatedTokens = strlen($messageBody) * 1.3 + 500;
        $estimatedCredits = max(1, ceil($estimatedTokens / 3.846));
        
        echo "  Estimated Credits Needed: $estimatedCredits\n";
        
        // Check if this would pass
        $available = $billingStatus['limits']['ai_credits']['balance'] ?? 0;
        echo "  Would Pass: " . ($available >= $estimatedCredits ? 'YES' : 'NO') . "\n";
        
        echo "\n";
    }
    
    echo "=== OpenAI Configuration Check ===\n";
    $openaiKey = env('OPENAI_API_KEY');
    echo "OpenAI API Key set: " . ($openaiKey ? 'YES (' . substr($openaiKey, 0, 10) . '...)' : 'NO') . "\n";
    
    echo "\n=== Potential Solutions ===\n";
    echo "1. Add credits to your local database:\n";
    echo "   UPDATE users SET ai_credits = 10000 WHERE id IN (select distinct user_id from leads where id in (select lead_id from conversations where id in (6,7,8,9,10)));\n\n";
    
    echo "2. Check if billing system is configured correctly\n";
    echo "3. Verify OpenAI API key is working\n\n";
    
    // Test a simple OpenAI call
    echo "=== Testing OpenAI Connection ===\n";
    try {
        $openai = new App\Services\OpenAiService();
        // This would test if the API key works
        echo "OpenAI Service initialized successfully\n";
    } catch (Exception $e) {
        echo "❌ OpenAI Service error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}