<?php
require_once 'vendor/autoload.php';

echo "=== Bearer Token Configuration Test ===\n\n";

// Check various configuration sources
$config_sources = [
    'services.unified_notification.token' => 'services config',
    'notifications.unified_api.bearer_token' => 'notifications config', 
    'NOTIFICATION_API_TOKEN' => 'environment variable',
    'UNIFIED_NOTIFICATION_TOKEN' => 'alt environment variable'
];

echo "Configuration Sources Check:\n";
foreach ($config_sources as $key => $description) {
    if (strpos($key, '.') !== false) {
        // This is a config key
        $value = "config('$key')"; // Simulated - would need Laravel context
    } else {
        // This is an environment variable
        $value = getenv($key) ?: 'not set';
    }
    
    $status = ($value && $value !== 'not set') ? '✅' : '❌';
    echo "$status $description ($key): " . ($value === 'not set' ? 'not set' : 'configured') . "\n";
}

echo "\n=== Environment File Example ===\n";
echo "Add these to your .env file:\n\n";
echo "# Unified Notification Service Configuration\n";
echo "NOTIFICATION_BASE_URL=https://notifications.shulesoft.africa/api\n";
echo "NOTIFICATION_API_TOKEN=your_bearer_token_here\n";
echo "\n# Alternative token variable names\n";
echo "UNIFIED_NOTIFICATION_TOKEN=your_bearer_token_here\n";

echo "\n=== Testing Session Creation (Mock Mode) ===\n";

// Test creating a session without authentication (should use mock)
$testUrl = 'http://localhost/safarichat/api/wasender/test-qr-generation';
$testData = ['session_id' => 'auth_test_' . time()];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Test QR Generation Response:\n";
echo "HTTP Code: $http_code\n";

if ($http_code == 200) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        echo "✅ Service is working\n";
        if (isset($data['test_result']['is_mock']) && $data['test_result']['is_mock']) {
            echo "📱 Using mock mode (expected without token)\n";
        }
    } else {
        echo "❌ Service error: " . ($data['message'] ?? 'Unknown') . "\n";
    }
} else {
    echo "❌ HTTP Error: $http_code\n";
    echo "Response: $response\n";
}

echo "\n=== Configuration Instructions ===\n";
echo "1. Set NOTIFICATION_API_TOKEN in your .env file\n";
echo "2. Get your Bearer token from notifications.shulesoft.africa\n";
echo "3. Clear config cache: php artisan config:clear\n";
echo "4. Test again - should use real API instead of mock\n";

echo "\n=== Troubleshooting ===\n";
echo "If still getting Bearer token errors:\n";
echo "- Verify your token is valid and active\n";  
echo "- Check token has permissions for /wasender/sessions/create\n";
echo "- Ensure notifications.shulesoft.africa is accessible\n";
echo "- Review Laravel logs for detailed error messages\n";

?>