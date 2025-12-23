<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Controller;
use App\Models\OutgoingMessage;

echo "=== Analyzing Message Sending Status ===\n";

try {
    // Check recent OutgoingMessage records
    echo "\n1. Recent Message Records (Last 5)...\n";
    $recentMessages = OutgoingMessage::orderBy('created_at', 'desc')->limit(5)->get();
    
    foreach ($recentMessages as $msg) {
        echo "   ID: {$msg->id}\n";
        echo "   Phone: {$msg->phone_number}\n";
        echo "   Status: {$msg->status}\n";
        echo "   Message: " . substr($msg->message, 0, 50) . (strlen($msg->message) > 50 ? '...' : '') . "\n";
        echo "   Created: {$msg->created_at}\n";
        echo "   Sent At: " . ($msg->sent_at ?: 'Not sent') . "\n";
        echo "   External ID: " . ($msg->external_id ?: 'None') . "\n";
        echo "   API Response: " . (isset($msg->waapi_response) ? 'Present' : 'None') . "\n";
        echo "   ---\n";
    }
    
    // Check system message logs
    echo "\n2. System Message Logs (Last 5)...\n";
    $systemLogs = \DB::table('system_message_logs')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($systemLogs as $log) {
        echo "   ID: {$log->id}\n";
        echo "   Phone: {$log->phone_number}\n";
        echo "   Status: {$log->status}\n";
        echo "   Message Type: {$log->message_type}\n";
        echo "   Created: {$log->created_at}\n";
        echo "   ---\n";
    }
    
    // Test a quick message send and track it
    echo "\n3. Testing Message Send with Tracking...\n";
    $testPhone = '255714825469'; // Full international format
    $message = 'Debug test: ' . date('H:i:s');
    
    echo "   Sending to: $testPhone\n";
    echo "   Message: $message\n";
    
    $controller = new Controller();
    $result = $controller->sendTextMessage($testPhone, $message, 'whatsapp', 'otp');
    echo "   Result: " . json_encode($result->original) . "\n";
    
    // Wait a moment and check the record
    sleep(3);
    
    $latestMessage = OutgoingMessage::orderBy('created_at', 'desc')->first();
    if ($latestMessage) {
        echo "\n4. Latest Message Record...\n";
        echo "   ID: {$latestMessage->id}\n";
        echo "   Status: {$latestMessage->status}\n";
        echo "   Phone: {$latestMessage->phone_number}\n";
        echo "   Created: {$latestMessage->created_at}\n";
        echo "   Sent At: " . ($latestMessage->sent_at ?: 'Not sent') . "\n";
        echo "   External ID: " . ($latestMessage->external_id ?: 'None') . "\n";
        
        if ($latestMessage->waapi_response) {
            echo "   API Response: " . json_encode($latestMessage->waapi_response) . "\n";
        }
    }
    
    // Check if messages are actually reaching the external API
    echo "\n5. API Configuration Check...\n";
    $baseUrl = config('services.unified_notification.base_url', 'https://notifications.shulesoft.africa/api');
    echo "   API Base URL: {$baseUrl}\n";
    
    // Test API connectivity
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(5)->get($baseUrl);
        echo "   API Connectivity: " . ($response->successful() ? 'OK' : 'FAILED') . "\n";
        if (!$response->successful()) {
            echo "   API Error: " . $response->status() . " - " . $response->body() . "\n";
        }
    } catch (Exception $e) {
        echo "   API Connectivity: FAILED - " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Summary ===\n";
    echo "📊 Recent message records: " . $recentMessages->count() . "\n";
    echo "📊 System message logs: " . $systemLogs->count() . "\n";
    
    $successfulMessages = $recentMessages->where('status', 'sent')->count();
    $failedMessages = $recentMessages->where('status', 'failed')->count();
    $pendingMessages = $recentMessages->where('status', 'queued')->count();
    
    echo "✅ Successful: {$successfulMessages}\n";
    echo "❌ Failed: {$failedMessages}\n";
    echo "⏳ Pending: {$pendingMessages}\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}