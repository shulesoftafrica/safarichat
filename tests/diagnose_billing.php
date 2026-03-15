<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════╗\n";
echo "║            SAFARICHAT BILLING API - DIAGNOSTIC REPORT                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════╝\n\n";

// Configuration Check
echo "📋 CONFIGURATION STATUS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$apiUrl = config('services.billing.api_url');
$token = config('services.billing.access_token');
$orgId = config('services.billing.organization_id');
$productId = config('services.billing.product_id');

printf("API URL:          %s %s\n", $apiUrl ?: '❌ NOT SET', $apiUrl ? '✅' : '');
printf("Access Token:     %s %s\n", $token ? substr($token, 0, 20) . '...' : '❌ NOT SET', $token ? '✅' : '');
printf("Token Length:     %d characters\n", strlen($token));
printf("Organization ID:  %s %s\n", $orgId ?: '❌ NOT SET', $orgId ? '✅' : '');
printf("Product ID:       %s %s\n", $productId ?: '⚠️  NOT SET (may be required)', $productId ? '✅' : '');

echo "\n";
echo "🔍 API CONNECTIVITY TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Test 1: Base API connectivity (without auth)
echo "\n1. Testing base API endpoint (no auth)...\n";
try {
    $response = Http::timeout(10)->get($apiUrl);
    printf("   Status: %d\n", $response->status());
    printf("   Response: %s\n", substr($response->body(), 0, 200));
} catch (\Exception $e) {
    printf("   ❌ Error: %s\n", $e->getMessage());
}

// Test 2: Products endpoint with authentication
echo "\n2. Testing /products endpoint (with Bearer token)...\n";
try {
    $response = Http::timeout(15)->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ])->get($apiUrl . '/products');
    
    printf("   Status: %d %s\n", $response->status(), $response->successful() ? '✅' : '❌');
    printf("   Response: %s\n", $response->body());
    
    if ($response->status() === 401) {
        echo "\n   ⚠️  AUTHENTICATION FAILED\n";
        echo "   Possible causes:\n";
        echo "   • Token has expired or been revoked\n";
        echo "   • Token was generated for a different environment\n";
        echo "   • Token doesn't belong to the specified organization\n";
        echo "   • API authentication method has changed\n";
    }
} catch (\Exception $e) {
    printf("   ❌ Error: %s\n", $e->getMessage());
}

// Test 3: Organizations endpoint
echo "\n3. Testing /organizations/{$orgId} endpoint...\n";
try {
    $response = Http::timeout(15)->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ])->get($apiUrl . '/organizations/' . $orgId);
    
    printf("   Status: %d %s\n", $response->status(), $response->successful() ? '✅' : '❌');
    printf("   Response: %s\n", $response->body());
} catch (\Exception $e) {
    printf("   ❌ Error: %s\n", $e->getMessage());
}

// Test 4: If product ID is set, test that endpoint
if ($productId) {
    echo "\n4. Testing /products/{$productId} endpoint...\n";
    try {
        $response = Http::timeout(15)->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->get($apiUrl . '/products/' . $productId);
        
        printf("   Status: %d %s\n", $response->status(), $response->successful() ? '✅' : '❌');
        printf("   Response: %s\n", substr($response->body(), 0, 500));
    } catch (\Exception $e) {
        printf("   ❌ Error: %s\n", $e->getMessage());
    }
}

echo "\n";
echo "💡 RECOMMENDATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (!$productId) {
    echo "⚠️  BILLING_PRODUCT_ID is not set. You may need to:\n";
    echo "   1. Create a 'SafariChat' product in the Shulesoft Billing dashboard\n";
    echo "   2. Note the Product ID and add it to your .env file\n\n";
}

echo "📌 TO FIX AUTHENTICATION ERRORS:\n\n";
echo "1. Contact Shulesoft Billing Support:\n";
echo "   • Email: support@shulesoft.africa (example)\n";
echo "   • Request: New API access token for organization ID: {$orgId}\n\n";

echo "2. Generate a new token via Shulesoft Dashboard:\n";
echo "   • Login to: https://shulesoftapi.shulesoft.africa\n";
echo "   • Navigate to: Settings → API Tokens\n";
echo "   • Generate a new token for 'SafariChat'\n";
echo "   • Copy the token and update BILLING_ACCESS_TOKEN in .env\n\n";

echo "3. Verify token immediately after generation:\n";
echo "   • Run: php test_billing_token.php\n";
echo "   • Should see HTTP 200 responses (not 401)\n\n";

echo "4. Test with user 45 again:\n";
echo "   • Run: php check_user45.php\n";
echo "   • Check logs: storage/logs/laravel.log\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
