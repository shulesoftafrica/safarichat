<?php
/**
 * Test script to verify json_decode fixes
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing json_decode fixes in AiWhatsAppService...\n";

// Test 1: Check if Product tags are already cast as array
echo "\n1. Testing Product tags field casting:\n";
try {
    $product = App\Models\Product::first();
    if ($product && $product->tags) {
        $type = gettype($product->tags);
        echo "✓ Product tags field type: {$type}\n";
        if ($type === 'array') {
            echo "✓ Tags are already cast as array - no json_decode needed\n";
        } else {
            echo "✗ Tags are not cast as array - may need json_decode\n";
        }
    } else {
        echo "ℹ No products found or no tags set\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing Product tags: " . $e->getMessage() . "\n";
}

// Test 2: Check if AiSalesAgent target_user_types are cast as array
echo "\n2. Testing AiSalesAgent target_user_types field casting:\n";
try {
    $agent = App\Models\AiSalesAgent::first();
    if ($agent && $agent->target_user_types) {
        $type = gettype($agent->target_user_types);
        echo "✓ AiSalesAgent target_user_types field type: {$type}\n";
        if ($type === 'array') {
            echo "✓ Target user types are already cast as array - no json_decode needed\n";
        } else {
            echo "✗ Target user types are not cast as array - may need json_decode\n";
        }
    } else {
        echo "ℹ No agents found or no target_user_types set\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing AiSalesAgent target_user_types: " . $e->getMessage() . "\n";
}

// Test 3: Verify the AiWhatsAppService code doesn't have json_decode calls anymore
echo "\n3. Verifying code fixes:\n";
$serviceContent = file_get_contents(__DIR__ . '/app/Services/AiWhatsAppService.php');
$jsonDecodeCount = substr_count($serviceContent, 'json_decode');

if ($jsonDecodeCount === 0) {
    echo "✓ No json_decode calls found in AiWhatsAppService.php\n";
} else {
    echo "✗ Found {$jsonDecodeCount} json_decode call(s) in AiWhatsAppService.php\n";
}

echo "\n🎯 Json Decode Error Resolution Summary:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Issue: json_decode() being called on fields already cast as arrays\n";
echo "Fix 1: Removed json_decode() for \$product->tags (line 328)\n";
echo "Fix 2: Removed json_decode() for \$agent->target_user_types (line 226)\n";
echo "Reason: Laravel model casting already converts JSON to PHP arrays\n";
echo "✅ Json decode errors should now be resolved.\n";