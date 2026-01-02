<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Conversation Status Check ===\n";
$conversations = DB::table('conversations')
    ->whereIn('id', [6, 7, 8, 9, 10])
    ->select('id', 'status', 'ai_processing_status', 'created_at')
    ->get();

foreach($conversations as $conv) {
    echo "Conversation #$conv->id: Status=$conv->status, AI Status=$conv->ai_processing_status\n";
}

echo "\nUser Credits Check:\n";
$user = App\Models\User::find(45);
echo "User $user->name now has $user->ai_credits AI credits\n";

echo "\nBilling Status Check:\n";
$billingStatus = App\Services\BillingService::getCachedStatus(45);
echo "Cached AI Credits Balance: " . ($billingStatus['limits']['ai_credits']['balance'] ?? 'N/A') . "\n";

// Force refresh billing status to pick up new credits
echo "\nRefreshing billing cache...\n";
App\Services\BillingService::clearCache(45);
$newBillingStatus = App\Services\BillingService::getCachedStatus(45);
echo "New AI Credits Balance: " . ($newBillingStatus['limits']['ai_credits']['balance'] ?? 'N/A') . "\n";