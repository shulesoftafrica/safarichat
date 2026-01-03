<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking AI Agent Followup Configuration ===\n";

// Check AI agents and their followup settings
$agents = App\Models\AiSalesAgent::select('id', 'user_id', 'agent_name', 'auto_followup', 'followup_delay', 'max_followups', 'followup_message')
    ->get();

echo sprintf("Total AI agents: %d\n", $agents->count());

foreach($agents as $agent) {
    echo sprintf("Agent %d (%s): auto_followup=%s, delay=%s hours, max=%s, message='%s'\n",
        $agent->id,
        $agent->agent_name ?? 'unnamed',
        $agent->auto_followup ? 'YES' : 'NO',
        $agent->followup_delay ?? 'NULL',
        $agent->max_followups ?? 'NULL',
        substr($agent->followup_message ?? 'NULL', 0, 50)
    );
}

// Check recent conversations that might need followups
echo "\nRecent AI conversations:\n";
$recentConversations = App\Models\Conversation::where('message_type', 'AI_AGENT')
    ->with(['lead'])
    ->latest()
    ->take(5)
    ->get();

foreach($recentConversations as $conv) {
    $leadPhone = $conv->lead ? $conv->lead->phone_number : 'NO LEAD';
    echo sprintf("Conversation %d | Lead %d (%s) | State: %s | FollowupAttempt: %s\n",
        $conv->id,
        $conv->lead_id ?? 'NULL',
        $leadPhone,
        $conv->conversation_state,
        $conv->followup_attempt_at ? $conv->followup_attempt_at->format('Y-m-d H:i:s') : 'NULL'
    );
}