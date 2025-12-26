<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Http\Controllers\Api\ProductAttachmentController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

echo "=== TESTING PRODUCT ATTACHMENT CONTROLLER ===\n\n";

// Get a test product
$product = Product::first();
if (!$product) {
    echo "ERROR: No products found in database\n";
    exit(1);
}

echo "Test Product: {$product->name} (ID: {$product->id})\n\n";

// Create a test file
$testContent = "This is a test document for RAG processing.\nIt contains some sample content to test the upload functionality.\n\nThis should be processed by the RAG system.";
$tempPath = storage_path('app/temp_test.txt');
file_put_contents($tempPath, $testContent);

// Create an UploadedFile instance
$uploadedFile = new UploadedFile(
    $tempPath,
    'test-document.txt',
    'text/plain',
    null,
    true // Mark as test file
);

// Create a request with the file - simulate form data properly
$request = Request::create('/api/products/8/attachments', 'POST', [
    'attachment_types' => ['technical_spec'],
    'titles' => ['Test Document'], 
    'descriptions' => ['Test document for RAG processing'],
    'is_public' => [false],
    'process_with_rag' => false  // Disable RAG processing for now
], [], [
    'files' => [$uploadedFile]
]);

echo "Request data being sent:\n";
echo "Files: " . count($request->file('files')) . " files\n";
echo "attachment_types: " . print_r($request->input('attachment_types'), true) . "\n";
echo "All input data: " . print_r($request->all(), true) . "\n\n";

try {
    echo "Creating attachment controller...\n";
    $controller = new ProductAttachmentController();
    
    echo "Calling store method...\n";
    $response = $controller->store($request, $product);
    
    echo "Response received:\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n\n";
    
    // Clean up
    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
    
    // Check if attachment was created
    $attachments = $product->fresh()->attachments;
    echo "Product attachments count after upload: " . $attachments->count() . "\n";
    
    if ($attachments->count() > 0) {
        echo "Latest attachment:\n";
        $latest = $attachments->last();
        echo "  ID: {$latest->id}\n";
        echo "  Title: {$latest->title}\n";
        echo "  File Path: {$latest->file_path}\n";
        echo "  Original Filename: {$latest->original_filename}\n";
        echo "  Processing Status: {$latest->processing_status}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}