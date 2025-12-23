<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Controller;
use App\Http\Controllers\Message;

echo "=== Testing Corrected OTP Flow ===\n";

try {
    $testPhone = '255700123456';
    $verifyCode = '123456';
    $message = 'Hello, Your Verification Code is ' . $verifyCode;
    
    echo "\n1. Testing with corrected 'otp' parameter...\n";
    
    $controller = new Controller();
    $result = $controller->sendTextMessage($testPhone, $message, 'whatsapp', 'otp');
    echo "   Result: " . json_encode($result->original) . "\n";
    
    echo "\n2. Testing Message::send method with 'otp' hint...\n";
    $messageController = new Message();
    $directResult = $messageController->send($message, $testPhone, null, 'otp');
    echo "   Direct Result: " . json_encode($directResult) . "\n";
    
    echo "\n3. Testing message type detection with different hints...\n";
    $reflection = new ReflectionClass($messageController);
    $method = $reflection->getMethod('determineSystemMessageType');
    $method->setAccessible(true);
    
    $testCases = [
        'otp' => 'otp_verification',
        'reset_pass' => 'password_reset',
        'welcome' => 'welcome_message',
        'payment' => 'payment_reminder',
        'system_notification' => 'system_notification',
        null => 'auto-detected'
    ];
    
    foreach ($testCases as $hint => $expected) {
        $detected = $method->invoke($messageController, $message, null, $hint);
        $status = ($hint === null) ? "(auto-detected: $detected)" : 
                 ($detected === $expected ? "✅" : "❌ Expected: $expected, Got: $detected");
        echo "   Hint: " . ($hint ?? 'null') . " → $detected $status\n";
    }
    
    echo "\n=== Test Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}