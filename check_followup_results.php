<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Followup Processing Results ===\n";

// Check the conversation we scheduled followup for
$conversation = App\Models\Conversation::find(9);

if ($conversation) {
    echo "Conversation 9 status:\n";
    echo "   Followup scheduled at: " . ($conversation->followup_scheduled_at ? $conversation->followup_scheduled_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "   Followup sent: " . ($conversation->followup_sent ? 'YES' : 'NO') . "\n";
    echo "   Is due? " . ($conversation->followup_scheduled_at && $conversation->followup_scheduled_at <= now() ? 'YES' : 'NO') . "\n";
} else {
    echo "Conversation 9 not found\n";
}

// Check overall followup counts
$totalScheduled = App\Models\Conversation::whereNotNull('followup_scheduled_at')->count();
$totalSent = App\Models\Conversation::where('followup_sent', true)->count();
$totalDue = App\Models\Conversation::where('followup_scheduled_at', '<=', now())
    ->whereNotNull('followup_scheduled_at')
    ->where('followup_sent', false)
    ->count();

echo "\nOverall followup statistics:\n";
echo "Total scheduled: $totalScheduled\n";
echo "Total sent: $totalSent\n";
echo "Currently due: $totalDue\n";

// Check the latest logs
echo "\nRecent cron logs:\n";
$logFile = storage_path('logs/cron-monitor.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -10);
    foreach($recentLines as $line) {
        if (!empty(trim($line))) {
            echo "   $line\n";
        }
    }
} else {
    echo "   No cron logs found\n";
}