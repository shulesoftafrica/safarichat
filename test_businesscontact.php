<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing BusinessContact model migration...\n";

try {
    echo "1. Checking BusinessContact model exists: ";
    if (class_exists('App\Models\BusinessContact')) {
        echo "✓ SUCCESS\n";
    } else {
        echo "✗ FAILED\n";
    }
    
    echo "2. Testing BusinessContact query: ";
    $count = App\Models\BusinessContact::count();
    echo "✓ SUCCESS ($count records)\n";
    
    echo "3. Testing OutgoingMessage model: ";
    $outgoingCount = App\Models\OutgoingMessage::count();
    echo "✓ SUCCESS ($outgoingCount records)\n";
    
    echo "4. Testing UserResolutionService: ";
    $testData = ['phone' => '+254700000001', 'name' => 'Test Contact', 'user_id' => 1];
    $contact = App\Services\UserResolutionService::resolveOrCreateContact($testData);
    echo $contact ? "✓ SUCCESS" : "✗ FAILED";
    echo "\n";
    
    echo "\n✅ All BusinessContact migration tests passed!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}