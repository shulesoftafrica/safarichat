<?php
require_once 'bootstrap/app.php';

use App\Http\Controllers\ServiceController;
use App\Models\Product;

// Test the updated product form
echo "=== Testing Updated Product Form ===\n\n";

// Test 1: Check if we can access the products page
echo "1. Testing product form access...\n";
try {
    $serviceController = new ServiceController();
    echo "✅ Service Controller accessible\n";
} catch (Exception $e) {
    echo "❌ Error accessing Service Controller: " . $e->getMessage() . "\n";
}

// Test 2: Check existing products
echo "\n2. Checking existing products...\n";
try {
    $products = Product::all();
    echo "✅ Found " . $products->count() . " existing products\n";
    
    foreach ($products as $product) {
        echo "   - ID: {$product->id}, Name: {$product->name}, Type: {$product->product_type}\n";
    }
} catch (Exception $e) {
    echo "❌ Error fetching products: " . $e->getMessage() . "\n";
}

// Test 3: Test creating a service (not a product)
echo "\n3. Testing service creation...\n";
try {
    $serviceData = [
        'name' => 'Premium Website Development Service',
        'description' => 'Complete website development with modern design and functionality',
        'product_type' => 'service',
        'category' => 'Technology',
        'subcategory' => 'Web Development',
        'retail_price' => 1500.00,
        'wholesale_price' => 1200.00,
        'min_negotiable_price' => 1000.00,
        'service_delivery_type' => 'digital',
        'pricing_type' => 'tiered',
        'lead_time_days' => 30,
        'warranty_period' => 365,
        'availability_status' => 'available',
        'inventory_threshold' => 0,
        'weight' => 0,
        'dimensions' => null,
        'brand' => 'Safari Chat Solutions',
        'selling_points' => json_encode([
            'Modern responsive design',
            'SEO optimized',
            'E-commerce ready',
            'Mobile-first approach',
            'Free maintenance for 6 months'
        ]),
        'tags' => json_encode([]),
        'business_id' => 1,
        'created_by' => 1
    ];
    
    $service = Product::create($serviceData);
    echo "✅ Service created successfully with ID: {$service->id}\n";
    echo "   - Name: {$service->name}\n";
    echo "   - Type: {$service->product_type}\n";
    echo "   - Price: TSH " . number_format($service->retail_price, 2) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error creating service: " . $e->getMessage() . "\n";
}

// Test 4: Test creating a tangible product
echo "\n4. Testing tangible product creation...\n";
try {
    $productData = [
        'name' => 'Smart IoT Temperature Sensor',
        'description' => 'WiFi-enabled temperature and humidity monitoring device',
        'product_type' => 'tangible_product',
        'sku' => 'IOT-TEMP-001',
        'category' => 'Electronics',
        'subcategory' => 'IoT Devices',
        'retail_price' => 89.99,
        'wholesale_price' => 65.00,
        'min_negotiable_price' => 50.00,
        'lead_time_days' => 7,
        'warranty_period' => 365,
        'availability_status' => 'available',
        'inventory_threshold' => 10,
        'weight' => 0.2,
        'dimensions' => '5cm x 5cm x 2cm',
        'brand' => 'TechSense',
        'selling_points' => json_encode([
            'Real-time monitoring',
            'Mobile app integration',
            'Long battery life (2 years)',
            'Waterproof design',
            'Easy installation'
        ]),
        'tags' => json_encode(['IoT', 'Smart Home', 'Monitoring', 'WiFi']),
        'business_id' => 1,
        'created_by' => 1
    ];
    
    $product = Product::create($productData);
    echo "✅ Product created successfully with ID: {$product->id}\n";
    echo "   - Name: {$product->name}\n";
    echo "   - Type: {$product->product_type}\n";
    echo "   - SKU: {$product->sku}\n";
    echo "   - Price: TSH " . number_format($product->retail_price, 2) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error creating product: " . $e->getMessage() . "\n";
}

// Test 5: Final product count
echo "\n5. Final product/service count...\n";
try {
    $totalProducts = Product::count();
    $services = Product::where('product_type', 'service')->count();
    $products = Product::where('product_type', 'tangible_product')->count();
    
    echo "✅ Total items: {$totalProducts}\n";
    echo "   - Services: {$services}\n";
    echo "   - Tangible Products: {$products}\n";
} catch (Exception $e) {
    echo "❌ Error counting products: " . $e->getMessage() . "\n";
}

echo "\n=== Product Form Test Complete ===\n";
?>