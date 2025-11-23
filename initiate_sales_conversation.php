<?php

require_once 'vendor/autoload.php';

// Load environment variables
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

// Test WhatsApp message sending
echo "🚀 Testing Safari Chat AI Sales Conversation\n";
echo "==========================================\n\n";

// Prepare sales message
$phone = "+255714825469";
$message = "🎯 *Safari Chat AI Demo*

Hi! I'm the AI assistant for Safari Chat - your complete WhatsApp business automation solution.

🤖 **What Safari Chat offers:**
• AI-powered customer service (24/7)
• Automated lead management  
• Smart conversation routing
• Real-time analytics & reporting
• 40% cost reduction in customer service

💰 **Special Pricing:**
• Starter: $99/month 
• Professional: $299/month
• Enterprise: $999/month

🎁 **Limited Offer:** 30-day free trial + setup included!

Would you like to learn more about how Safari Chat can transform your business communications?

Reply with:
• 'DEMO' for a live demonstration
• 'PRICING' for detailed pricing info
• 'FEATURES' for technical specifications

Ready to boost your business? Let's chat! 🚀";

echo "📱 Target Phone: $phone\n";
echo "💬 Message Preview:\n";
echo str_repeat("-", 50) . "\n";
echo $message . "\n";
echo str_repeat("-", 50) . "\n\n";

// Test API configuration
$apiKey = getenv('OPENAI_API_KEY');
$waApiKey = getenv('WASENDER_API_KEY'); 
$waInstance = getenv('WASENDER_INSTANCE_ID');

echo "🔧 Configuration Check:\n";
echo "OpenAI API: " . ($apiKey ? "✅ Configured" : "❌ Missing") . "\n";
echo "WaSender API: " . ($waApiKey ? "✅ Configured" : "❌ Missing") . "\n";
echo "WaSender Instance: " . ($waInstance ? "✅ Configured" : "❌ Missing") . "\n\n";

if ($waApiKey && $waInstance) {
    echo "🚀 Sending sales message...\n";
    
    $url = "https://api.wasender.io/v1/send";
    $data = [
        'number' => $phone,
        'type' => 'text',
        'message' => $message,
        'instance_id' => $waInstance
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $waApiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        echo "✅ Message sent successfully!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Failed to send message\n";
        echo "HTTP Code: $httpCode\n";
        echo "Response: $response\n";
    }
} else {
    echo "⚠️ WaSender configuration missing. Message not sent.\n";
    echo "Please configure WASENDER_API_KEY and WASENDER_INSTANCE_ID in .env file\n";
}

echo "\n🎯 Sales Conversation Initiated!\n";
echo "The AI will now handle incoming responses with RAG-enhanced knowledge.\n";