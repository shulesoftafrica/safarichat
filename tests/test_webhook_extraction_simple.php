<?php

/**
 * Simple Webhook Message Extraction Test
 * 
 * Tests the extractMessageData logic without Laravel dependencies
 */

echo "Testing Webhook Message Extraction Logic...\n\n";

// Mock Instance object
class MockInstance {
    public $user_id = 1;
    public $instance_id = 'test_instance';
}

// Mock the extractMessageData logic directly
function extractMessageData($webhookData, $instance)
{
    try {
        // Handle different webhook payload structures
        $messageData = null;
        
        // New webhook format with nested data.messages structure
        if (isset($webhookData['data']['messages'])) {
            $messageData = $webhookData['data']['messages'];
        }
        // Legacy format with direct message data
        elseif (isset($webhookData['messages'])) {
            $messageData = $webhookData['messages'];
        }
        // Direct message format
        else {
            $messageData = $webhookData;
        }

        // Extract phone number from remoteJid or key structure
        $chatId = null;
        $phoneNumber = null;
        
        if (isset($messageData['key']['remoteJid'])) {
            $chatId = $messageData['key']['remoteJid'];
            $phoneNumber = $messageData['key']['cleanedSenderPn'] ?? null;
        } elseif (isset($messageData['remoteJid'])) {
            $chatId = $messageData['remoteJid'];
        } else {
            $chatId = $messageData['chatId'] ?? $messageData['from'] ?? null;
        }

        if (!$chatId) {
            echo "   ❌ No chatId, remoteJid or from field found\n";
            return null;
        }

        // Clean phone number if not already provided
        if (!$phoneNumber) {
            $phoneNumber = str_replace(['@s.whatsapp.net', '@c.us', '@g.us'], '', $chatId);
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        }
        
        if (empty($phoneNumber)) {
            echo "   ❌ Could not extract valid phone number\n";
            return null;
        }

        // Extract message body from different possible locations
        $messageBody = '';
        
        // Check messageBody field first (direct field)
        if (isset($messageData['messageBody']) && !empty($messageData['messageBody'])) {
            $messageBody = $messageData['messageBody'];
        }
        // Check nested message.conversation
        elseif (isset($messageData['message']['conversation'])) {
            $messageBody = $messageData['message']['conversation'];
        }
        // Check other common fields
        elseif (isset($messageData['body'])) {
            $messageBody = $messageData['body'];
        }
        elseif (isset($messageData['text'])) {
            $messageBody = $messageData['text'];
        }
        
        if (empty(trim($messageBody))) {
            $messageBody = '[Media message]';
        }

        // Extract sender name
        $senderName = $messageData['pushName'] ?? 
                     $messageData['senderName'] ?? 
                     $messageData['name'] ?? 
                     null;

        // Determine message type
        $messageType = 'text'; // Default
        if (isset($messageData['message'])) {
            $message = $messageData['message'];
            if (isset($message['conversation'])) {
                $messageType = 'text';
            } elseif (isset($message['imageMessage'])) {
                $messageType = 'image';
            } elseif (isset($message['videoMessage'])) {
                $messageType = 'video';
            } elseif (isset($message['audioMessage']) || isset($message['pttMessage'])) {
                $messageType = 'audio';
            } elseif (isset($message['documentMessage'])) {
                $messageType = 'document';
            }
        }

        // Check if it's a group message
        $isGroup = str_contains($chatId, '@g.us') || 
                  str_contains($chatId, '.g.') ||
                  ($messageData['isGroup'] ?? false) === true;

        // Extract timestamp
        $timestamp = $webhookData['timestamp'] ?? 
                    $messageData['messageTimestamp'] ?? 
                    $messageData['timestamp'] ?? 
                    time();
        
        // Convert milliseconds to seconds if needed
        if ($timestamp > 9999999999) {
            $timestamp = intval($timestamp / 1000);
        }
        
        if (is_numeric($timestamp)) {
            $timestamp = date('Y-m-d H:i:s', $timestamp);
        }

        // Extract message ID
        $messageId = $messageData['id'] ?? 
                    $messageData['key']['id'] ?? 
                    $messageData['messageId'] ?? 
                    uniqid();

        // Check if message is from self
        $fromMe = $messageData['key']['fromMe'] ?? 
                 $messageData['fromMe'] ?? 
                 false;

        return [
            'user_id' => $instance->user_id,
            'instance_id' => $instance->instance_id,
            'message_id' => $messageId,
            'chat_id' => $chatId,
            'phone_number' => $phoneNumber,
            'sender_name' => $senderName,
            'message_body' => trim($messageBody),
            'message_type' => $messageType,
            'from_me' => $fromMe,
            'is_group' => $isGroup,
            'message_timestamp' => $timestamp,
            'status' => 'received'
        ];

    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        return null;
    }
}

