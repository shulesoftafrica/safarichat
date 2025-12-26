<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get latest product attachments
$attachments = DB::select('SELECT pa.*, p.name as product_name FROM product_attachments pa LEFT JOIN products p ON pa.product_id = p.id ORDER BY pa.id DESC LIMIT 10');

echo "Recent Product Attachments:\n";
echo "============================\n";
foreach($attachments as $att) {
    echo "ID: {$att->id} | Product: {$att->product_name} ({$att->product_id}) | File: {$att->original_filename} | Path: {$att->file_path} | Created: {$att->created_at}\n";
}

// Check if files exist on disk
echo "\n\nFile System Check:\n";
echo "==================\n";
foreach($attachments as $att) {
    $fullPath = storage_path('app/public/' . $att->file_path);
    $exists = file_exists($fullPath) ? 'EXISTS' : 'MISSING';
    echo "{$att->original_filename}: {$exists} at {$fullPath}\n";
}

// Test the relationship
echo "\n\nRelationship Test:\n";
echo "==================\n";
$product = App\Models\Product::with(['attachments'])->first();
if ($product) {
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "Attachments count: " . $product->attachments->count() . "\n";
    foreach ($product->attachments as $attachment) {
        echo "  - {$attachment->title} ({$attachment->original_filename})\n";
    }
} else {
    echo "No products found\n";
}