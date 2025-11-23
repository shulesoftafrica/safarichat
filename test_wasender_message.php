<?php

require_once 'vendor/autoload.php';

use App\Services\WaSenderService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel for console usage
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Starting WaSender test message...\n";
    
    // Initialize WaSender service
    $waSenderService = new WaSenderService();
    
    // Test phone number
    $phoneNumber = '255714825469';
    $message = 'Test message from WaSender Service - ' . date('Y-m-d H:i:s');
    
    echo "Sending message to: {$phoneNumber}\n";
    echo "Message: {$message}\n";
    
    // Send the test message
    $result = $waSenderService->sendTextMessage(
        $phoneNumber,
        $message,
        null, // Use default instance
        1     // Use user ID 1 for test
    );
    
    echo "\n=== RESULT ===\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($result['success']) {
        echo "\n✅ Message sent successfully!\n";
        echo "Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
    } else {
        echo "\n❌ Message failed to send.\n";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Exception occurred:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTest completed.\n";