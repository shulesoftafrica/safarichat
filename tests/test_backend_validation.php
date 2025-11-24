<?php
// Simple test to verify service creation with updated validation
require_once 'bootstrap/app.php';

use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

echo "=== Backend Service Validation Test ===\n\n";

// Test 1: Service with required fields only
echo "1. Testing service validation with minimal required fields...\n";

$serviceData = [
    'product_type' => 'service',
    'name' => 'Test Website Development Service',
    'description' => 'Professional website development service',
    'category' => 'Technology',
    'service_delivery_type' => 'digital',
    'pricing_type' => 'one_time'
];

try {
    // Create a mock request
    $request = Request::create('/test', 'POST', $serviceData);
    $storeRequest = new StoreProductRequest();
    $storeRequest->replace($serviceData);
    
    // Get validation rules
    $rules = $storeRequest->rules();
    $messages = $storeRequest->messages();
    
    echo "✓ Service validation rules generated successfully\n";
    echo "   - SKU is optional: " . (strpos($rules['sku'], 'nullable') !== false ? 'Yes' : 'No') . "\n";
    echo "   - Retail price is optional: " . (strpos($rules['retail_price'], 'nullable') !== false ? 'Yes' : 'No') . "\n";
    echo "   - Service delivery type required: " . (strpos($rules['service_delivery_type'], 'required') !== false ? 'Yes' : 'No') . "\n";
    
    // Test validation
    $validator = Validator::make($serviceData, $rules, $messages);
    
    if ($validator->passes()) {
        echo "✓ Service validation passed\n";
    } else {
        echo "✗ Service validation failed:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "   - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error testing service validation: " . $e->getMessage() . "\n";
}

// Test 2: Product with required fields
echo "\n2. Testing tangible product validation with required fields...\n";

$productData = [
    'product_type' => 'tangible',
    'name' => 'Test Smart Phone',
    'description' => 'Latest smartphone technology',
    'category' => 'Electronics',
    'sku' => 'SP-001',
    'retail_price' => 999.99,
    'wholesale_price' => 799.99,
    'status' => 'active'
];

try {
    $request = Request::create('/test', 'POST', $productData);
    $storeRequest = new StoreProductRequest();
    $storeRequest->replace($productData);
    
    $rules = $storeRequest->rules();
    $validator = Validator::make($productData, $rules);
    
    echo "✓ Product validation rules generated successfully\n";
    echo "   - SKU is required: " . (strpos($rules['sku'], 'required') !== false ? 'Yes' : 'No') . "\n";
    echo "   - Retail price is required: " . (strpos($rules['retail_price'], 'required') !== false ? 'Yes' : 'No') . "\n";
    
    if ($validator->passes()) {
        echo "✓ Product validation passed\n";
    } else {
        echo "✗ Product validation failed:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "   - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error testing product validation: " . $e->getMessage() . "\n";
}

// Test 3: Service missing required pricing fields (should pass)
echo "\n3. Testing service without pricing fields (should pass)...\n";

$serviceWithoutPricing = [
    'product_type' => 'service',
    'name' => 'Consulting Service',
    'description' => 'Business consulting',
    'category' => 'Consulting',
    'service_delivery_type' => 'consultation',
    'pricing_type' => 'per_hour'
    // Note: No sku, retail_price, wholesale_price
];

try {
    $storeRequest = new StoreProductRequest();
    $storeRequest->replace($serviceWithoutPricing);
    
    $rules = $storeRequest->rules();
    $validator = Validator::make($serviceWithoutPricing, $rules);
    
    if ($validator->passes()) {
        echo "✓ Service without pricing fields validation passed (as expected)\n";
    } else {
        echo "✗ Service without pricing fields validation failed:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "   - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Backend Validation Test Complete ===\n";
?>