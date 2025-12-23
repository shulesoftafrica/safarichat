<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Controller;
use App\Http\Controllers\Setup;
use App\Http\Controllers\Message;
use App\Services\SystemWhatsAppService;
use Illuminate\Support\Facades\Log;

echo "=== Testing Complete OTP Flow ===\n";

try {
    echo "\n1. Testing Setup::otp() method flow...\n";
    
    // Simulate OTP request like in Setup.php
    $testPhone = '255700123456';
    $verifyCode = '123456';
    $message = 'Hello, Your Verification Code is ' . $verifyCode;
    
    echo "   Test Phone: $testPhone\n";
    echo "   OTP Message: $message\n";
    
    // Test the Controller::sendTextMessage method
    echo "\n2. Testing Controller::sendTextMessage method...\n";
    $controller = new Controller();
    
    // This simulates the call from Setup.php line 48: 
    // $this->sendTextMessage($phone, $message, 'whatsapp','reset_pass');
    echo "   Calling sendTextMessage with parameters:\n";
    echo "   - chatId: $testPhone\n";
    echo "   - text: $message\n";
    echo "   - source: whatsapp\n";
    echo "   - instance_id: reset_pass\n";
    
    // Test the updated method
    $result = $controller->sendTextMessage($testPhone, $message, 'whatsapp', 'reset_pass');
    echo "   Result: " . json_encode($result->original) . "\n";
    
    // Test Message::send method directly
    echo "\n3. Testing Message::send method directly...\n";
    $messageController = new Message();
    
    echo "   Calling send with messageTypeHint='reset_pass'...\n";
    $directResult = $messageController->send($message, $testPhone, null, 'reset_pass');
    echo "   Direct Result: " . json_encode($directResult) . "\n";
    
    // Test message type detection
    echo "\n4. Testing message type detection...\n";
    $reflection = new ReflectionClass($messageController);
    $method = $reflection->getMethod('determineSystemMessageType');
    $method->setAccessible(true);
    
    // Test with hint 'reset_pass'
    $detectedType1 = $method->invoke($messageController, $message, null, 'reset_pass');
    echo "   Message type with hint 'reset_pass': $detectedType1\n";
    
    // Test without hint (pattern matching)
    $detectedType2 = $method->invoke($messageController, $message, null, null);
    echo "   Message type without hint (pattern matching): $detectedType2\n";
    
    // Test with different OTP messages
    echo "\n5. Testing different OTP message patterns...\n";
    $otpMessages = [
        'Your verification code is 123456',
        'OTP: 789012',
        'Please verify with code 456789',
        'Hello, Your Verification Code is 123456' // Our current message
    ];
    
    foreach ($otpMessages as $index => $otpMsg) {
        $detectedType = $method->invoke($messageController, $otpMsg, null, null);
        echo "   Message " . ($index + 1) . " ('$otpMsg'): $detectedType\n";
    }
    
    // Test SystemWhatsAppService availability
    echo "\n6. Testing SystemWhatsAppService availability...\n";
    $systemService = app(SystemWhatsAppService::class);
    $isAvailable = $systemService->isAvailable();
    echo "   System service available: " . ($isAvailable ? 'YES' : 'NO') . "\n";
    
    if ($isAvailable) {
        // Test system instance capabilities
        $systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
        if ($systemInstance) {
            echo "   System instance ID: {$systemInstance->id}\n";
            echo "   System instance phone: {$systemInstance->phone_number}\n";
            
            $canSendOtp = $systemInstance->canSendMessageType('otp_verification');
            $canSendReset = $systemInstance->canSendMessageType('password_reset');
            
            echo "   Can send OTP: " . ($canSendOtp ? 'YES' : 'NO') . "\n";
            echo "   Can send Password Reset: " . ($canSendReset ? 'YES' : 'NO') . "\n";
        }
    }
    
    echo "\n=== Test Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}