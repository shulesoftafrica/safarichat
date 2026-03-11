#!/usr/bin/env php
<?php

/**
 * Billing Implementation Test Script
 * 
 * This script tests the Shulesoft Billing API integration
 * Run: php test-billing-integration.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  SafariChat Billing Integration Test Suite                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test counters
$passed = 0;
$failed = 0;
$warnings = 0;

// Helper function for test results
function test($description, $callback) {
    global $passed, $failed, $warnings;
    
    echo "Testing: {$description}... ";
    
    try {
        $result = $callback();
        
        if ($result === true) {
            echo "✅ PASS\n";
            $passed++;
        } elseif ($result === null) {
            echo "⚠️  WARNING\n";
            $warnings++;
        } else {
            echo "❌ FAIL: {$result}\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "❌ FAIL: {$e->getMessage()}\n";
        $failed++;
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. CONFIGURATION TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

test("Billing API URL is configured", function() {
    $url = config('services.billing.api_url');
    if (empty($url)) {
        return "BILLING_API_URL not set in .env";
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return "Invalid URL format: {$url}";
    }
    return true;
});

test("Billing Access Token is configured", function() {
    $token = config('services.billing.access_token');
    if (empty($token)) {
        return "BILLING_ACCESS_TOKEN not set in .env";
    }
    if (strlen($token) < 20) {
        return "Access token seems too short (possible placeholder)";
    }
    return true;
});

test("Organization ID is configured", function() {
    $orgId = config('services.billing.organization_id');
    if (empty($orgId)) {
        return "BILLING_ORGANIZATION_ID not set in .env";
    }
    return true;
});

test("Product ID is configured", function() {
    $productId = config('services.billing.product_id');
    if (empty($productId)) {
        return "BILLING_PRODUCT_ID not set in .env";
    }
    return true;
});

test("Credits Price Plan ID is configured", function() {
    $planId = config('services.billing.credits_price_plan_id');
    if (empty($planId)) {
        return "BILLING_CREDITS_PRICE_PLAN_ID not set in .env";
    }
    return true;
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "2. DATABASE SCHEMA TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

test("billing_accounts table exists", function() {
    try {
        DB::table('billing_accounts')->limit(1)->get();
        return true;
    } catch (Exception $e) {
        return "Table does not exist";
    }
});

test("subscription_ucn column exists", function() {
    $columns = DB::getSchemaBuilder()->getColumnListing('billing_accounts');
    if (!in_array('subscription_ucn', $columns)) {
        return "Column not found. Run migration: php artisan migrate";
    }
    return true;
});

test("credit_ucn column exists", function() {
    $columns = DB::getSchemaBuilder()->getColumnListing('billing_accounts');
    if (!in_array('credit_ucn', $columns)) {
        return "Column not found. Run migration: php artisan migrate";
    }
    return true;
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "3. API CONNECTIVITY TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

test("Can connect to Shulesoft API", function() {
    $apiUrl = config('services.billing.api_url');
    $token = config('services.billing.access_token');
    
    if (empty($apiUrl) || empty($token)) {
        return null; // Skip if not configured
    }
    
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ])->timeout(10)->get($apiUrl . '/products');
    
    if ($response->failed()) {
        return "API returned status {$response->status()}: {$response->body()}";
    }
    
    return true;
});

test("Can fetch SafariChat product details", function() {
    $apiUrl = config('services.billing.api_url');
    $token = config('services.billing.access_token');
    $productId = config('services.billing.product_id');
    
    if (empty($apiUrl) || empty($token) || empty($productId)) {
        return null; // Skip if not configured
    }
    
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ])->timeout(10)->get($apiUrl . '/products/' . $productId);
    
    if ($response->failed()) {
        return "Product not found. Status {$response->status()}";
    }
    
    return true;
});

test("Can fetch price plans for SafariChat", function() {
    $apiUrl = config('services.billing.api_url');
    $token = config('services.billing.access_token');
    $productId = config('services.billing.product_id');
    
    if (empty($apiUrl) || empty($token) || empty($productId)) {
        return null; // Skip if not configured
    }
    
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ])->timeout(10)->get($apiUrl . '/products/' . $productId . '/price-plans');
    
    if ($response->failed()) {
        return "Failed to fetch price plans. Status {$response->status()}";
    }
    
    $data = $response->json();
    $planCount = count($data['data'] ?? []);
    
    if ($planCount === 0) {
        return "No price plans found. Create plans in Shulesoft Dashboard";
    }
    
    echo "({$planCount} plans found) ";
    return true;
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "4. CONTROLLER METHODS TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

test("BillingApiController exists", function() {
    return class_exists('App\Http\Controllers\Api\BillingApiController');
});

test("getWalletUCN() method exists", function() {
    return method_exists('App\Http\Controllers\Api\BillingApiController', 'getWalletUCN');
});

test("fetchPricePlan() method exists", function() {
    return method_exists('App\Http\Controllers\Api\BillingApiController', 'fetchPricePlan');
});

test("getFallbackPricing() method exists", function() {
    return method_exists('App\Http\Controllers\Api\BillingApiController', 'getFallbackPricing');
});

test("upgradePlan() method exists", function() {
    return method_exists('App\Http\Controllers\Api\BillingApiController', 'upgradePlan');
});

test("renewPlan() method exists", function() {
    return method_exists('App\Http\Controllers\Api\BillingApiController', 'renewPlan');
});

test("topUpWallet() method exists", function() {
    return method_exists('App\Http\Controllers\Api\BillingApiController', 'topUpWallet');
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "5. API ROUTES TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

test("GET /api/billing/wallet/get-ucn route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/billing/wallet/get-ucn' && in_array('GET', $route->methods())) {
            return true;
        }
    }
    return "Route not found in routes/api.php";
});

test("POST /api/billing/upgrade route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/billing/upgrade' && in_array('POST', $route->methods())) {
            return true;
        }
    }
    return "Route not found in routes/api.php";
});

test("POST /api/billing/renew route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/billing/renew' && in_array('POST', $route->methods())) {
            return true;
        }
    }
    return "Route not found in routes/api.php";
});

test("POST /api/billing/wallet/topup route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/billing/wallet/topup' && in_array('POST', $route->methods())) {
            return true;
        }
    }
    return "Route not found in routes/api.php";
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$total = $passed + $failed + $warnings;

echo "Total Tests: {$total}\n";
echo "✅ Passed:   {$passed}\n";
echo "❌ Failed:   {$failed}\n";
echo "⚠️  Warnings: {$warnings}\n";
echo "\n";

if ($failed === 0 && $warnings === 0) {
    echo "🎉 ALL TESTS PASSED! Billing integration is ready.\n";
    exit(0);
} elseif ($failed === 0) {
    echo "⚠️  Tests passed with warnings. Review configuration.\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Please fix the issues above.\n";
    exit(1);
}
