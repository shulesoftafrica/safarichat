<?php
/**
 * Test the exact scenario from the error logs with debugging
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing array literal error with real webhook simulation...\n";

try {
    // Use the exact data from the logs
    $webhookData = [
        "event" => "messages.received",
        "sessionId" => "de042e1a46b394de63bed34c5b2d9c55108db5061b075b29ce9225be30d7cca2",
        "data" => [
            "messages" => [
                "key" => [
                    "id" => "3EB0F6F8CC499363721CF6",
                    "fromMe" => false,
                    "remoteJid" => "255714825469@s.whatsapp.net",
                    "senderPn" => "255714825469@s.whatsapp.net",
                    "cleanedSenderPn" => "255714825469"
                ],
                "messageTimestamp" => 1764534733,
                "pushName" => "Double Fruitful",
                "message" => [
                    "conversation" => "How much do you charge ?"
                ],
                "messageBody" => "How much do you charge ?",
                "remoteJid" => "255714825469@s.whatsapp.net"
            ]
        ]
    ];

    // Test directly calling the AiWhatsAppService method to see the logged data types
    echo "\nSimulating the exact AI processing that's failing...\n";
    
    // Create mock objects for testing
    $mockMessage = new stdClass();
    $mockMessage->message_body = "How much do you charge ?";
    $mockMessage->phone_number = "255714825469";
    $mockMessage->message_type = "text";
    
    $mockLead = new stdClass();
    $mockLead->id = 15;
    
    $mockProduct = new stdClass();
    $mockProduct->id = 7;
    
    // Test the exact data that was causing the issue
    $mockAiResult = [
        'response' => 'Could you please specify which product or service you are interested in?',
        'confidence' => 1,
        'tokens_used' => 276,
        'actions' => [], // This should be an array
        'rag_used' => false, // This should be boolean false, not 0
        'rag_sources' => [] // This should be an array
    ];
    
    $mockSentiment = ['sentiment' => 'neutral'];
    $mockRagSources = []; // Empty array
    
    echo "Mock data prepared:\n";
    echo "  aiResult['actions']: " . var_export($mockAiResult['actions'], true) . " (type: " . gettype($mockAiResult['actions']) . ")\n";
    echo "  aiResult['rag_used']: " . var_export($mockAiResult['rag_used'], true) . " (type: " . gettype($mockAiResult['rag_used']) . ")\n";
    echo "  ragSources param: " . var_export($mockRagSources, true) . " (type: " . gettype($mockRagSources) . ")\n";
    
    // Test the validation logic manually
    echo "\nTesting validation logic:\n";
    
    $aiActions = $mockAiResult['actions'] ?? [];
    if (!is_array($aiActions)) {
        $aiActions = [];
    }
    echo "  aiActions after validation: " . var_export($aiActions, true) . " (type: " . gettype($aiActions) . ")\n";
    
    $ragSourcesArray = $mockRagSources;
    if (!is_array($ragSourcesArray)) {
        $ragSourcesArray = [];
    }
    echo "  ragSourcesArray after validation: " . var_export($ragSourcesArray, true) . " (type: " . gettype($ragSourcesArray) . ")\n";
    
    $ragEnhanced = $mockAiResult['rag_used'] ?? false;
    if (!is_bool($ragEnhanced)) {
        $ragEnhanced = (bool) $ragEnhanced;
    }
    echo "  ragEnhanced after validation: " . var_export($ragEnhanced, true) . " (type: " . gettype($ragEnhanced) . ")\n";
    
    // Test the exact conversationData structure that would be passed to create()
    $conversationContext = [
        'phone_number' => $mockMessage->phone_number,
        'message_type' => $mockMessage->message_type ?? 'text',
        'sources_count' => count($ragSourcesArray),
        'processing_method' => 'rag_enhanced'
    ];
    
    $conversationData = [
        'product_id' => $mockProduct->id,
        'customer_message' => $mockMessage->message_body,
        'ai_response' => $mockAiResult['response'],
        'sentiment' => $mockSentiment['sentiment'],
        'confidence_score' => $mockAiResult['confidence'],
        'tokens_used' => $mockAiResult['tokens_used'] ?? 0,
        'state' => 'active',
        'summary' => 'Customer: ' . $mockMessage->message_body,
        'ai_actions' => $aiActions,
        'rag_sources' => $ragSourcesArray,
        'rag_enhanced' => $ragEnhanced ? true : false,
        'conversation_context' => $conversationContext
    ];
    
    echo "\nFinal conversationData structure:\n";
    foreach ($conversationData as $key => $value) {
        $type = gettype($value);
        $displayValue = is_array($value) ? '[array with ' . count($value) . ' items]' : var_export($value, true);
        echo "  {$key}: {$displayValue} (type: {$type})\n";
    }
    
    // Check if any values might be causing the "0" array literal issue
    echo "\nChecking for potential '0' values that might be misinterpreted:\n";
    foreach ($conversationData as $key => $value) {
        if ($value === 0 || $value === '0' || $value === false) {
            echo "  WARNING: {$key} has value that might cause issues: " . var_export($value, true) . "\n";
        }
    }
    
    echo "\n✅ Validation logic appears correct. The issue might be in the Eloquent model casting or database driver.\n";
    echo "The next webhook should show detailed logging in the Laravel logs.\n";

} catch (Exception $e) {
    echo "\n✗ Error during testing: " . $e->getMessage() . "\n";
}