<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\Message;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel for console usage
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

try {
    echo "Starting WaSender test via Message Controller...\n";
    
    // Initialize Message controller
    $messageController = new Message();
    
    // Test phone number and message
    $phoneNumber = '255714825469';
    $message = 'Hello from WaSender! Test message sent at ' . date('Y-m-d H:i:s');
    $userId = 1; // Test user ID
    
    echo "Sending message to: +{$phoneNumber}\n";
    echo "Message: {$message}\n";
    echo "User ID: {$userId}\n\n";
    
    // Send the test message using the refactored send method
    $result = $messageController->send($message, $phoneNumber, $userId);
    
    echo "=== RESULT ===\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($result['success']) && $result['success']) {
        echo "✅ Message sent successfully!\n";
        if (isset($result['message_id'])) {
            echo "Message ID: " . $result['message_id'] . "\n";
        }
    } else {
        echo "❌ Message failed to send.\n";
        if (isset($result['message'])) {
            echo "Error: " . $result['message'] . "\n";
        }
        if (isset($result['error_code'])) {
            echo "Error Code: " . $result['error_code'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ Exception occurred:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Print stack trace for debugging
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";