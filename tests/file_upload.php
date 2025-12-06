
<?php
/**
 * WaSender API Test Script
 * Tests media upload and message sending functionality
 * Based on official WaSender API documentation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Configuration
$apiKey = 'de042e1a46b394de63bed34c5b2d9c55108db5061b075b29ce9225be30d7cca2';
$baseUrl = 'https://www.wasenderapi.com/api';
$recipient = '+255714825469';

// Initialize HTTP client
$client = new Client();

echo "=== WaSender API Test Script ===\n";
echo "Testing file upload and message sending to: $recipient\n\n";

/**
 * Test 1: Upload media file using Base64 method
 */
function uploadMediaBase64($client, $baseUrl, $apiKey, $filePath) {
    echo "--- Test 1: Upload Media File (Base64 Method) ---\n";
    
    if (!file_exists($filePath)) {
        echo "❌ File not found: $filePath\n";
        return null;
    }
    
    try {
        // Read file and encode to base64
        $fileContent = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);
        $base64Content = base64_encode($fileContent);
        
        // Create data URL format
        $dataUrl = "data:$mimeType;base64,$base64Content";
        
        echo "📁 File: " . basename($filePath) . "\n";
        echo "📏 Size: " . formatBytes(filesize($filePath)) . "\n";
        echo "🎭 MIME: $mimeType\n";
        echo "⬆️ Uploading...\n";
        
        $response = $client->post("$baseUrl/upload", [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'base64' => $dataUrl
            ]
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if ($result['success']) {
            echo "✅ Upload successful!\n";
            echo "🔗 Public URL: " . $result['publicUrl'] . "\n\n";
            return $result['publicUrl'];
        } else {
            echo "❌ Upload failed: " . json_encode($result) . "\n\n";
            return null;
        }
        
    } catch (RequestException $e) {
        echo "❌ Upload failed: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
        echo "\n";
        return null;
    }
}

/**
 * Test 2: Upload media file using Raw Binary method
 */
