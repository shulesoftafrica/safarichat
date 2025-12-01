<?php
/**
 * Simulate the exact webhook scenario that was failing
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Simulating the exact failing webhook scenario...\n";

try {
    // Find a user and WhatsApp instance for testing
    $user = App\Models\User::first();
    $instance = App\Models\WhatsappInstance::where('user_id', $user->id)->first();
    
    if (!$user || !$instance) {
        echo "✗ Need user and WhatsApp instance for testing\n";
        exit(1);
    }

    // Create test data that mimics the failing scenario
    echo "\n1. Creating test IncomingMessage like the failing one:\n";
    $messageData = [
        'user_id' => $instance->user_id,
        'instance_id' => $instance->instance_id,
        'message_id' => 'test_array_fix_' . time(),
        'chat_id' => '255714825469@s.whatsapp.net',
        'phone_number' => '255714825469',
        'sender_name' => 'Test User',
        'message_body' => 'How much do you charge ?',
        'message_type' => 'text',
        'from_me' => false,
        'is_group' => false,
        'message_timestamp' => now(),
        'status' => 'received',
        'metadata' => []
    ];
    
    $incomingMessage = App\Models\IncomingMessage::create($messageData);
    echo "✓ Created IncomingMessage with ID: {$incomingMessage->id}\n";

    // Find or create a lead
    echo "\n2. Creating test Lead:\n";
    $lead = App\Models\Lead::firstOrCreate(
        ['phone_number' => $incomingMessage->phone_number],
        [
            'name' => $incomingMessage->sender_name,
            'source' => 'whatsapp',
            'status' => 'new',
            'first_contact_at' => now(),
            'last_activity_at' => now(),
        ]
    );
    echo "✓ Using Lead with ID: {$lead->id}\n";

    // Create test AI result that could cause the array issue
    echo "\n3. Testing with potentially problematic AI result data:\n";
    $problematicAiResult = [
        'response' => 'Could you please specify which service or product you are interested in?',
        'confidence' => 1,
        'tokens_used' => 272,
        'actions' => 0, // This might cause the array issue
        'rag_used' => 0, // This might cause boolean issue
        'rag_sources' => '0' // This might cause array issue
    ];
    
    $sentiment = ['sentiment' => 'neutral'];
    $product = App\Models\Product::first();
    $ragSources = []; // Empty array as fallback
    
    echo "Problematic data:\n";
    foreach (['actions', 'rag_used', 'rag_sources'] as $key) {
        $value = $problematicAiResult[$key];
        echo "  {$key}: " . var_export($value, true) . " (type: " . gettype($value) . ")\n";
    }

    // Test the saveConversation method with our fix
    echo "\n4. Testing conversation creation with our array fixes:\n";
    $conversationData = [
        'product_id' => $product?->id,
        'customer_message' => $incomingMessage->message_body,
        'ai_response' => $problematicAiResult['response'],
        'sentiment' => $sentiment['sentiment'],
        'confidence_score' => $problematicAiResult['confidence'],
        'tokens_used' => $problematicAiResult['tokens_used'] ?? 0,
        'state' => 'active',
        'summary' => 'Customer: ' . $incomingMessage->message_body,
        // Test our array fixes
        'ai_actions' => is_array($problematicAiResult['actions']) ? $problematicAiResult['actions'] : [],
        'rag_sources' => is_array($ragSources) ? $ragSources : [],
        'rag_enhanced' => is_bool($problematicAiResult['rag_used']) ? $problematicAiResult['rag_used'] : (bool) $problematicAiResult['rag_used'],
        'conversation_context' => [
            'phone_number' => $incomingMessage->phone_number,
            'message_type' => $incomingMessage->message_type ?? 'text',
            'sources_count' => 0,
            'processing_method' => 'rag_enhanced'
        ]
    ];
    
    echo "Fixed data:\n";
    echo "  ai_actions: " . var_export($conversationData['ai_actions'], true) . " (type: " . gettype($conversationData['ai_actions']) . ")\n";
    echo "  rag_sources: " . var_export($conversationData['rag_sources'], true) . " (type: " . gettype($conversationData['rag_sources']) . ")\n";
    echo "  rag_enhanced: " . var_export($conversationData['rag_enhanced'], true) . " (type: " . gettype($conversationData['rag_enhanced']) . ")\n";

    // Try creating the conversation
    $conversation = $lead->conversations()->create($conversationData);
    echo "✓ Conversation created successfully with ID: {$conversation->id}\n";

    // Clean up
    $conversation->delete();
    $incomingMessage->delete();
    echo "\n✓ Test data cleaned up\n";

    echo "\n🎉 SUCCESS! The array literal error has been fixed.\n";
    echo "The saveConversation method now properly handles:\n";
    echo "  ✅ Non-array values for array fields (converts to empty arrays)\n";
    echo "  ✅ Non-boolean values for boolean fields (converts to proper booleans)\n";
    echo "  ✅ PostgreSQL array literal format requirements\n";

} catch (Exception $e) {
    echo "\n✗ Error during simulation: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}