<?php

// Test the unified notification API directly with attachment

$data = [
    'schema_name' => 'safari_chat',
    'channel' => 'whatsapp',
    'to' => '0714825469@s.whatsapp.net',
    'message' => 'Test attachment message',
    'provider' => 'whatsapp',
    'attachment' => 'JVBERi0xLjQNCjEgMCBvYmoNCjw8L1R5cGUvQ2F0YWxvZy9QYWdlcyAyIDAgUj4+DQplbmRvYmoNCjIgMCBvYmoNCjw8L1R5cGUvUGFnZXMvS2lkc1sgNiAwIFIgXS9Db3VudCAxPj4NCmVuZG9iag0KMyAwIG9iag0KPDwvQ3JlYXRpb25EYXRlKEQ6MjAyNTAzMjIxNTE0MzMpL1Byb2R1Y2VyKFBERlRyb24gTGlicmFyeSBWLjEuMTMuNC4yKS9DcmVhdG9yKCkvVGl0bGUoKS9BdXRob3IoKS9TdWJqZWN0KCk+Pg0KZW5kb2JqDQo0IDAgb2JqDQo8PC9CYXNlRm9udC9QaGkvVHlwZS9Gb250L1N1YnR5cGUvVHJ1ZVR5cGU+Pg0KZW5kb2JqDQo1IDAgb2JqDQo8PC9CYXNlRm9udC9NaW5pb24tUmVndWxhci9UeXBlL0ZvbnQvU3VidHlwZS9UcnVlVHlwZT4+DQplbmRvYmoNCjYgMCBvYmoNCjw8L1R5cGUvUGFnZS9QYXJlbnQgMiAwIFIvUmVzb3VyY2VzPDwvUHJvY1NldFs+',
    'attachment_type' => 'application/pdf',
    'attachment_name' => 'test.pdf'
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