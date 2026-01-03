<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Message Processing Status ===\n";

// Check pending messages
$pending = DB::select("SELECT b.channel,a.email,a.phone, b.id as message_sentby_id, a.body,a.subject, a.user_id FROM messages a join messages_sentby b on a.id=b.message_id where return_code is null limit 10");

echo sprintf("Pending messages: %d\n", count($pending));

if (!empty($pending)) {
    echo "\nSample pending messages:\n";
    foreach($pending as $msg) {
        echo sprintf("ID: %d | Channel: %s | Phone: %s | Body: %s\n", 
            $msg->message_sentby_id, 
            $msg->channel,
            $msg->phone ?? 'NULL',
            substr($msg->body, 0, 50) . '...'
        );
    }
}

// Check if processScheduledFollowUps method still exists and is being called
echo "\nMessage processing methods:\n";
$reflection = new ReflectionClass(App\Http\Controllers\Message::class);
$methods = $reflection->getMethods();
foreach($methods as $method) {
    if(strpos($method->getName(), 'process') !== false) {
        echo "- " . $method->getName() . "\n";
    }
}