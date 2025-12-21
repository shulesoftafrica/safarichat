<?php
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Log;

// Test full session creation and QR generation flow
$baseUrl = 'http://localhost:8000';
$testSessionData = [
    'phone_number' => '+255712345678',
    'account_protection' => true,
    'log_messages' => true,
    'read_incoming_messages' => true,
    'webhook_enabled' => true,
    'webhook_events' => ['messages.received', 'session.status', 'messages.update']
];

echo "=== Testing Complete WhatsApp Session Flow ===\n";
echo "Base URL: $baseUrl\n";
echo "Test Phone: " . $testSessionData['phone_number'] . "\n\n";

// Test 1: Create Session
echo "1. Testing session creation...\n";

// First, let's try the legacy method which might not require auth
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/wasender/create-session');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testSessionData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
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
echo "Response:\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 200 && isset($data['success']) && $data['success']) {
    echo "✅ Session creation successful!\n";
    
    // Extract session ID for QR generation test
    $sessionId = null;
    if (isset($data['data']['wasender_session_id'])) {
        $sessionId = $data['data']['wasender_session_id'];
    } elseif (isset($data['data']['id'])) {
        $sessionId = $data['data']['id'];
    }
    
    if ($sessionId) {
        echo "Session ID: $sessionId\n";
        
        // Test 2: Generate QR Code
        echo "\n2. Testing QR code generation for session: $sessionId\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/wasender/test-qr-generation');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['session_id' => $sessionId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $qrResponse = curl_exec($ch);
        $qrHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $qrError = curl_error($ch);
        curl_close($ch);

        echo "QR HTTP Status: $qrHttpCode\n";
        if ($qrError) {
            echo "QR cURL Error: $qrError\n";
        }
        
        $qrData = json_decode($qrResponse, true);
        if (isset($qrData['test_result']['qr_code'])) {
            echo "✅ QR code generated successfully!\n";
            echo "QR Code Length: " . strlen($qrData['test_result']['qr_code']) . " characters\n";
            if (isset($qrData['test_result']['is_mock'])) {
                echo "✅ Using mock QR code (unified API fallback)\n";
            }
        } else {
            echo "QR Response:\n";
            echo json_encode($qrData, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Could not extract session ID from response\n";
    }
    
} else {
    echo "❌ Session creation failed\n";
    if (isset($data['message'])) {
        echo "Error: " . $data['message'] . "\n";
    }
}

echo "\n=== Testing Summary ===\n";
echo "1. Session Creation: " . ($httpCode === 200 ? "✅ Success" : "❌ Failed") . "\n";
echo "2. Authentication: ";
if (isset($data['error']) && strpos($data['error'], 'personal access token') !== false) {
    echo "⚠️  Requires Personal Access Token (fallback active)\n";
} elseif ($httpCode === 200) {
    echo "✅ Success\n";
} else {
    echo "❌ Failed\n";
}
echo "3. QR Generation: " . (isset($qrData) && isset($qrData['test_result']['qr_code']) ? "✅ Success" : "❌ Not tested") . "\n";

echo "\n=== Test Complete ===\n";