<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "Testing direct HTTP request to products endpoint...\n";

// Test the API endpoint directly
try {
    $baseUrl = config('app.url');
    $url = $baseUrl . '/api/billing/products';
    
    echo "Making request to: " . $url . "\n";
    
    $response = Http::timeout(10)->withHeaders([
        'X-API-Key' => 'Dp77IDXdqtBuB2zLvYovj2QmAK',
        'Accept' => 'application/json'
    ])->get($url, [
        'product_code' => 'safarichat',
        'currency' => 'TZS'
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}