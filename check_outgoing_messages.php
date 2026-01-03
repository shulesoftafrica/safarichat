<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Outgoing Messages ===\n";

// Check recent outgoing messages
$recentMessages = App\Models\OutgoingMessage::orderBy('created_at', 'desc')
    ->take(10)
    ->get();

echo "Recent outgoing messages:\n";
foreach($recentMessages as $msg) {
    echo sprintf("ID: %d | Phone: %s | Status: %s | Message: %s | Created: %s\n",
        $msg->id,
        $msg->phone_number,
        $msg->status,
        substr($msg->message_body, 0, 50) . '...',
        $msg->created_at->format('Y-m-d H:i:s')
    );
}