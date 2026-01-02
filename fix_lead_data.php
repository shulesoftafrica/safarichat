<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lead;

echo "=== Fixing Lead Data Issues ===\n\n";

// Fix lead without user_id
$leadWithoutUser = Lead::whereNull('user_id')->first();
if ($leadWithoutUser) {
    // Get the AI agent's user_id 
    $agent = $leadWithoutUser->aiSalesAgent;
    if ($agent) {
        $leadWithoutUser->update(['user_id' => $agent->user_id]);
        echo "✅ Fixed lead {$leadWithoutUser->id} - assigned to user {$agent->user_id}\n";
    }
}

// Ensure leads have proper contact relationships
$leads = Lead::with('contact')->get();
foreach ($leads as $lead) {
    if (!$lead->contact && $lead->events_guest_id) {
        echo "⚠️  Lead {$lead->id} has events_guest_id but no contact relationship\n";
    } elseif (!$lead->contact) {
        echo "⚠️  Lead {$lead->id} has no contact information\n";
    } else {
        echo "✅ Lead {$lead->id} has proper contact: {$lead->contact->guest_phone}\n";
    }
}

echo "\n=== Testing Manual Status Update ===\n";

// Test manual status update to verify system works
$testLead = Lead::first();
if ($testLead) {
    $originalStatus = $testLead->status;
    $testLead->update(['status' => 'OUTREACHED']);
    echo "✅ Manual update works: Lead {$testLead->id} changed to OUTREACHED\n";
    $testLead->update(['status' => $originalStatus]);
    echo "✅ Reverted to: {$originalStatus}\n";
}