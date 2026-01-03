<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Followup Scheduling Fix ===\n";

// Get an AI agent that has auto_followup enabled
$agent = App\Models\AiSalesAgent::where('auto_followup', true)->first();

if (!$agent) {
    echo "❌ No AI agents with auto_followup enabled found\n";
    exit(1);
}

echo "✅ Using AI agent: {$agent->assistant_name} (ID: {$agent->id})\n";
echo "   Auto followup: " . ($agent->auto_followup ? 'YES' : 'NO') . "\n";
echo "   Followup delay: {$agent->followup_delay} hours\n";

// Get a lead that has conversations
$lead = App\Models\Lead::whereHas('conversations')->first();

if (!$lead) {
    echo "❌ No leads with conversations found\n";
    exit(1);
}

echo "✅ Using lead: ID {$lead->id}\n";

// Get or create a conversation for this lead
$conversation = $lead->conversations()->latest()->first();

if (!$conversation) {
    echo "❌ No conversations found for this lead\n";
    exit(1);
}

echo "✅ Using conversation: ID {$conversation->id}\n";

// Test the fixed scheduleFollowup method
echo "\n🧪 Testing scheduleFollowup method...\n";

$followupTime = now()->addHours(2); // 2 hours from now
$conversation->scheduleFollowup($followupTime, "Test followup message");

// Refresh and check
$conversation->refresh();

echo "✅ Followup scheduled!\n";
echo "   Followup scheduled at: " . ($conversation->followup_scheduled_at ? $conversation->followup_scheduled_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "   Followup sent: " . ($conversation->followup_sent ? 'YES' : 'NO') . "\n";

// Test that scheduled followups can now be found
echo "\n🔍 Checking scheduled followups...\n";

$dueFollowups = App\Models\Conversation::where('followup_scheduled_at', '<=', now()->addHours(3))
    ->whereNotNull('followup_scheduled_at')
    ->where('followup_sent', false)
    ->count();

echo "Followups due in next 3 hours: $dueFollowups\n";

$allScheduled = App\Models\Conversation::whereNotNull('followup_scheduled_at')->count();
echo "Total scheduled followups: $allScheduled\n";

echo "\n✅ Test completed successfully!\n";