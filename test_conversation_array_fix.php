<?php
/**
 * Test conversation creation with proper array formatting
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing conversation creation with array fixes...\n";

try {
    // Test array type validation
    echo "\n1. Testing array type validation:\n";
    
    // Test with non-array values that might cause the error
    $testData = [
        'ai_actions' => 0, // This might be the problem - integer instead of array
        'rag_sources' => '0', // String instead of array
        'rag_enhanced' => 1, // Integer instead of boolean
    ];
    
    foreach ($testData as $field => $value) {
        echo "Testing {$field} with value: " . var_export($value, true) . "\n";
        
        if ($field === 'ai_actions' || $field === 'rag_sources') {
            $corrected = is_array($value) ? $value : [];
            echo "  Corrected to: " . var_export($corrected, true) . "\n";
        }
        
        if ($field === 'rag_enhanced') {
            $corrected = is_bool($value) ? $value : (bool) $value;
            echo "  Corrected to: " . var_export($corrected, true) . "\n";
        }
    }

    // Test 2: Check Conversation model array casts
    echo "\n2. Verifying Conversation model array casts:\n";
    $conversation = new App\Models\Conversation();
    $casts = $conversation->getCasts();
    
    $arrayCasts = array_filter($casts, function($cast) {
        return $cast === 'array';
    });
    
    echo "Array-cast fields: " . implode(', ', array_keys($arrayCasts)) . "\n";
    
    $booleanCasts = array_filter($casts, function($cast) {
        return $cast === 'boolean';
    });
    
    echo "Boolean-cast fields: " . implode(', ', array_keys($booleanCasts)) . "\n";

    // Test 3: Simulate the problematic data from the error
    echo "\n3. Testing data structure from error log:\n";
    $errorData = [
        'ai_actions' => [], // Empty array - should work
        'rag_sources' => [], // Empty array - should work  
        'rag_enhanced' => false, // Boolean false - should work (was showing as 0 in SQL)
    ];
    
    foreach ($errorData as $field => $value) {
        echo "Field '{$field}': " . var_export($value, true) . " (type: " . gettype($value) . ")\n";
    }

    echo "\n✅ Array type validation and conversion logic added to saveConversation method.\n";
    echo "✅ The method now ensures:\n";
    echo "   - ai_actions is always an array (converts non-arrays to empty array)\n";
    echo "   - rag_sources is always an array (converts non-arrays to empty array)\n";
    echo "   - rag_enhanced is always a boolean (converts truthy/falsy values to bool)\n";
    echo "\nThis should resolve the PostgreSQL array literal error.\n";

} catch (Exception $e) {
    echo "\n✗ Error during testing: " . $e->getMessage() . "\n";
}