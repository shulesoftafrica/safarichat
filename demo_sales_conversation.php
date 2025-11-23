<?php

// Create sample product and send WhatsApp message using Laravel components

echo "🚀 Safari Chat RAG System Test\n";
echo "===============================\n\n";

// Use exec to run Laravel commands
echo "1. Creating sample product...\n";

$createProductCommand = 'php artisan tinker --execute="
use App\Models\Product;

$product = Product::create([
    \'name\' => \'AI-Powered Safari Chat System\',
    \'description\' => \'Complete WhatsApp business automation platform with AI integration, lead management, and customer service capabilities.\',
    \'price\' => 299.99,
    \'quantity\' => 100,
    \'product_type\' => \'service\',
    \'service_delivery_type\' => \'digital\',
    \'service_duration_days\' => 365,
    \'service_pricing_type\' => \'subscription\',
    \'hourly_rate\' => 49.99,
    \'requires_consultation\' => true,
    \'ai_prompt\' => \'You are a sales expert for Safari Chat, an AI-powered WhatsApp business automation platform. Highlight features like automated customer service, lead management, AI conversations, and business growth capabilities.\',
    \'ai_enabled\' => true,
    \'selling_points\' => json_encode([
        \'Automated WhatsApp customer service\',
        \'AI-powered lead qualification\',
        \'Multi-channel conversation management\', 
        \'Real-time analytics and reporting\',
        \'24/7 customer support automation\',
        \'CRM integration capabilities\'
    ])
]);

echo \'Product created with ID: \' . $product->id;
"';

$result = shell_exec($createProductCommand);
echo "✅ Product creation result: " . trim($result) . "\n\n";

// Now send WhatsApp message
echo "2. Sending WhatsApp sales message...\n";

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
• Starter: \$99/month 
• Professional: \$299/month
• Enterprise: \$999/month

🎁 **Limited Offer:** 30-day free trial + setup included!

Would you like to learn more about how Safari Chat can transform your business communications?

Reply with:
• 'DEMO' for a live demonstration
• 'PRICING' for detailed pricing info
• 'FEATURES' for technical specifications

Ready to boost your business? Let's chat! 🚀";

// WaSender API configuration
$apiKey = "de042e1a46b394de63bed34c5b2d9c55108db5061b075b29ce9225be30d7cca2";
$instanceId = "1b3e9320-a8b5-4a38-9b31-2c80207b740d";
$baseUrl = "https://wasenderapi.com/api";

echo "📱 Target: $phone\n";
echo "🔧 Instance: $instanceId\n\n";

// Send via WaSender API
$url = "$baseUrl/send";
$data = [
    'number' => $phone,
    'type' => 'text', 
    'message' => $message,
    'instance_id' => $instanceId
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "🚀 Sending message...\n";
echo "HTTP Response Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
} else if ($httpCode == 200 || $httpCode == 201) {
    echo "✅ Message sent successfully!\n";
    $result = json_decode($response, true);
    if ($result && isset($result['status'])) {
        echo "Status: " . $result['status'] . "\n";
        if (isset($result['message_id'])) {
            echo "Message ID: " . $result['message_id'] . "\n";
        }
    }
} else {
    echo "❌ Failed to send message\n";
    echo "Response: $response\n";
}

echo "\n🎯 Sales Conversation Ready!\n";
echo "The AI will now handle incoming responses using:\n";
echo "• RAG-enhanced product knowledge\n";
echo "• Automated lead qualification\n";
echo "• Context-aware conversation flow\n";
echo "• Multi-language support\n\n";

echo "💬 When the user responds, the system will:\n";
echo "1. Process incoming message\n";
echo "2. Search document vectors for relevant context\n";
echo "3. Generate AI response with product knowledge\n";
echo "4. Track conversation and lead status\n";

?>