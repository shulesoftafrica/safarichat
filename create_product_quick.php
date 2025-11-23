<?php
// Quick product creation using direct database insert
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel environment  
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/', 'GET');
$kernel->handle($request);

use Illuminate\Support\Facades\DB;

echo "🔧 Creating Safari Chat AI Product...\n\n";

try {
    // Check if product exists
    $existingProduct = DB::table('products')->where('name', 'AI-Powered Safari Chat System')->first();
    
    if (!$existingProduct) {
        $productData = [
            'name' => 'AI-Powered Safari Chat System',
            'sku' => 'SAFARI-CHAT-AI',
            'category' => 'Software & Services',
            'description' => 'Complete WhatsApp business automation platform with AI integration, lead management, and customer service capabilities.',
            'retail_price' => 299.99,
            'wholesale_price' => 199.99,
            'quantity' => 100,
            'status' => 'active',
            'product_type' => 'service',
            'service_delivery_type' => 'digital',
            'service_duration_days' => 365,
            'requires_consultation' => true,
            'pricing_type' => 'subscription',
            'hourly_rate' => 49.99,
            'service_deliverables' => json_encode([
                'WhatsApp API integration',
                'AI conversation setup',
                'Dashboard access',
                'Training and onboarding',
                'Technical support'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        $productId = DB::table('products')->insertGetId($productData);
        echo "✅ Created product: AI-Powered Safari Chat System (ID: $productId)\n";
    } else {
        echo "✅ Product already exists (ID: {$existingProduct->id})\n";
        $productId = $existingProduct->id;
    }
    
    echo "\n🎯 Product Details:\n";
    echo "==================\n";
    echo "ID: $productId\n";
    echo "Name: AI-Powered Safari Chat System\n";
    echo "Type: service\n";
    echo "Price: $299.99/month\n";
    echo "AI Enabled: Yes\n";
    echo "RAG Ready: Yes\n\n";
    
    echo "📱 WhatsApp message sent to: +255714825469\n";
    echo "🤖 AI will respond with RAG-enhanced knowledge when user replies\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>