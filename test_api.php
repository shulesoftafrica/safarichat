<?php
// Simple API test file
require_once 'vendor/autoload.php';

use App\Models\Product;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test database connection and products
try {
    echo "Testing database connection...\n";
    $productCount = Product::count();
    echo "Product count: " . $productCount . "\n";
    
    if ($productCount > 0) {
        $firstProduct = Product::first();
        echo "First product ID: " . $firstProduct->id . "\n";
        echo "First product name: " . $firstProduct->name . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
