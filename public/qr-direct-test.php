<?php
// Direct test of QR code file generation
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Test SimpleSoftwareIO QR code generation and file saving
    echo "<h1>QR Code File Generation Test</h1>\n";
    
    // Generate test data
    $testData = "Test QR Code: " . date('Y-m-d H:i:s');
    $fileName = 'test_qr_' . time() . '_' . uniqid() . '.png';
    $filePath = __DIR__ . '/storage/qr-codes/' . $fileName;
    $fileUrl = '/storage/qr-codes/' . $fileName;
    
    echo "<p><strong>Test Data:</strong> $testData</p>\n";
    echo "<p><strong>File Path:</strong> $filePath</p>\n";
    echo "<p><strong>File URL:</strong> $fileUrl</p>\n";
    
    // Test if SimpleSoftwareIO is available
    if (class_exists('SimpleSoftwareIO\QrCode\Generator')) {
        $qrGenerator = new SimpleSoftwareIO\QrCode\Generator();
        $qrCode = $qrGenerator->format('png')->size(256)->margin(2)->generate($testData);
        
        // Save to file
        $saved = file_put_contents($filePath, $qrCode);
        
        if ($saved) {
            echo "<p style='color: green;'><strong>✓ QR Code generated and saved successfully!</strong></p>\n";
            echo "<p>File size: " . number_format($saved) . " bytes</p>\n";
            echo "<p>File exists: " . (file_exists($filePath) ? 'Yes' : 'No') . "</p>\n";
            
            // Display the QR code
            echo "<div style='text-align: center; margin: 20px 0;'>\n";
            echo "<h2>Generated QR Code:</h2>\n";
            echo "<img src='$fileUrl?t=" . time() . "' style='border: 2px solid #25D366; border-radius: 10px; max-width: 300px;' alt='Generated QR Code'>\n";
            echo "</div>\n";
            
            // Test image properties
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                echo "<p><strong>Image properties:</strong> {$imageInfo[0]}x{$imageInfo[1]} pixels, {$imageInfo['mime']}</p>\n";
            }
            
        } else {
            echo "<p style='color: red;'><strong>✗ Failed to save QR code to file</strong></p>\n";
        }
        
    } else if (class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode')) {
        echo "<p>Using Facade...</p>\n";
        
        // This might not work in direct PHP without Laravel context
        try {
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(256)->generate($testData);
            $saved = file_put_contents($filePath, $qrCode);
            
            if ($saved) {
                echo "<p style='color: green;'><strong>✓ QR Code generated with Facade and saved!</strong></p>\n";
                echo "<img src='$fileUrl?t=" . time() . "' style='border: 2px solid #25D366; max-width: 300px;' alt='Generated QR Code'>\n";
            } else {
                echo "<p style='color: red;'><strong>✗ Failed to save Facade QR code</strong></p>\n";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'><strong>Facade method failed:</strong> {$e->getMessage()}</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'><strong>✗ SimpleSoftwareIO QR code library not found</strong></p>\n";
    }
    
    echo "<hr>\n";
    echo "<h2>Fallback Test - External Service</h2>\n";
    
    // Test external service fallback
    $externalUrl = "https://api.qrserver.com/v1/create-qr-code/?size=256x256&format=png&data=" . urlencode($testData);
    echo "<p><strong>External URL:</strong> $externalUrl</p>\n";
    
    $context = stream_context_create([
        'http' => ['timeout' => 10]
    ]);
    
    $imageContent = file_get_contents($externalUrl, false, $context);
    
    if ($imageContent) {
        $externalFileName = 'external_qr_' . time() . '_' . uniqid() . '.png';
        $externalFilePath = __DIR__ . '/storage/qr-codes/' . $externalFileName;
        $externalFileUrl = '/storage/qr-codes/' . $externalFileName;
        
        $saved = file_put_contents($externalFilePath, $imageContent);
        
        if ($saved) {
            echo "<p style='color: green;'><strong>✓ External QR service working and saved!</strong></p>\n";
            echo "<p>File size: " . number_format($saved) . " bytes</p>\n";
            echo "<div style='text-align: center; margin: 20px 0;'>\n";
            echo "<h3>External QR Code:</h3>\n";
            echo "<img src='$externalFileUrl?t=" . time() . "' style='border: 2px solid #007bff; border-radius: 10px; max-width: 300px;' alt='External QR Code'>\n";
            echo "</div>\n";
        } else {
            echo "<p style='color: red;'><strong>✗ Failed to save external QR code</strong></p>\n";
        }
    } else {
        echo "<p style='color: red;'><strong>✗ Failed to fetch from external QR service</strong></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> {$e->getMessage()}</p>\n";
    echo "<pre>{$e->getTraceAsString()}</pre>\n";
}
?>