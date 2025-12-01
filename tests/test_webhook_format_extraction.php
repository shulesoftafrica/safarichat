<?php

/**
 * Test Webhook Message Extraction
 * 
 * This script tests the updated extractMessageData method with the new webhook format
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Http\Controllers\WaSenderController;
use App\Models\WhatsappInstance;
use App\Models\User;

echo "Testing Webhook Message Extraction with New Format...\n\n";

try {
    // Create test webhook data in the new format
    $newWebhookFormat = [
        "event" => "messages.received",
        "sessionId" => "de042e1a46b394de63bed34c5b2d9c55108db5061b075b29ce9225be30d7cca2",
        "data" => [
            "messages" => [
                "key" => [
                    "id" => "3EB0F6F8CC499363721CF6",
                    "fromMe" => false,
                    "remoteJid" => "255714825469@s.whatsapp.net",
                    "senderPn" => "255714825469@s.whatsapp.net",
                    "cleanedSenderPn" => "255714825469",
                    "senderLid" => "178121085038764@lid",
                    "addressingMode" => "pn"
                ],
                "messageTimestamp" => 1764534733,
                "pushName" => "Double Fruitful",
                "broadcast" => false,
                "message" => [
                    "conversation" => "wasender testing",
                    "messageContextInfo" => [
                        "deviceListMetadata" => [
                            "senderKeyHash" => "M61D4vwSkSP1uw==",
                            "senderTimestamp" => "1764133717",
                            "senderAccountType" => "E2EE",
                            "receiverAccountType" => "E2EE",
                            "recipientKeyHash" => "qYrjM+uroNMGCQ==",
                            "recipientTimestamp" => "1763365800"
                        ],
                        "deviceListMetadataVersion" => 2,
                        "messageSecret" => "66K4nWeraErjeZ2yY19SguyGKEER0i5k4ds2Z2Me6RY=",
                        "limitSharingV2" => [
                            "trigger" => "UNKNOWN",
                            "initiatedByMe" => false
                        ]
                    ]
                ],
                "messageBody" => "wasender testing",
                "remoteJid" => "255714825469@s.whatsapp.net",
                "id" => "3EB0F6F8CC499363721CF6"
            ]
        ],
        "timestamp" => 1764534733727
    ];

    echo "1. Testing with new webhook format...\n";

    // Find test user and instance
    $testUser = User::first();
    if (!$testUser) {
        echo "❌ No users found. Please create a test user first.\n";
        exit(1);
    }

    $testInstance = WhatsappInstance::where('user_id', $testUser->id)->first();
    if (!$testInstance) {
        $testInstance = WhatsappInstance::create([
            'user_id' => $testUser->id,
            'instance_id' => 'test_instance_' . time(),
            'instance_name' => 'Test Instance',
            'phone_number' => '+255123456789',
            'status' => 'connected'
        ]);
    }

    echo "   ✅ Test User: {$testUser->name}\n";
    echo "   ✅ Test Instance: {$testInstance->instance_name}\n\n";

    // Create controller instance
    $aiWhatsAppService = app(\App\Services\AiWhatsAppService::class);
    $controller = new WaSenderController($aiWhatsAppService);

    // Use reflection to call the private extractMessageData method
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('extractMessageData');
    $method->setAccessible(true);

    // Test the extraction
    $extractedData = $method->invoke($controller, $newWebhookFormat, $testInstance);

    echo "2. Extraction Results:\n";
    if ($extractedData) {
        echo "   ✅ Extraction: SUCCESS\n";
        echo "   📱 Phone Number: {$extractedData['phone_number']}\n";
        echo "   💬 Message Body: '{$extractedData['message_body']}'\n";
        echo "   👤 Sender Name: {$extractedData['sender_name']}\n";
        echo "   🆔 Message ID: {$extractedData['message_id']}\n";
        echo "   💾 Chat ID: {$extractedData['chat_id']}\n";
        echo "   📧 Message Type: {$extractedData['message_type']}\n";
        echo "   ↩️ From Me: " . ($extractedData['from_me'] ? 'Yes' : 'No') . "\n";
        echo "   👥 Is Group: " . ($extractedData['is_group'] ? 'Yes' : 'No') . "\n";
        echo "   ⏰ Timestamp: {$extractedData['message_timestamp']}\n";
        echo "   📄 Status: {$extractedData['status']}\n";

        if ($extractedData['media_data']) {
            echo "   🎬 Media Data: Present\n";
        }

        echo "\n3. Field Validation:\n";
        $validations = [
            'phone_number' => $extractedData['phone_number'] === '255714825469',
            'message_body' => $extractedData['message_body'] === 'wasender testing',
            'sender_name' => $extractedData['sender_name'] === 'Double Fruitful',
            'message_id' => $extractedData['message_id'] === '3EB0F6F8CC499363721CF6',
            'from_me' => $extractedData['from_me'] === false,
            'message_type' => $extractedData['message_type'] === 'text',
        ];

        $passCount = 0;
        foreach ($validations as $field => $passed) {
            $status = $passed ? '✅' : '❌';
            echo "   {$status} {$field}: " . ($passed ? 'PASS' : 'FAIL') . "\n";
            if ($passed) $passCount++;
        }

        echo "\n4. Test Summary:\n";
        echo "   📊 Fields Tested: " . count($validations) . "\n";
        echo "   ✅ Passed: {$passCount}\n";
        echo "   ❌ Failed: " . (count($validations) - $passCount) . "\n";
        echo "   📈 Success Rate: " . round(($passCount / count($validations)) * 100, 1) . "%\n";

        if ($passCount === count($validations)) {
            echo "\n🎉 ALL TESTS PASSED! The webhook extraction is working perfectly.\n";
        } else {
            echo "\n⚠️ Some tests failed. Please check the extraction logic.\n";
        }

    } else {
        echo "   ❌ Extraction: FAILED\n";
        echo "   🔍 The extractMessageData method returned null.\n";
        echo "   🐛 Check the logs for error details.\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";

    // Test with image message format
    echo "\n5. Testing with Image Message Format...\n";

    $imageWebhook = [
        "event" => "messages.received",
        "sessionId" => "test_session",
        "data" => [
            "messages" => [
                "key" => [
                    "id" => "TEST_IMG_123",
                    "fromMe" => false,
                    "remoteJid" => "255714825469@s.whatsapp.net",
                    "cleanedSenderPn" => "255714825469"
                ],
                "messageTimestamp" => time(),
                "pushName" => "Test User",
                "message" => [
                    "imageMessage" => [
                        "url" => "https://example.com/image.jpg",
                        "fileName" => "test_image.jpg",
                        "fileLength" => 54321,
                        "mimetype" => "image/jpeg",
                        "caption" => "Check out this image!"
                    ]
                ],
                "messageBody" => "Check out this image!",
                "remoteJid" => "255714825469@s.whatsapp.net",
                "id" => "TEST_IMG_123"
            ]
        ],
        "timestamp" => time() * 1000
    ];

    $imageData = $method->invoke($controller, $imageWebhook, $testInstance);

    if ($imageData) {
        echo "   ✅ Image Message Extraction: SUCCESS\n";
        echo "   🖼️ Message Type: {$imageData['message_type']}\n";
        echo "   💬 Caption: '{$imageData['message_body']}'\n";
        echo "   🎬 Has Media Data: " . ($imageData['media_data'] ? 'Yes' : 'No') . "\n";
        
        if ($imageData['media_data']) {
            echo "   📁 File Name: " . ($imageData['media_data']['filename'] ?? 'N/A') . "\n";
            echo "   🏷️ MIME Type: " . ($imageData['media_data']['mimetype'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   ❌ Image Message Extraction: FAILED\n";
    }

} catch (\Exception $e) {
    echo "\n❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "🔍 Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔬 Webhook Format Test Complete!\n";
echo str_repeat("=", 60) . "\n";