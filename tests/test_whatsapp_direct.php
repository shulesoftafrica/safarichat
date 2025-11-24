<?php

use App\Http\Controllers\Message;

echo "Initializing WhatsApp message test...\n";

try {
    // Create Message instance
    $messageController = new Message();
    
    // Target phone number
    $phoneNumber = '255714825469';
    $chatId = $phoneNumber . '@c.us';
    
    // Test message
    $testMessage = 'Hello! This is a test message from SafariChat system. Time: ' . date('Y-m-d H:i:s');
    
    echo "Sending WhatsApp message...\n";
    echo "Target: +{$phoneNumber}\n";
    echo "Message: {$testMessage}\n";
    
    // Use the same method that's used in the Kernel schedule
    $result = $messageController->storeMessage($chatId, $testMessage, 'whatsapp');
    
    if ($result) {
        echo "✅ Message stored and queued successfully!\n";
        echo "📱 Message ID: " . $result->id . "\n";
        echo "📝 Check the outgoing_messages table and queue processing for delivery status\n";
    } else {
        echo "❌ Failed to store message\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed!\n";

?>