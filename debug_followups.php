<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Investigating Followup Issues ===\n";

// Check follow-up counts
$dueFollowups = App\Models\Conversation::where('followup_scheduled_at', '<=', now())
    ->whereNotNull('followup_scheduled_at')
    ->where('followup_sent', false)
    ->count();
echo "Due followups: $dueFollowups\n";

$totalFollowups = App\Models\Conversation::whereNotNull('followup_scheduled_at')->count();
echo "Total followups scheduled: $totalFollowups\n";

$sentFollowups = App\Models\Conversation::where('followup_sent', true)->count();
echo "Sent followups: $sentFollowups\n";

// Show some sample scheduled followups
$samples = App\Models\Conversation::whereNotNull('followup_scheduled_at')
    ->select('id', 'followup_scheduled_at', 'followup_sent', 'lead_id')
    ->take(5)
    ->get();

echo "\nSample scheduled followups:\n";
foreach($samples as $f) {
    echo sprintf("ID: %d | Scheduled: %s | Sent: %s | Lead: %d\n", 
        $f->id, 
        $f->followup_scheduled_at ? $f->followup_scheduled_at->format('Y-m-d H:i:s') : 'NULL',
        $f->followup_sent ? 'Yes' : 'No', 
        $f->lead_id ?? 'NULL'
    );
}

// Check if leads have proper phone numbers
echo "\nChecking lead phone numbers for scheduled followups:\n";
$followupsWithLeads = App\Models\Conversation::whereNotNull('followup_scheduled_at')
    ->with('lead')
    ->take(5)
    ->get();

foreach($followupsWithLeads as $conv) {
    $phone = $conv->lead ? $conv->lead->phone_number : 'NO LEAD';
    echo sprintf("Conversation %d -> Lead %d -> Phone: %s\n", 
        $conv->id, 
        $conv->lead_id ?? 'NULL',
        $phone
    );
}