<?php

// Test text message first (no attachment)

$data = [
    'schema_name' => 'safari_chat',
    'channel' => 'whatsapp',
    'to' => '0714825469@s.whatsapp.net',
    'message' => 'Test text message without attachment',
    'provider' => 'whatsapp'
];

$json = json_encode($data, JSON_PRETTY_PRINT);
echo "JSON Payload (Text Only):\n";
echo $json . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://notifications.shulesoft.africa/api/notifications/send');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($error) {
    echo "Curl Error: $error\n";
}

// Now test with attachment
echo "=== Testing with attachment ===\n";

$dataWithAttachment = [
    'schema_name' => 'safari_chat',
    'channel' => 'whatsapp',
    'to' => '0714825469@s.whatsapp.net',
    'message' => 'Test attachment message',
    'provider' => 'whatsapp',
    'attachment' => 'JVBERi0xLjQNCjEgMCBvYmoNCjw8L1R5cGUvQ2F0YWxvZy9QYWdlcyAyIDAgUj4+DQplbmRvYmoNCjIgMCBvYmoNCjw8L1R5cGUvUGFnZXMvS2lkc1sgNiAwIFIgXS9Db3VudCAxPj4NCmVuZG9iag0K',
    'attachment_type' => 'application/pdf',
    'attachment_name' => 'test.pdf'
];

$jsonWithAttachment = json_encode($dataWithAttachment, JSON_PRETTY_PRINT);
echo "JSON Payload (With Attachment):\n";
echo $jsonWithAttachment . "\n\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'https://notifications.shulesoft.africa/api/notifications/send');
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $jsonWithAttachment);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn'
]);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$error2 = curl_error($ch2);
curl_close($ch2);

echo "HTTP Code: $httpCode2\n";
echo "Response: $response2\n";

if ($error2) {
    echo "Curl Error: $error2\n";
}