function uploadMediaBinary($client, $baseUrl, $apiKey, $filePath) {
    echo "--- Test 2: Upload Media File (Raw Binary Method) ---\n";
    
    if (!file_exists($filePath)) {
        echo "❌ File not found: $filePath\n";
        return null;
    }
    
    try {
        $mimeType = mime_content_type($filePath);
        
        echo "📁 File: " . basename($filePath) . "\n";
        echo "📏 Size: " . formatBytes(filesize($filePath)) . "\n";
        echo "🎭 MIME: $mimeType\n";
        echo "⬆️ Uploading...\n";
        
        $response = $client->post("$baseUrl/upload", [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => $mimeType
            ],
            'body' => fopen($filePath, 'r')
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if ($result['success']) {
            echo "✅ Upload successful!\n";
            echo "🔗 Public URL: " . $result['publicUrl'] . "\n\n";
            return $result['publicUrl'];
        } else {
            echo "❌ Upload failed: " . json_encode($result) . "\n\n";
            return null;
        }
        
    } catch (RequestException $e) {
        echo "❌ Upload failed: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
        echo "\n";
        return null;
    }
}

/**
 * Test 3: Send text message
 */
function sendTextMessage($client, $baseUrl, $apiKey, $recipient, $message) {
    echo "--- Test 3: Send Text Message ---\n";
    echo "📱 To: $recipient\n";
    echo "💬 Message: $message\n";
    echo "📤 Sending...\n";
    
    try {
        $response = $client->post("$baseUrl/send-message", [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'to' => $recipient,
                'text' => $message
            ]
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if ($result['success']) {
            echo "✅ Message sent successfully!\n";
            echo "🆔 Message ID: " . $result['data']['msgId'] . "\n";
            echo "📞 JID: " . $result['data']['jid'] . "\n";
            echo "📊 Status: " . $result['data']['status'] . "\n\n";
            return true;
        } else {
            echo "❌ Message failed: " . json_encode($result) . "\n\n";
            return false;
        }
        
    } catch (RequestException $e) {
        echo "❌ Message failed: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
        echo "\n";
        return false;
    }
}

/**
 * Test 4: Send image message with uploaded media
 */
function sendImageMessage($client, $baseUrl, $apiKey, $recipient, $mediaUrl, $caption) {
    echo "--- Test 4: Send Image Message ---\n";
    echo "📱 To: $recipient\n";
    echo "🖼️ Image URL: $mediaUrl\n";
    echo "💬 Caption: $caption\n";
    echo "📤 Sending...\n";
    
    try {
        $response = $client->post("$baseUrl/send-message", [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'json' => [
                'to' => $recipient,
                'text' => $caption,
                'imageUrl' => $mediaUrl
            ]
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if ($result['success']) {
            echo "✅ Image message sent successfully!\n";
            echo "🆔 Message ID: " . $result['data']['msgId'] . "\n";
            echo "📞 JID: " . $result['data']['jid'] . "\n";
            echo "📊 Status: " . $result['data']['status'] . "\n\n";
            return true;
        } else {
            echo "❌ Image message failed: " . json_encode($result) . "\n\n";
            return false;
        }
        
    } catch (RequestException $e) {
        echo "❌ Image message failed: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
        echo "\n";
        return false;
    }
}

/**
 * Test 5: Send document message with uploaded media
 */
function sendDocumentMessage($client, $baseUrl, $apiKey, $recipient, $mediaUrl, $filename, $caption) {
    echo "--- Test 5: Send Document Message ---\n";
    echo "📱 To: $recipient\n";
    echo "📄 Document URL: $mediaUrl\n";
    echo "📝 Filename: $filename\n";
    echo "💬 Caption: $caption\n";
    echo "📤 Sending...\n";
    
    try {
        $response = $client->post("$baseUrl/send-message", [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'to' => $recipient,
                'text' => $caption, // Required: text field
                'document' => [
                    'url' => $mediaUrl,
                    'filename' => $filename
                ]
            ]
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if ($result['success']) {
            echo "✅ Document message sent successfully!\n";
            echo "🆔 Message ID: " . $result['data']['msgId'] . "\n";
            echo "📞 JID: " . $result['data']['jid'] . "\n";
            echo "📊 Status: " . $result['data']['status'] . "\n\n";
            return true;
        } else {
            echo "❌ Document message failed: " . json_encode($result) . "\n\n";
            return false;
        }
        
    } catch (RequestException $e) {
        echo "❌ Document message failed: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
        echo "\n";
        return false;
    }
}

/**
 * Utility function to format file sizes
 */
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

// Main execution
try {
    // Test file path
    $testFile = __DIR__ . '/../public/images/cards.png';
    
    // Run tests
    echo "🚀 Starting WaSender API tests...\n\n";
    
    // Test 1: Send simple text message
    $textSent = sendTextMessage($client, $baseUrl, $apiKey, $recipient, 
        "Hello! This is a test message from WaSender API. 🚀\nTimestamp: " . date('Y-m-d H:i:s'));
    
    // Test 2: Upload media file (try both methods)
    $mediaUrl = uploadMediaBase64($client, $baseUrl, $apiKey, $testFile);
    
    if (!$mediaUrl) {
        echo "Base64 upload failed, trying binary upload...\n";
        $mediaUrl = uploadMediaBinary($client, $baseUrl, $apiKey, $testFile);
    }
    
    // Test 3: Send image message if upload was successful
    if ($mediaUrl) {
        $imageSent = sendImageMessage($client, $baseUrl, $apiKey, $recipient, $mediaUrl, 
            "Here's a test image uploaded via WaSender API! 📸");
            
        // Test 4: Also try sending as document
        $docSent = sendDocumentMessage($client, $baseUrl, $apiKey, $recipient, $mediaUrl, 
            "cards.png", "Here's the same file sent as a document! 📄");
    }
    
    // Summary
    echo "=== Test Summary ===\n";
    echo "📱 Recipient: $recipient\n";
    echo "💬 Text Message: " . ($textSent ? "✅ Sent" : "❌ Failed") . "\n";
    echo "🖼️ Image Upload: " . ($mediaUrl ? "✅ Success" : "❌ Failed") . "\n";
    echo "📸 Image Message: " . (isset($imageSent) && $imageSent ? "✅ Sent" : "❌ Failed/Skipped") . "\n";
    echo "📄 Document Message: " . (isset($docSent) && $docSent ? "✅ Sent" : "❌ Failed/Skipped") . "\n";
    echo "\n🎉 Testing completed!\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}