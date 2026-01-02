<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Conversation Status Analysis ===\n";

// Check conversations that were failing
$conversations = DB::table('conversations')
    ->whereIn('id', [6, 7, 8, 9, 10])
    ->get();

foreach($conversations as $conv) {
    echo "\nConversation #$conv->id:\n";
    echo "  Status: $conv->status\n";
    echo "  AI Processing Status: " . ($conv->ai_processing_status ?? 'NULL') . "\n";
    echo "  Created: $conv->created_at\n";
    echo "  Updated: $conv->updated_at\n";
}

echo "\n=== Resetting Failed Conversations ===\n";

// Reset the conversations to allow them to be processed again
$updated = DB::table('conversations')
    ->whereIn('id', [6, 7, 8, 9, 10])
    ->update([
        'ai_processing_status' => 'pending',
        'status' => 'pending',
        'retry_count' => 0,
        'last_error' => null,
        'updated_at' => now()
    ]);

echo "Reset $updated conversation(s) to pending status\n";

echo "\n🎯 Now try running: php artisan ai-agent:process-conversations --limit=5\n";