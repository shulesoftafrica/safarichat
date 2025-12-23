<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing API Authentication ===\n";

$baseUrl = 'https://notifications.shulesoft.africa/api';
$token = config('services.unified_notification.token') 
    ?? config('notifications.unified_api.bearer_token')
    ?? env('NOTIFICATION_API_TOKEN')
    ?? env('UNIFIED_NOTIFICATION_TOKEN');

echo "Base URL: {$baseUrl}\n";
echo "Token: " . ($token ? substr($token, 0, 10) . '...[' . strlen($token) . ' chars total]' : '[NOT SET]') . "\n";

// Test API call with proper authentication
echo "\n=== Testing Authenticated API Call ===\n";

try {
    $testData = [
        'schema_name' => '75298c45-1441-4cfa-a330-aeadfd47a85f', // System instance UUID
        'channel' => 'whatsapp',
        'to' => '+255714825469',
        'message' => 'Test message: ' . date('H:i:s'),
        'priority' => 'high',
        'type' => 'wasender'
    ];

    echo "Test Data:\n";
    echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

    $response = \Illuminate\Support\Facades\Http::withToken($token)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])
        ->timeout(30)
        ->post($baseUrl . '/notifications/send', $testData);

    echo "\nResponse Status: " . $response->status() . "\n";
    echo "Response Headers: " . json_encode($response->headers()) . "\n";
    echo "Response Body: " . $response->body() . "\n";

    if ($response->successful()) {
        echo "✅ API call successful!\n";
        $responseData = $response->json();
        
        if (isset($responseData['message_id'])) {
            echo "✅ Message ID: " . $responseData['message_id'] . "\n";
        }
        if (isset($responseData['external_id'])) {
            echo "✅ External ID: " . $responseData['external_id'] . "\n";
        }
    } else {
        echo "❌ API call failed\n";
        if ($response->status() === 401) {
            echo "   Issue: Authentication failed - token might be invalid\n";
        } elseif ($response->status() === 422) {
            echo "   Issue: Validation error - check required fields\n";
        } else {
            echo "   Issue: HTTP " . $response->status() . " error\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// Test the UnifiedNotificationService directly
echo "\n=== Testing UnifiedNotificationService Directly ===\n";

try {
    $service = new \App\Services\UnifiedNotificationService();
    
    $result = $service->sendNotification([
        'schema_name' => '75298c45-1441-4cfa-a330-aeadfd47a85f',
        'to' => '+255714825469',
        'message' => 'Direct service test: ' . date('H:i:s'),
        'message_type' => 'text',
        'priority' => 'high'
    ]);

    echo "Service Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

    if (isset($result['success']) && $result['success']) {
        echo "✅ UnifiedNotificationService working correctly!\n";
    } else {
        echo "❌ UnifiedNotificationService failed\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }

} catch (Exception $e) {
    echo "❌ Service Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}