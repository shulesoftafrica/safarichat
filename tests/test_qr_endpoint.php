<?php
require_once 'vendor/autoload.php';

// Test the unprotected QR generation route
$url = 'http://localhost/safarichat/api/wasender/test-qr-generation';

$data = [
    'session_id' => '456'  // String that should be converted to int
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: " . $response . "\n";

// Try to decode and display formatted response
$response_data = json_decode($response, true);
if ($response_data) {
    echo "\nFormatted Response:\n";
    print_r($response_data);
}
?>