<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Creating Due Followup Test ===\n";

// Get a conversation and schedule a followup that's already due
$conversation = App\Models\Conversation::first();

if (!$conversation) {
    echo "❌ No conversations found\n";
    exit(1);
}

// Schedule a followup for 1 minute ago (so it's due)
$pastTime = now()->subMinutes(1);
$conversation->scheduleFollowup($pastTime, "This is a test followup message - please respond if you're interested!");

echo "✅ Scheduled followup for conversation {$conversation->id} at {$pastTime->format('Y-m-d H:i:s')}\n";

// Check that it's due
$dueFollowups = App\Models\Conversation::where('followup_scheduled_at', '<=', now())
    ->whereNotNull('followup_scheduled_at')
    ->where('followup_sent', false)
    ->count();

echo "Due followups: $dueFollowups\n";

// Now run the scheduled task to see if it processes
echo "\n🔄 Running scheduled followups...\n";

// Manually call the followup processing method
$kernel = app(App\Console\Kernel::class);
$reflection = new ReflectionClass($kernel);
$method = $reflection->getMethod('processScheduledFollowups');
$method->setAccessible(true);

try {
    $method->invoke($kernel);
    echo "✅ Followup processing completed\n";
} catch (Exception $e) {
    echo "❌ Followup processing failed: " . $e->getMessage() . "\n";
}

// Check the results
$conversation->refresh();
echo "\nResults:\n";
echo "   Followup sent: " . ($conversation->followup_sent ? 'YES' : 'NO') . "\n";

$dueFollowupsAfter = App\Models\Conversation::where('followup_scheduled_at', '<=', now())
    ->whereNotNull('followup_scheduled_at')
    ->where('followup_sent', false)
    ->count();

echo "Due followups after processing: $dueFollowupsAfter\n";