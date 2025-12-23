<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WhatsappInstance;
use App\Services\SystemWhatsAppService;

echo "=== System Default WhatsApp Instance Test ===\n";

// Test 1: Check if system default instance exists
$systemInstance = WhatsappInstance::getSystemDefault();
if ($systemInstance) {
    echo "✅ System Instance Found!\n";
    echo "   ID: {$systemInstance->id}\n";
    echo "   Phone: {$systemInstance->phone_number}\n";
    echo "   Display Name: {$systemInstance->display_name}\n";
    echo "   Usage Scope: {$systemInstance->usage_scope}\n";
    echo "   Is System Default: " . ($systemInstance->is_system_default ? 'Yes' : 'No') . "\n";
    echo "   Message Types: " . implode(', ', json_decode($systemInstance->allowed_message_types, true) ?? []) . "\n";
} else {
    echo "❌ System Instance NOT Found!\n";
}

// Test 2: Check SystemWhatsAppService
try {
    $service = new SystemWhatsAppService();
    echo "\n✅ SystemWhatsAppService Created!\n";
    echo "   Available: " . ($service->isAvailable() ? 'Yes' : 'No') . "\n";
    
    $stats = $service->getSystemStats(30);
    echo "   Stats: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "\n❌ SystemWhatsAppService Error: " . $e->getMessage() . "\n";
}

// Test 3: Test message type validation
if ($systemInstance) {
    echo "\n=== Message Type Validation ===\n";
    $messageTypes = ['otp_verification', 'welcome_message', 'payment_reminder', 'invalid_type'];
    
    foreach ($messageTypes as $type) {
        $canSend = $systemInstance->canSendMessageType($type);
        echo "   {$type}: " . ($canSend ? '✅ Allowed' : '❌ Not Allowed') . "\n";
    }
}

echo "\n=== Test Complete ===\n";