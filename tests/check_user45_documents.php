<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check products for user_id 45
    $products = DB::table('products')->where('user_id', 45)->get();
    
    echo "=== PRODUCTS FOR USER_ID 45 ===\n";
    echo "Total products found: " . $products->count() . "\n\n";
    
    foreach ($products as $product) {
        echo "Product ID: {$product->id}\n";
        echo "Name: {$product->name}\n";
        echo "Type: " . ($product->product_type ?? 'N/A') . "\n";
        echo "Image Path: " . ($product->image_path ?? 'NULL') . "\n";
        echo "Attachment Path: " . ($product->attachment_path ?? 'NULL') . "\n";
        echo "Campaign Attachment: " . ($product->campaign_attachment_path ?? 'NULL') . "\n";
        echo "Image Original Name: " . ($product->image_original_name ?? 'NULL') . "\n";
        echo "Attachment Original Name: " . ($product->attachment_original_name ?? 'NULL') . "\n";
        echo "Created: " . ($product->created_at ?? 'N/A') . "\n";
        echo "Updated: " . ($product->updated_at ?? 'N/A') . "\n";
        echo "-----------------------------------\n";
    }
    
    // Check if there are any file paths in storage
    $documentsWithFiles = $products->filter(function($product) {
        return !empty($product->image_path) || 
               !empty($product->attachment_path) || 
               !empty($product->campaign_attachment_path);
    });
    
    echo "\nProducts with files: " . $documentsWithFiles->count() . "\n";
    
    if ($documentsWithFiles->count() > 0) {
        echo "Files found:\n";
        foreach ($documentsWithFiles as $product) {
            if (!empty($product->image_path)) {
                echo "- Image: " . $product->image_path . "\n";
            }
            if (!empty($product->attachment_path)) {
                echo "- Attachment: " . $product->attachment_path . "\n";
            }
            if (!empty($product->campaign_attachment_path)) {
                echo "- Campaign Attachment: " . $product->campaign_attachment_path . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}