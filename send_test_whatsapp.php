<?php
// Test WhatsApp message sending script
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request instance
$request = Illuminate\Http\Request::create('/', 'GET');
$kernel->handle($request);

use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Log;

echo "Testing WhatsApp message send to +255714825469...\n";

try {
    $phoneNumber = '255714825469'; // Target phone number
    $message = '🎯 *Safari Chat AI Demo*

Hi! I\'m the AI assistant for Safari Chat - your complete WhatsApp business automation solution.

🤖 *What Safari Chat offers:*
• AI-powered customer service (24/7)
• Automated lead management  
• Smart conversation routing
• Real-time analytics & reporting
• 40% cost reduction in customer service

💰 *Special Pricing:*
• Starter: $99/month 
• Professional: $299/month
• Enterprise: $999/month

🎁 *Limited Offer:* 30-day free trial + setup included!

Would you like to learn more about how Safari Chat can transform your business communications?

Reply with:
• \'DEMO\' for a live demonstration
• \'PRICING\' for detailed pricing info
• \'FEATURES\' for technical specifications

Ready to boost your business? Let\'s chat! 🚀';
    $source = 'sales_ai';
    $userId = 1; // Default user ID
    
    // Dispatch the job to send WhatsApp message
    SendWhatsAppMessage::dispatch($message, $phoneNumber, $source, $userId);
    
    echo "✅ Safari Chat AI sales message dispatched successfully!\n";
    echo "📱 Target: +{$phoneNumber}\n";
    echo "🎯 Sales conversation initiated with RAG-enhanced AI support\n";
    echo "📝 Check the queue processing and logs for delivery status\n";
    
} catch (Exception $e) {
    echo "❌ Error sending WhatsApp message: " . $e->getMessage() . "\n";
    echo "📝 Error details: " . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo "\nDone!\n";
?>