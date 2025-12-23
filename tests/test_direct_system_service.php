<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Services\SystemWhatsAppService;

// Bootstrap Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Request::capture();
$response = $kernel->handle($request);

echo "🔧 Direct SystemWhatsAppService Method Test\n";
echo "===========================================\n\n";

try {
    $systemService = app(SystemWhatsAppService::class);
    
    echo "1. Testing sendSystemMessage directly...\n";
    
    $result = $systemService->sendSystemMessage(
        '+255700999888',
        'Direct test message from sendSystemMessage',
        'system_notification'
    );
    
    echo "Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "\n\n";
    
    echo "2. Testing sendGenericMessage...\n";
    
    $genericResult = $systemService->sendGenericMessage(
        '+255700999777',
        'Direct test message from sendGenericMessage',
        'system_notification'
    );
    
    echo "Result: " . ($genericResult ? "✅ SUCCESS" : "❌ FAILED") . "\n\n";
    
    echo "3. Check recent system message logs...\n";
    $recentLogs = \App\Models\SystemMessageLog::orderBy('created_at', 'desc')->limit(5)->get();
    
    echo "Recent logs count: " . $recentLogs->count() . "\n";
    foreach ($recentLogs as $log) {
        echo "- {$log->created_at}: {$log->message_type} to {$log->phone_number} ({$log->status})\n";
    }
    
    echo "\n4. Check recent outgoing messages...\n";
    $recentOutgoing = \App\Models\OutgoingMessage::where('is_system_message', true)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "Recent system outgoing messages count: " . $recentOutgoing->count() . "\n";
    foreach ($recentOutgoing as $msg) {
        echo "- {$msg->created_at}: to {$msg->phone_number} ({$msg->status})\n";
    }
    
    echo "\n✨ Direct Test Complete!\n";
    
} catch (Exception $e) {
    echo "❌ Test Failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}