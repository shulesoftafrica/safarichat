<?php

// Test the unified notification API directly with attachment

$data = [
    'schema_name' => 'shulesoft',
    'channel' => 'whatsapp',
    'to' => '+255714825469',
    'message' => 'Test attachment image message',
    'type' => 'wasender',
    'attachment' => base64_encode('https://safarichat.ai/public/images/cards.png'),
    'attachment_type' => 'application/pdf',
    'attachment_name' => 'card.pdf'
];

$json = json_encode($data, JSON_PRETTY_PRINT);
echo "JSON Payload:\n";
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
echo "Response: $response\n";

if ($error) {
    echo "Curl Error: $error\n";
}