<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\OpenAiService;

echo "🧪 Testing OpenAI Error Case Resolution\n";
echo "=====================================\n\n";

// The exact message that caused the error
$problematicMessage = "The system of fee collection and other contributions";

echo "📋 Testing Problematic Message:\n";
echo "Original: '$problematicMessage'\n";
echo "Length: " . strlen($problematicMessage) . " bytes\n";
echo "UTF-8 valid: " . (mb_check_encoding($problematicMessage, 'UTF-8') ? 'Yes' : 'No') . "\n";

// Check for any hidden characters
$hexDump = bin2hex($problematicMessage);
echo "Hex dump: $hexDump\n\n";

// Test the OpenAI service sanitization
$openAiService = new OpenAiService();

// Use reflection to access the private sanitizeText method
$reflection = new ReflectionClass($openAiService);
$sanitizeMethod = $reflection->getMethod('sanitizeText');
$sanitizeMethod->setAccessible(true);

$sanitizedMessage = $sanitizeMethod->invoke($openAiService, $problematicMessage);

echo "🧽 After sanitization:\n";
echo "Cleaned: '$sanitizedMessage'\n";
echo "Length: " . strlen($sanitizedMessage) . " bytes\n";
echo "UTF-8 valid: " . (mb_check_encoding($sanitizedMessage, 'UTF-8') ? 'Yes' : 'No') . "\n";
echo "Changed: " . ($problematicMessage !== $sanitizedMessage ? 'Yes' : 'No') . "\n\n";

// Test with various potential encoding issues
$testCases = [
    "The system of fee collection\x00 and other contributions", // null byte
    "The system of fee collection\x01 and other contributions", // control char
    "The system of fee collection\x1A and other contributions", // substitute char
    "The system\xEF\xBB\xBF of fee collection", // BOM
];

foreach ($testCases as $i => $testCase) {
    echo "Test case " . ($i + 1) . ":\n";
    echo "Original: '" . $testCase . "'\n";
    echo "Original hex: " . bin2hex($testCase) . "\n";
    
    $cleaned = $sanitizeMethod->invoke($openAiService, $testCase);
    echo "Cleaned: '$cleaned'\n";
    echo "Cleaned hex: " . bin2hex($cleaned) . "\n";
    echo "UTF-8 valid: " . (mb_check_encoding($cleaned, 'UTF-8') ? 'Yes' : 'No') . "\n\n";
}

echo "✅ Error Case Testing Complete!\n";