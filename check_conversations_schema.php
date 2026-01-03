<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Conversations Table Schema ===\n";

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Get table columns
$columns = DB::select("SELECT column_name, data_type, is_nullable 
                       FROM information_schema.columns 
                       WHERE table_name = 'conversations' 
                       ORDER BY ordinal_position");

echo "Conversations table columns:\n";
foreach($columns as $col) {
    echo sprintf("- %s (%s) %s\n", 
        $col->column_name, 
        $col->data_type,
        $col->is_nullable === 'YES' ? 'nullable' : 'not null'
    );
}

// Check specifically for followup fields
echo "\nFollowup-related columns:\n";
foreach($columns as $col) {
    if(strpos($col->column_name, 'followup') !== false) {
        echo sprintf("- %s (%s) %s\n", 
            $col->column_name, 
            $col->data_type,
            $col->is_nullable === 'YES' ? 'nullable' : 'not null'
        );
    }
}

// Check some sample conversations to understand the data
echo "\nSample conversation records:\n";
$conversations = App\Models\Conversation::select('id', 'lead_id', 'message_type', 'conversation_state', 'followup_attempt_at')
    ->take(5)
    ->get();

foreach($conversations as $conv) {
    echo sprintf("ID: %d | Lead: %s | Type: %s | State: %s | FollowupAttempt: %s\n",
        $conv->id,
        $conv->lead_id ?? 'NULL',
        $conv->message_type,
        $conv->conversation_state,
        $conv->followup_attempt_at ? $conv->followup_attempt_at->format('Y-m-d H:i:s') : 'NULL'
    );
}