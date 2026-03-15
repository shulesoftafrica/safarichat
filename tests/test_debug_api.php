<?php

// Quick test of the new debug API endpoints

$baseUrl = 'http://localhost/safarichat/public/api/billing/debug';

echo "Testing Billing Debug API Endpoints\n";
echo "====================================\n\n";

// Test 1: Get current token
echo "Test 1: Getting current token from .env...\n";
$ch = curl_init($baseUrl . '/current-token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: $response\n\n";

$currentData = json_decode($response, true);
$currentToken = $currentData['token'] ?? '';

// Test 2: Test the current token
echo "Test 2: Testing current token authentication...\n";
$ch = curl_init($baseUrl . '/test-token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'api_url' => 'https://shulesoftapi.shulesoft.africa/api',
    'token' => $currentToken,
    'organization_id' => '1'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
$result = json_decode($response, true);
echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Test with a different token (if provided)
if ($argc > 1) {
    $testToken = $argv[1];
    echo "Test 3: Testing provided token: $testToken...\n";
    
    $ch = curl_init($baseUrl . '/test-token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'api_url' => 'https://shulesoftapi.shulesoft.africa/api',
        'token' => $testToken,
        'organization_id' => '1'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    $result = json_decode($response, true);
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
}

echo "====================================\n";
echo "Usage: php test_debug_api.php [token_to_test]\n";
echo "\nOpen http://localhost/safarichat/public/test-billing-token.html\n";
echo "for an interactive web-based tester!\n";
