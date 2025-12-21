<?php
require_once 'vendor/autoload.php';

// Test authentication with the unified notification service
$baseUrl = 'https://notifications.shulesoft.africa/api';
$bearerToken = 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn';

echo "=== Testing Unified Notification Service Authentication ===\n";
echo "Base URL: $baseUrl\n";
echo "Token: " . substr($bearerToken, 0, 20) . "...\n\n";

// Test 1: Check if the service is accessible
echo "1. Testing basic connectivity...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

// Test 2: Test authentication with current token
echo "2. Testing authentication with current token...\n";
$testData = [
    'schema_name' => 'test_schema',
    'channel' => 'whatsapp',
    'to' => '+255712345678',
    'message' => 'Test authentication',
    'priority' => 'normal'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/notifications/send');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $bearerToken,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n\n";

// Test 3: Test WaSender session creation endpoint
echo "3. Testing WaSender session creation...\n";
$sessionData = [
    'schema_name' => 'test_schema',
    'name' => 'Test Session',
    'phone_number' => '+255712345678',
    'account_protection' => true,
    'log_messages' => true,
    'read_incoming_messages' => true,
    'webhook_enabled' => true,
    'webhook_events' => ['messages.received', 'session.status']
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/wasender/sessions/create');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sessionData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $bearerToken,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n\n";

// Analyze results
$data = json_decode($response, true);
if ($httpCode === 401 || (isset($data['message']) && strpos($data['message'], 'personal access token') !== false)) {
    echo "❌ Authentication Failed - Token may be expired or invalid\n";
    echo "✅ Solution: You need to get a new personal access token from the API provider\n";
} elseif ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Authentication Successful\n";
} else {
    echo "⚠️  Unexpected response - check API endpoint or server status\n";
}

echo "\n=== Test Complete ===\n";