<?php

// Direct database product creation and WhatsApp message test
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "laravel";

try {
    echo "🔧 Creating Sample Product and Sending Sales Message...\n\n";
    
    // Connect to MySQL database
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if product already exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->execute(['AI-Powered Safari Chat System']);
    $existingProduct = $stmt->fetch();
    
    if (!$existingProduct) {
        // Create sample product
        $stmt = $pdo->prepare("
            INSERT INTO products (
                name, description, price, quantity, product_type, 
                service_delivery_type, service_duration_days, service_pricing_type, 
                hourly_rate, requires_consultation, ai_prompt, ai_enabled, selling_points,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $sellingPoints = json_encode([
            'Automated WhatsApp customer service',
            'AI-powered lead qualification',
            'Multi-channel conversation management', 
            'Real-time analytics and reporting',
            '24/7 customer support automation',
            'CRM integration capabilities'
        ]);
        
        $aiPrompt = 'You are a sales expert for Safari Chat, an AI-powered WhatsApp business automation platform. Highlight features like automated customer service, lead management, AI conversations, and business growth capabilities.';
        
        $stmt->execute([
            'AI-Powered Safari Chat System',
            'Complete WhatsApp business automation platform with AI integration, lead management, and customer service capabilities.',
            299.99,
            100,
            'service',
            'digital',
            365,
            'subscription',
            49.99,
            1,
            $aiPrompt,
            1,
            $sellingPoints
        ]);
        
        $productId = $pdo->lastInsertId();
        echo "✅ Created product: AI-Powered Safari Chat System (ID: $productId)\n";
    } else {
        $productId = $existingProduct['id'];
        echo "✅ Product already exists (ID: $productId)\n";
    }
    
    // Send WhatsApp message using WaSender API
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
    
    echo "📱 Sending message to: $phone\n";
    echo "🔧 Using WaSender Instance: $instanceId\n\n";
    
    // Prepare API request
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
    
    echo "🚀 API Response:\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "❌ cURL Error: $error\n";
    } else {
        if ($httpCode == 200 || $httpCode == 201) {
            echo "✅ Message sent successfully!\n";
            $result = json_decode($response, true);
            if ($result) {
                echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "Response: $response\n";
            }
        } else {
            echo "❌ Failed to send message\n";
            echo "Response: $response\n";
        }
    }
    
    // Log the outgoing message attempt
    try {
        $stmt = $pdo->prepare("
            INSERT INTO outgoing_messages (
                chat_id, message, status, created_at, updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $phone,
            $message,
            ($httpCode == 200 || $httpCode == 201) ? 'sent' : 'failed'
        ]);
        
        echo "✅ Message logged in database\n";
    } catch (Exception $e) {
        echo "⚠️ Could not log message: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎯 Sales Conversation Initiated!\n";
    echo "📊 Product ID: $productId\n";
    echo "📱 Target: $phone\n";
    echo "🤖 AI will handle responses with RAG-enhanced knowledge\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}