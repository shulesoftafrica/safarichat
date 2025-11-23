<?php
// Direct QR code test without Laravel routing
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Test SimpleSoftwareIO library directly
    if (class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode')) {
        echo "SimpleSoftwareIO library not available in direct context\n";
    }
    
    // Test with direct QrCode class
    if (class_exists('SimpleSoftwareIO\QrCode\Generator')) {
        $qrGenerator = new SimpleSoftwareIO\QrCode\Generator();
        $qrCode = $qrGenerator->format('png')->size(256)->margin(2)->generate('Test QR Code Direct');
        $base64 = base64_encode($qrCode);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'method' => 'SimpleSoftwareIO Direct',
            'qr_code' => 'data:image/png;base64,' . $base64,
            'qr_length' => strlen($base64),
            'library_available' => true
        ]);
    } else {
        // Fallback to external QR service
        $qrText = 'Test QR Code Fallback';
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=256x256&format=png&data=" . urlencode($qrText);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10
            ]
        ]);
        
        $imageContent = file_get_contents($qrCodeUrl, false, $context);
        if ($imageContent) {
            $base64 = base64_encode($imageContent);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'method' => 'External QR Service',
                'qr_code' => 'data:image/png;base64,' . $base64,
                'qr_length' => strlen($base64),
                'library_available' => false
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate QR code using external service'
            ]);
        }
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>