<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lead;

echo "=== Lead Status Distribution Analysis ===\n\n";

$statuses = Lead::selectRaw('status, COUNT(*) as count')
               ->groupBy('status')
               ->orderBy('count', 'desc')
               ->get();

echo "Current Status Distribution:\n";
foreach($statuses as $status) {
    echo sprintf("  %-20s: %d leads\n", $status->status, $status->count);
}

echo "\nTotal leads: " . Lead::count() . "\n\n";

// Check when leads were last updated
echo "Lead Update Timeline:\n";
$recent = Lead::selectRaw('DATE(updated_at) as date, COUNT(*) as count')
             ->where('updated_at', '>', now()->subDays(30))
             ->groupBy('date')
             ->orderBy('date', 'desc')
             ->limit(10)
             ->get();

if($recent->isEmpty()) {
    echo "  No recent updates found in last 30 days\n";
} else {
    foreach($recent as $day) {
        echo "  {$day->date}: {$day->count} leads updated\n";
    }
}

// Check for any automated activity signs
echo "\nChecking Automation Signs:\n";

$outreached = Lead::where('status', 'OUTREACHED')->count();
$withLastContact = Lead::whereNotNull('last_contact_at')->count();
$withLastInteraction = Lead::whereNotNull('last_interaction_at')->count();

echo "  Leads marked as OUTREACHED: {$outreached}\n";
echo "  Leads with last_contact_at: {$withLastContact}\n";  
echo "  Leads with last_interaction_at: {$withLastInteraction}\n";

// Check if there are any conversations
$conversations = \App\Models\Conversation::count();
echo "  Total conversations: {$conversations}\n";

// Check for AI sales agents
$agents = \App\Models\AiSalesAgent::where('status', 'active')->count();
echo "  Active AI agents: {$agents}\n";