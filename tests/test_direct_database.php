<?php

// Quick test to verify the API is working
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING API ENDPOINT DIRECTLY ===\n\n";

// Test if we can create an attachment record directly
use App\Models\Product;
use App\Models\ProductAttachment;

$product = Product::first();
if (!$product) {
    echo "ERROR: No products found\n";
    exit(1);
}

echo "Testing with product: {$product->name} (ID: {$product->id})\n\n";

// Create a test attachment record directly
try {
    $attachment = $product->attachments()->create([
        'attachment_type' => 'technical_spec',
        'file_path' => 'test/test-file.txt',
        'original_filename' => 'direct-test.txt',
        'mime_type' => 'text/plain',
        'file_size' => 100,
        'title' => 'Direct Test File',
        'description' => 'Testing direct database insertion',
        'is_public' => true,
        'display_order' => 1,
        'processing_status' => 'completed',
        'is_processed' => true
    ]);
    
    echo "SUCCESS: Attachment created with ID: {$attachment->id}\n";
    echo "Title: {$attachment->title}\n";
    echo "File Path: {$attachment->file_path}\n\n";
    
    // Now test if we can retrieve it
    $attachments = $product->fresh()->attachments;
    echo "Product now has {$attachments->count()} attachment(s):\n";
    foreach ($attachments as $att) {
        echo "  - {$att->title} ({$att->original_filename})\n";
    }
    
} catch (Exception $e) {
    echo "ERROR creating attachment: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}