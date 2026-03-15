<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$token = config('services.billing.access_token');
$apiUrl = config('services.billing.api_url');

echo "Testing Billing API Authentication\n";
echo "===================================\n";
echo "API URL: $apiUrl\n";
echo "Token: $token\n";
echo "Token Length: " . strlen($token) . "\n\n";

// Test 1: Simple GET request to verify authentication
echo "Test 1: Testing authentication with products endpoint...\n";
$response = Http::timeout(15)->withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
])->get($apiUrl . '/products');

echo "Status Code: " . $response->status() . "\n";
echo "Response: " . $response->body() . "\n\n";

// Test 2: Check if the token format is correct
echo "Test 2: Checking token format...\n";
if (preg_match('/^\d+\|[a-zA-Z0-9_]+$/', $token)) {
    echo "✓ Token format appears valid (Laravel Sanctum format)\n";
} else {
    echo "✗ Token format may be incorrect\n";
}
echo "\n";

// Test 3: Try to get organization details
echo "Test 3: Testing with organizations endpoint...\n";
$orgId = config('services.billing.organization_id');
$response2 = Http::timeout(15)->withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
])->get($apiUrl . '/organizations/' . $orgId);

echo "Status Code: " . $response2->status() . "\n";
echo "Response: " . $response2->body() . "\n\n";

echo "===================================\n";
echo "If all tests return 401 Unauthenticated, your token needs to be regenerated.\n";
