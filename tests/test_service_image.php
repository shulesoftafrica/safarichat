<?php
/**
 * Test WaSenderService sendImage method
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\WaSenderService;

echo "=== Testing WaSenderService::sendImage() ===\n\n";

try {
    $service = new WaSenderService();
    
    $phoneNumber = '+255714825469';
    $imagePath = 'public/images/cards.png';
    $caption = 'Here is a test image sent via WaSenderService! 📸';
    
    echo "📱 Recipient: $phoneNumber\n";
    echo "🖼️ Image: $imagePath\n";
    echo "💬 Caption: $caption\n";
    echo "📤 Sending...\n\n";
    
    $result = $service->sendImage(
        $phoneNumber,
        $imagePath,
        $caption,
        null, // instanceId  
        45    // userId - user with active instance
    );
    
    if ($result['success']) {
        echo "✅ Image sent successfully!\n";
        echo "🆔 Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        echo "📞 JID: " . ($result['jid'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['status'] ?? 'N/A') . "\n";
        echo "\n📋 Full Response:\n";
        print_r($result);
    } else {
        echo "❌ Failed to send image\n";
        print_r($result);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";
