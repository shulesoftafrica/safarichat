<?php
// Test the base64 QR generation logic

echo "Testing base64 QR code generation...\n";

$sessionId = "test_session_" . time();
$qrText = "1@" . time() . "," . $sessionId . ",mock_server_token," . substr(md5($sessionId . time()), 0, 16);

echo "Session ID: {$sessionId}\n";
echo "QR Text: {$qrText}\n";

// Use external QR code generation service
$externalQRUrl = "https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=" . urlencode($qrText);

echo "External QR URL: {$externalQRUrl}\n";

try {
    $qrImageContent = file_get_contents($externalQRUrl);
    if ($qrImageContent !== false) {
        $base64QR = base64_encode($qrImageContent);
        echo "SUCCESS!\n";
        echo "Image size: " . strlen($qrImageContent) . " bytes\n";
        echo "Base64 length: " . strlen($base64QR) . " characters\n";
        echo "Base64 starts with: " . substr($base64QR, 0, 50) . "...\n";
        echo "Data URL format: data:image/png;base64," . substr($base64QR, 0, 50) . "...\n";
    } else {
        echo "ERROR: Failed to download QR code\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>