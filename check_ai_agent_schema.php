<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== AI Sales Agents Table Schema ===\n";

use Illuminate\Support\Facades\DB;

// Get table columns
$columns = DB::select("SELECT column_name, data_type, is_nullable 
                       FROM information_schema.columns 
                       WHERE table_name = 'ai_sales_agents' 
                       ORDER BY ordinal_position");

echo "AI Sales Agents table columns:\n";
foreach($columns as $col) {
    if(strpos($col->column_name, 'followup') !== false || 
       strpos($col->column_name, 'auto') !== false ||
       strpos($col->column_name, 'name') !== false) {
        echo sprintf("- %s (%s) %s\n", 
            $col->column_name, 
            $col->data_type,
            $col->is_nullable === 'YES' ? 'nullable' : 'not null'
        );
    }
}

// Get sample AI agent data
echo "\nSample AI agents:\n";
$agents = App\Models\AiSalesAgent::select('id', 'user_id', 'assistant_name', 'auto_followup', 'followup_delay', 'max_followups')
    ->take(5)
    ->get();

foreach($agents as $agent) {
    echo sprintf("Agent %d (%s): auto_followup=%s, delay=%s hours, max=%s\n",
        $agent->id,
        $agent->assistant_name ?? 'unnamed',
        $agent->auto_followup ? 'YES' : 'NO',
        $agent->followup_delay ?? 'NULL',
        $agent->max_followups ?? 'NULL'
    );
}