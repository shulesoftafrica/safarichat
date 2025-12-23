<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🔍 Checking System Message Log Errors\n";
echo "======================================\n\n";

$failedLogs = \App\Models\SystemMessageLog::where('status', 'failed')
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get(['id', 'phone_number', 'error_message', 'created_at']);

foreach ($failedLogs as $log) {
    echo "Log ID: {$log->id}\n";
    echo "Phone: {$log->phone_number}\n";
    echo "Created: {$log->created_at}\n";
    echo "Error: " . ($log->error_message ?: 'No error message recorded') . "\n";
    echo "---\n";
}