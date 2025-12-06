<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WaSenderService;

$wasenderService = new WaSenderService();

try {
    echo "Testing simple text message through Laravel...\n";
    
    $result = $wasenderService->sendTextMessage('0714825469', 'Test message from Laravel WaSenderService');
    
    echo "Text Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Testing attachment message through Laravel...\n";
    
    // Use an existing PDF file from storage
    $testFilePath = 'storage/uploads/Clouds Media Rate Card II 2025.pdf';
    
    if (!file_exists($testFilePath)) {
        echo "PDF file not found, creating a minimal valid PDF...\n";
        
        // Create a minimal valid PDF file
        $pdfContent = '%PDF-1.4
1 0 obj
<</Type/Catalog/Pages 2 0 R>>
endobj
2 0 obj
<</Type/Pages/Kids[ 6 0 R ]/Count 1>>
endobj
3 0 obj
<</CreationDate(D:20251205151433)/Producer(PDFTron Library V.1.13.4.2)/Creator()/Title()/Author()/Subject()>>
endobj
4 0 obj
<</BaseFont/Phi/Type/Font/Subtype/TrueType>>
endobj
5 0 obj
<</BaseFont/Minion-Regular/Type/Font/Subtype/TrueType>>
endobj
6 0 obj
<</Type/Page/Parent 2 0 R/Resources<</ProcSet[/PDF/Text]/Font<</F1 4 0 R/F2 5 0 R>>>>/MediaBox[0 0 612 792]/Contents 7 0 R>>
endobj
7 0 obj
<</Length 44>>
stream
BT
/F1 12 Tf
72 720 Td
(Test Document) Tj
ET
endstream
endobj
xref
0 8
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000251 00000 n 
0000000305 00000 n 
0000000368 00000 n 
0000000526 00000 n 
trailer
<</Size 8/Root 1 0 R>>
startxref
620
%%EOF';
        
        if (!is_dir('storage/temp')) {
            mkdir('storage/temp', 0755, true);
        }
        $testFilePath = 'storage/temp/test.pdf';
        file_put_contents($testFilePath, $pdfContent);
    }
    
    $attachmentResult = $wasenderService->sendDocument('0714825469', $testFilePath, 'Test attachment message');
    
    echo "Attachment Result: " . json_encode($attachmentResult, JSON_PRETTY_PRINT) . "\n";
    
    // Clean up test file
    if (file_exists($testFilePath)) {
        unlink($testFilePath);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}