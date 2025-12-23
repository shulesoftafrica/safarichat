<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== API Configuration Analysis ===\n";

// Check all possible configuration sources
$configs = [
    'services.unified_notification.base_url' => config('services.unified_notification.base_url'),
    'services.unified_notification.token' => config('services.unified_notification.token'),
    'notifications.unified_api.bearer_token' => config('notifications.unified_api.bearer_token'),
    'NOTIFICATION_API_TOKEN' => env('NOTIFICATION_API_TOKEN'),
    'UNIFIED_NOTIFICATION_TOKEN' => env('UNIFIED_NOTIFICATION_TOKEN'),
];

foreach ($configs as $key => $value) {
    if ($key === 'services.unified_notification.token' || 
        $key === 'notifications.unified_api.bearer_token' || 
        $key === 'NOTIFICATION_API_TOKEN' || 
        $key === 'UNIFIED_NOTIFICATION_TOKEN') {
        echo "{$key}: " . ($value ? '[SET - ' . strlen($value) . ' chars]' : '[NOT SET]') . "\n";
    } else {
        echo "{$key}: " . ($value ?: '[NOT SET]') . "\n";
    }
}

// Test different API endpoints to find the correct one
$baseUrl = config('services.unified_notification.base_url', 'https://notifications.shulesoft.africa/api');
$token = config('services.unified_notification.token') 
    ?? config('notifications.unified_api.bearer_token')
    ?? env('NOTIFICATION_API_TOKEN')
    ?? env('UNIFIED_NOTIFICATION_TOKEN');

echo "\n=== API Endpoint Testing ===\n";
echo "Base URL: {$baseUrl}\n";
echo "Token: " . ($token ? '[SET - ' . strlen($token) . ' chars]' : '[NOT SET]') . "\n";

// Test different possible endpoints
$testEndpoints = [
    '',
    '/health',
    '/status',
    '/notifications',
    '/notifications/send',
    '/api/notifications/send'
];

foreach ($testEndpoints as $endpoint) {
    $testUrl = rtrim($baseUrl, '/') . $endpoint;
    echo "\nTesting: {$testUrl}\n";
    
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(5)->get($testUrl);
        echo "  Status: {$response->status()}\n";
        
        if ($response->successful()) {
            $body = $response->body();
            echo "  Body: " . substr($body, 0, 100) . (strlen($body) > 100 ? '...' : '') . "\n";
        } else {
            echo "  Error: " . substr($response->body(), 0, 200) . "\n";
        }
    } catch (Exception $e) {
        echo "  Exception: " . $e->getMessage() . "\n";
    }
}

// Check if there's an alternative API configuration
echo "\n=== Alternative Configuration Check ===\n";

// Check for WaSender specific configuration
$wasenderUrl = env('WASENDER_API_URL');
$wasenderToken = env('WASENDER_API_TOKEN');

echo "WASENDER_API_URL: " . ($wasenderUrl ?: '[NOT SET]') . "\n";
echo "WASENDER_API_TOKEN: " . ($wasenderToken ? '[SET - ' . strlen($wasenderToken) . ' chars]' : '[NOT SET]') . "\n";

// Check other messaging service configurations
$twilioSid = env('TWILIO_ACCOUNT_SID');
$twilioToken = env('TWILIO_AUTH_TOKEN');

echo "TWILIO_ACCOUNT_SID: " . ($twilioSid ? '[SET]' : '[NOT SET]') . "\n";
echo "TWILIO_AUTH_TOKEN: " . ($twilioToken ? '[SET]' : '[NOT SET]') . "\n";