try {
    // Test webhook data in the new format
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
                    "conversation" => "wasender testing"
                ],
                "messageBody" => "wasender testing",
                "remoteJid" => "255714825469@s.whatsapp.net",
                "id" => "3EB0F6F8CC499363721CF6"
            ]
        ],
        "timestamp" => 1764534733727
    ];

    echo "1. Testing new webhook format extraction...\n";

    $mockInstance = new MockInstance();
    $extractedData = extractMessageData($newWebhookFormat, $mockInstance);

    if ($extractedData) {
        echo "   ✅ Extraction: SUCCESS\n\n";
        
        echo "2. Extracted Fields:\n";
        echo "   📱 Phone Number: " . $extractedData['phone_number'] . "\n";
        echo "   💬 Message Body: '" . $extractedData['message_body'] . "'\n";
        echo "   👤 Sender Name: " . $extractedData['sender_name'] . "\n";
        echo "   🆔 Message ID: " . $extractedData['message_id'] . "\n";
        echo "   💾 Chat ID: " . $extractedData['chat_id'] . "\n";
        echo "   📧 Message Type: " . $extractedData['message_type'] . "\n";
        echo "   ↩️ From Me: " . ($extractedData['from_me'] ? 'Yes' : 'No') . "\n";
        echo "   👥 Is Group: " . ($extractedData['is_group'] ? 'Yes' : 'No') . "\n";
        echo "   ⏰ Timestamp: " . $extractedData['message_timestamp'] . "\n";
        
        echo "\n3. Field Validation:\n";
        $tests = [
            'Phone Number' => $extractedData['phone_number'] === '255714825469',
            'Message Body' => $extractedData['message_body'] === 'wasender testing',
            'Sender Name' => $extractedData['sender_name'] === 'Double Fruitful',
            'Message ID' => $extractedData['message_id'] === '3EB0F6F8CC499363721CF6',
            'From Me' => $extractedData['from_me'] === false,
            'Message Type' => $extractedData['message_type'] === 'text',
            'Is Group' => $extractedData['is_group'] === false,
        ];

        $passed = 0;
        foreach ($tests as $testName => $result) {
            $status = $result ? '✅' : '❌';
            echo "   {$status} {$testName}: " . ($result ? 'PASS' : 'FAIL') . "\n";
            if ($result) $passed++;
        }

        echo "\n4. Test Summary:\n";
        echo "   📊 Total Tests: " . count($tests) . "\n";
        echo "   ✅ Passed: {$passed}\n";
        echo "   ❌ Failed: " . (count($tests) - $passed) . "\n";
        echo "   📈 Success Rate: " . round(($passed / count($tests)) * 100, 1) . "%\n";

        if ($passed === count($tests)) {
            echo "\n🎉 ALL TESTS PASSED!\n";
            echo "The webhook extraction logic correctly handles the new format.\n";
        } else {
            echo "\n⚠️ Some tests failed. Please review the extraction logic.\n";
        }

    } else {
        echo "   ❌ Extraction: FAILED\n";
        echo "   The extraction method returned null.\n";
    }

    // Test image message format
    echo "\n" . str_repeat("-", 50) . "\n";
    echo "\n5. Testing Image Message Format...\n";

    $imageWebhook = [
        "data" => [
            "messages" => [
                "key" => [
                    "id" => "IMG_TEST_123",
                    "fromMe" => false,
                    "remoteJid" => "255714825469@s.whatsapp.net",
                    "cleanedSenderPn" => "255714825469"
                ],
                "pushName" => "Test User",
                "message" => [
                    "imageMessage" => [
                        "url" => "https://example.com/image.jpg",
                        "fileName" => "test.jpg",
                        "mimetype" => "image/jpeg",
                        "caption" => "Check this out!"
                    ]
                ],
                "messageBody" => "Check this out!",
                "id" => "IMG_TEST_123"
            ]
        ]
    ];

    $imageData = extractMessageData($imageWebhook, $mockInstance);

    if ($imageData) {
        echo "   ✅ Image Extraction: SUCCESS\n";
        echo "   🖼️ Message Type: " . $imageData['message_type'] . "\n";
        echo "   💬 Caption: '" . $imageData['message_body'] . "'\n";
        
        if ($imageData['message_type'] === 'image') {
            echo "   ✅ Correctly identified as image message\n";
        }
    } else {
        echo "   ❌ Image Extraction: FAILED\n";
    }

} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔬 Webhook Format Test Complete!\n";
echo str_repeat("=", 60) . "\n";