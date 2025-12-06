<?php
/**
 * WaSender API - PDF Document Sending Test
 * Sends a PDF file via WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Configuration
$apiKey = 'de042e1a46b394de63bed34c5b2d9c55108db5061b075b29ce9225be30d7cca2';
$baseUrl = 'https://www.wasenderapi.com/api';
$recipient = '+255714825469';
$pdfFile = __DIR__ . '/../storage/uploads/card.pdf';

// Initialize HTTP client
$client = new Client();

echo "=== WaSender API - PDF Document Test ===\n";
echo "📱 Recipient: $recipient\n";
echo "📄 PDF File: $pdfFile\n\n";

// Check if file exists
if (!file_exists($pdfFile)) {
    die("❌ Error: PDF file not found at $pdfFile\n");
}

echo "📁 File: " . basename($pdfFile) . "\n";
echo "📏 Size: " . formatBytes(filesize($pdfFile)) . "\n";
echo "🎭 MIME: " . mime_content_type($pdfFile) . "\n\n";

try {
    // Step 1: Upload the PDF file using binary method (more memory efficient)
    echo "--- Step 1: Uploading PDF File ---\n";
    echo "⬆️ Uploading...\n";
    
    $mimeType = mime_content_type($pdfFile);
    
    $uploadResponse = $client->post("$baseUrl/upload", [
        'headers' => [
            'Authorization' => "Bearer $apiKey",
            'Content-Type' => $mimeType
        ],
        'body' => fopen($pdfFile, 'r')
    ]);
    
    $uploadResult = json_decode($uploadResponse->getBody(), true);
    
    if (!$uploadResult['success']) {
        die("❌ Upload failed: " . json_encode($uploadResult) . "\n");
    }
    
    $documentUrl = $uploadResult['publicUrl'];
    echo "✅ Upload successful!\n";
    echo "🔗 Public URL: $documentUrl\n\n";
    
    // Step 2: Send the document message
    echo "--- Step 2: Sending PDF Document Message ---\n";
    echo "📤 Sending...\n";
    
    $messageResponse = $client->post("$baseUrl/send-message", [
        'headers' => [
            'Authorization' => "Bearer $apiKey",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'json' => [
            'to' => $recipient,
            'text' => 'Quarterly report',
            'documentUrl' => $documentUrl
        ]
    ]);
    
    $messageResult = json_decode($messageResponse->getBody(), true);
    
    if ($messageResult['success']) {
        echo "✅ Document message sent successfully!\n";
        echo "🆔 Message ID: " . $messageResult['data']['msgId'] . "\n";
        echo "📞 JID: " . $messageResult['data']['jid'] . "\n";
        echo "📊 Status: " . $messageResult['data']['status'] . "\n\n";
        
        echo "=== Test Summary ===\n";
        echo "📱 Recipient: $recipient\n";
        echo "📄 Document: " . basename($pdfFile) . "\n";
        echo "🔗 URL: $documentUrl\n";
        echo "✅ Status: Sent successfully!\n";
    } else {
        echo "❌ Message failed: " . json_encode($messageResult) . "\n";
    }
    
} catch (RequestException $e) {
    echo "❌ Request failed: " . $e->getMessage() . "\n";
    if ($e->hasResponse()) {
        echo "Response: " . $e->getResponse()->getBody() . "\n";
    }
}

/**
 * Format file size
 */
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}