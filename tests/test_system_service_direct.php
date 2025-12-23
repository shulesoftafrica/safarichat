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

echo "🔧 Testing SystemWhatsAppService Directly\n";
echo "==========================================\n\n";

try {
    $systemService = app(SystemWhatsAppService::class);
    
    echo "1. Service Availability Check...\n";
    if (!$systemService->isAvailable()) {
        echo "❌ SystemWhatsAppService not available\n";
        exit(1);
    }
    echo "✅ SystemWhatsAppService is available\n\n";
    
    echo "2. Testing sendGenericMessage method...\n";
    $testPhone = '+255700123123';
    $testMessage = 'Direct test message from SystemWhatsAppService';
    
    $result = $systemService->sendGenericMessage($testPhone, $testMessage, 'system_notification');
    
    if ($result) {
        echo "✅ sendGenericMessage returned: TRUE (success)\n";
    } else {
        echo "❌ sendGenericMessage returned: FALSE (failure)\n";
    }
    
    echo "\n3. Testing specific message types...\n";
    
    // Test OTP
    $otpResult = $systemService->sendGenericMessage($testPhone, 'Your OTP: 123456', 'otp_verification');
    echo "OTP Message: " . ($otpResult ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    // Test Welcome
    $welcomeResult = $systemService->sendGenericMessage($testPhone, 'Welcome to SafariChat!', 'welcome_message');
    echo "Welcome Message: " . ($welcomeResult ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    // Test Payment
    $paymentResult = $systemService->sendGenericMessage($testPhone, 'Payment due reminder', 'payment_reminder');
    echo "Payment Message: " . ($paymentResult ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    echo "\n4. Check System Instance Details...\n";
    
    $systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
    if ($systemInstance) {
        echo "System Instance Found:\n";
        echo "  ID: {$systemInstance->id}\n";
        echo "  Phone: {$systemInstance->phone_number}\n";
        echo "  Status: {$systemInstance->status}\n";
        echo "  Connect Status: {$systemInstance->connect_status}\n";
        echo "  Usage Scope: {$systemInstance->usage_scope}\n";
        echo "  Allowed Message Types: {$systemInstance->allowed_message_types}\n\n";
        
        // Test message type validation
        echo "5. Message Type Validation...\n";
        $allowedTypes = json_decode($systemInstance->allowed_message_types, true);
        echo "Allowed Types: " . implode(', ', $allowedTypes) . "\n";
        
        foreach (['otp_verification', 'welcome_message', 'payment_reminder', 'system_notification'] as $type) {
            $canSend = $systemInstance->canSendMessageType($type);
            echo "  {$type}: " . ($canSend ? "✅ ALLOWED" : "❌ NOT ALLOWED") . "\n";
        }
        
    } else {
        echo "❌ System default instance not found!\n";
    }
    
    echo "\n6. Check Recent System Message Logs...\n";
    $recentLogs = \App\Models\SystemMessageLog::orderBy('created_at', 'desc')->limit(3)->get();
    
    if ($recentLogs->count() > 0) {
        echo "Recent system message logs:\n";
        foreach ($recentLogs as $log) {
            echo "  - {$log->created_at}: {$log->message_type} to {$log->phone_number} ({$log->status})\n";
        }
    } else {
        echo "No recent system message logs found\n";
    }
    
    echo "\n✨ Direct SystemWhatsAppService Test Complete!\n";
    
} catch (Exception $e) {
    echo "❌ Test Failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}