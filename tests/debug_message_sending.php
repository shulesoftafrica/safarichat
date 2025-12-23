<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Controller;
use App\Services\SystemWhatsAppService;

echo "=== Debugging Message Sending Issue ===\n";

try {
    // Clear logs for clean test
    echo "\n1. Clearing logs for clean test...\n";
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        file_put_contents($logPath, '');
        echo "   ✅ Logs cleared\n";
    }
    
    // Test 1: Check system configuration
    echo "\n2. Checking System Configuration...\n";
    $systemService = app(\App\Services\SystemWhatsAppService::class);
    $isAvailable = $systemService->isAvailable();
    echo "   System service available: " . ($isAvailable ? 'YES' : 'NO') . "\n";
    
    if (!$isAvailable) {
        echo "   ❌ System service not available - this could be the issue!\n";
        return;
    }
    
    // Test 2: Check system instance
    $systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
    if ($systemInstance) {
        echo "   System instance ID: {$systemInstance->id}\n";
        echo "   System instance UUID: {$systemInstance->uuid}\n";
        echo "   System instance phone: {$systemInstance->phone_number}\n";
        echo "   Status: {$systemInstance->status}\n";
    } else {
        echo "   ❌ No system instance found!\n";
        return;
    }
    
    // Test 3: Check UnifiedNotificationService configuration
    echo "\n3. Checking UnifiedNotificationService Configuration...\n";
    $unifiedService = app(\App\Services\UnifiedNotificationService::class);
    
    // Check base URL and token
    $baseUrl = config('services.unified_notification.base_url', 'https://notifications.shulesoft.africa/api');
    $token = config('services.unified_notification.token') 
        ?? config('notifications.unified_api.bearer_token')
        ?? env('NOTIFICATION_API_TOKEN')
        ?? env('UNIFIED_NOTIFICATION_TOKEN');
    
    echo "   Base URL: {$baseUrl}\n";
    echo "   Token configured: " . (!empty($token) ? 'YES' : 'NO') . "\n";
    echo "   Token length: " . (strlen($token ?: '')) . "\n";
    
    if (empty($token)) {
        echo "   ❌ No API token configured - this could be the issue!\n";
        echo "   💡 Check env variables: NOTIFICATION_API_TOKEN or UNIFIED_NOTIFICATION_TOKEN\n";
    }
    
    // Test 4: Try sending a message
    echo "\n4. Testing Message Sending...\n";
    $testPhone = '714825469';
    $message = 'Test message: ' . time();
    
    echo "   Phone: $testPhone\n";
    echo "   Message: $message\n";
    
    // Send via Controller (Setup.php flow)
    $controller = new Controller();
    $result = $controller->sendTextMessage($testPhone, $message, 'whatsapp', 'otp');
    echo "   Controller result: " . json_encode($result->original) . "\n";
    
    // Test 5: Check if job was queued
    echo "\n5. Checking Queue Status...\n";
    $pendingJobs = \DB::table('jobs')->count();
    $failedJobs = \DB::table('failed_jobs')->count();
    
    echo "   Pending jobs: $pendingJobs\n";
    echo "   Failed jobs: $failedJobs\n";
    
    if ($pendingJobs == 0 && $failedJobs == 0) {
        echo "   ⚠️  No jobs queued - message might have been processed immediately or failed to queue\n";
    }
    
    // Test 6: Check recent logs
    echo "\n6. Checking Recent Logs...\n";
    sleep(2); // Wait a bit for logs to be written
    
    if (file_exists($logPath) && filesize($logPath) > 0) {
        $logContent = file_get_contents($logPath);
        $lines = explode("\n", array_filter(explode("\n", $logContent)));
        
        echo "   Recent log entries:\n";
        foreach (array_slice($lines, -10) as $line) {
            if (!empty(trim($line))) {
                echo "   " . trim($line) . "\n";
            }
        }
    } else {
        echo "   ❌ No log entries found - logging might be disabled\n";
    }
    
    // Test 7: Check OutgoingMessage records
    echo "\n7. Checking Message Records...\n";
    $recentMessages = \App\Models\OutgoingMessage::orderBy('created_at', 'desc')->limit(3)->get();
    
    if ($recentMessages->count() > 0) {
        foreach ($recentMessages as $msg) {
            echo "   ID: {$msg->id}, Status: {$msg->status}, Phone: {$msg->phone_number}, Created: {$msg->created_at}\n";
        }
    } else {
        echo "   ❌ No outgoing message records found\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}