<?php
// Test external QR service file saving
echo "<h1>External QR Service File Test</h1>\n";

try {
    // Create directory if it doesn't exist
    $qrDir = __DIR__ . '/storage/qr-codes';
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0755, true);
        echo "<p>Created QR directory: $qrDir</p>\n";
    }
    
    // Test data
    $testData = "Test QR from External Service: " . date('Y-m-d H:i:s');
    $fileName = 'external_test_qr_' . time() . '_' . uniqid() . '.png';
    $filePath = $qrDir . '/' . $fileName;
    $fileUrl = '/storage/qr-codes/' . $fileName;
    
    echo "<p><strong>Test Data:</strong> $testData</p>\n";
    echo "<p><strong>File Path:</strong> $filePath</p>\n";
    echo "<p><strong>File URL:</strong> $fileUrl</p>\n";
    
    // External QR service URL
    $externalUrl = "https://api.qrserver.com/v1/create-qr-code/?size=256x256&format=png&data=" . urlencode($testData);
    echo "<p><strong>External Service URL:</strong> <a href='$externalUrl' target='_blank'>$externalUrl</a></p>\n";
    
    // Fetch image from external service
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'SafariChat QR Generator'
        ]
    ]);
    
    echo "<p>Fetching from external service...</p>\n";
    $imageContent = file_get_contents($externalUrl, false, $context);
    
    if ($imageContent) {
        echo "<p style='color: green;'><strong>✓ Successfully fetched image from external service!</strong></p>\n";
        echo "<p>Downloaded size: " . number_format(strlen($imageContent)) . " bytes</p>\n";
        
        // Save to file
        $saved = file_put_contents($filePath, $imageContent);
        
        if ($saved) {
            echo "<p style='color: green;'><strong>✓ Successfully saved QR code to file!</strong></p>\n";
            echo "<p>Saved file size: " . number_format($saved) . " bytes</p>\n";
            echo "<p>File exists: " . (file_exists($filePath) ? 'Yes' : 'No') . "</p>\n";
            
            // Get image info
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                echo "<p><strong>Image properties:</strong> {$imageInfo[0]}x{$imageInfo[1]} pixels, {$imageInfo['mime']}</p>\n";
            }
            
            // Display the QR code
            echo "<div style='text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;'>\n";
            echo "<h2>Generated QR Code:</h2>\n";
            echo "<img src='$fileUrl?t=" . time() . "' style='border: 3px solid #25D366; border-radius: 15px; max-width: 300px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);' alt='Generated QR Code' onload='console.log(\"QR image loaded successfully\")' onerror='console.error(\"QR image failed to load\")'>\n";
            echo "<p style='margin-top: 15px; color: #666;'>Scan this QR code to test!</p>\n";
            echo "</div>\n";
            
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<strong>✅ SUCCESS!</strong> The external QR service method is working perfectly.\n";
            echo "</div>\n";
            
        } else {
            echo "<p style='color: red;'><strong>✗ Failed to save file to: $filePath</strong></p>\n";
            echo "<p>Check directory permissions and disk space.</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'><strong>✗ Failed to fetch image from external service</strong></p>\n";
        
        // Check if URL is accessible
        $headers = get_headers($externalUrl, 1);
        echo "<p><strong>Response headers:</strong></p>\n";
        echo "<pre>" . print_r($headers, true) . "</pre>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> {$e->getMessage()}</p>\n";
    echo "<pre>{$e->getTraceAsString()}</pre>\n";
}

echo "<hr>\n";
echo "<h2>Test Laravel Controller Method</h2>\n";
echo "<p>You can now test the full Laravel implementation by:</p>\n";
echo "<ol>\n";
echo "<li>Going to <a href='/wasender' target='_blank'>/wasender</a> (requires authentication)</li>\n";
echo "<li>Entering a phone number</li>\n";
echo "<li>Clicking 'Generate QR Code'</li>\n";
echo "</ol>\n";
echo "<p>The controller should now use the external service fallback and save images like this test.</p>\n";
?>