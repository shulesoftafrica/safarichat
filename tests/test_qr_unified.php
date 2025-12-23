<?php
require_once 'vendor/autoload.php';

// Simple test script to verify QR code generation from unified service
$baseUrl = 'http://localhost:8000';
$endpoint = '/api/wasender/test-qr-generation';

// Test data
$testSessionId = 'test_session_' . uniqid();

echo "=== Testing QR Code Generation via Unified Service ===\n";
echo "Base URL: $baseUrl\n";
echo "Endpoint: $endpoint\n";
echo "Test Session ID: $testSessionId\n\n";

// Prepare the test request
$postData = json_encode([
    'session_id' => $testSessionId
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Making request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response:\n";
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n";
    
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        echo "\n✅ QR generation test successful!\n";
        if (isset($data['test_result']['qr_code'])) {
            echo "✅ QR code received from unified service\n";
        }
    } else {
        echo "\n❌ QR generation test failed\n";
        if (isset($data['message'])) {
            echo "Error: " . $data['message'] . "\n";
        }
    }
}

echo "\n=== Test Complete ===\n";