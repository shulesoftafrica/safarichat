<?php
require_once 'vendor/autoload.php';

// Test session creation via API
$url = 'http://localhost/safarichat/api/wasender/sessions/create';

$data = [
    'instance_name' => 'test_qr_generation_' . time(),
    'webhook_events' => ['messages.received', 'session.status', 'messages.update'],
    'webhook_url' => 'https://example.com/webhook'
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
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: " . $response . "\n";

// If session was created, try to get QR code
if ($http_code == 200 || $http_code == 201) {
    $session_data = json_decode($response, true);
    if (isset($session_data['instance_id']) || isset($session_data['id'])) {
        $session_id = $session_data['instance_id'] ?? $session_data['id'];
        echo "\nTrying to get QR code for session: $session_id\n";
        
        // Test QR code generation
        $qr_url = "http://localhost/safarichat/api/wasender/sessions/$session_id/qrcode";
        
        $ch2 = curl_init();
        curl_setopt_array($ch2, [
            CURLOPT_URL => $qr_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ]
        ]);
        
        $qr_response = curl_exec($ch2);
        $qr_http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        echo "QR HTTP Code: $qr_http_code\n";
        echo "QR Response: " . $qr_response . "\n";
    }
}
?>