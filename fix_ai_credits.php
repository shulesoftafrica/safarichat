<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fixing AI Credits Issue ===\n";

try {
    // Update user credits directly
    $userId = 45;
    $creditsToAdd = 100000;
    
    DB::table('users')
        ->where('id', $userId)
        ->update(['ai_credits' => $creditsToAdd]);
    
    // Also update business credits if business exists
    $user = App\Models\User::find($userId);
    if ($user && $user->business) {
        DB::table('businesses')
            ->where('id', $user->business->id)
            ->update(['ai_credits' => $creditsToAdd]);
        echo "✅ Updated business AI credits to $creditsToAdd\n";
    }
    
    echo "✅ Updated user AI credits to $creditsToAdd\n";
    
    // Clear billing cache to force refresh
    App\Services\BillingService::clearCache($userId);
    echo "✅ Cleared billing cache\n";
    
    // Test current credits
    $currentUser = App\Models\User::find($userId);
    echo "✅ Current user credits: " . $currentUser->ai_credits . "\n";
    
    // Test billing status
    $billingStatus = App\Services\BillingService::getCachedStatus($userId);
    echo "✅ Billing AI credits: " . ($billingStatus['limits']['ai_credits']['balance'] ?? 'N/A') . "\n";
    
    echo "\n🎉 Credits successfully added! Try running the AI processing command again.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}