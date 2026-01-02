<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Conversations Table Structure ===\n";
$columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'conversations'");
foreach($columns as $col) {
    echo "  - " . $col->column_name . "\n";
}

echo "\n=== Resetting Conversations with Correct Fields ===\n";

// Reset only the status field since that seems to be what exists
$updated = DB::table('conversations')
    ->whereIn('id', [6, 7, 8, 9, 10])
    ->update([
        'status' => 'pending',
        'updated_at' => now()
    ]);

echo "Reset $updated conversation(s) to pending status\n";

echo "\n✅ Try running the AI command now!\n";