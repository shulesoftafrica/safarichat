<?php

/**
 * Test AI WhatsApp Integration
 * 
 * This script tests the complete flow:
 * 1. Simulates webhook receiving a WhatsApp message
 * 2. Verifies the message is processed with AI
 * 3. Checks that an AI response is generated and sent back
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\Controllers\WaSenderController;
use App\Models\WhatsappInstance;
use App\Models\IncomingMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing AI WhatsApp Integration...\n\n";

try {
    // Test 1: Check if required services are available
    echo "1. Testing service availability...\n";
    
    $aiWhatsAppService = app(\App\Services\AiWhatsAppService::class);
    $waSenderService = app(\App\Services\WaSenderService::class);
    
    echo "   ✅ AiWhatsAppService: Available\n";
    echo "   ✅ WaSenderService: Available\n";
    
    // Test 2: Check if we can create the controller
    echo "\n2. Testing WaSenderController initialization...\n";
    
    $controller = app(WaSenderController::class);
    echo "   ✅ WaSenderController: Created successfully\n";
    
    // Test 3: Find or create test data
    echo "\n3. Setting up test data...\n";
    
    // Find a user for testing
    $testUser = User::first();
    if (!$testUser) {
        echo "   ❌ No users found in database. Please create a user first.\n";
        exit(1);
    }
    echo "   ✅ Test User: {$testUser->name} (ID: {$testUser->id})\n";
    
    // Find or create a WhatsApp instance
    $testInstance = WhatsappInstance::where('user_id', $testUser->id)->first();
    if (!$testInstance) {
        $testInstance = WhatsappInstance::create([
            'user_id' => $testUser->id,
            'instance_id' => 'test_instance_' . time(),
            'instance_name' => 'Test Instance',
            'phone_number' => '+255123456789',
            'status' => 'connected'
        ]);
    }
    echo "   ✅ Test WhatsApp Instance: {$testInstance->instance_name} (ID: {$testInstance->instance_id})\n";
    
    // Test 4: Simulate incoming webhook
    echo "\n4. Testing webhook message processing...\n";
    
    $webhookData = [
        'event' => 'message',
        'type' => 'text',
        'id' => 'test_msg_' . time(),
        'chatId' => '255987654321@c.us',
        'from' => '255987654321@c.us',
        'body' => 'Hello, I am interested in your products. Can you help me?',
        'senderName' => 'Test Customer',
        'messageType' => 'text',
        'fromMe' => false,
        'timestamp' => time(),
        'isGroup' => false
    ];
    
    // Create a mock request
    $request = Request::create('/webhook', 'POST', $webhookData);
    $request->headers->set('Content-Type', 'application/json');
    
    echo "   📞 Simulating webhook for phone: 255987654321\n";
    echo "   💬 Message: \"{$webhookData['body']}\"\n";
    
    // Test 5: Process the webhook
    echo "\n5. Processing message with AI...\n";
    
    $response = $controller->handleWebhook($request, $testInstance->instance_id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
        echo "   ✅ Webhook processing: Success\n";
        echo "   ✅ AI Processing: " . ($responseData['ai_processed'] ? 'Completed' : 'Failed') . "\n";
        echo "   ✅ Response Sent: " . ($responseData['response_sent'] ? 'Yes' : 'No') . "\n";
        
        if (isset($responseData['conversation_id'])) {
            echo "   ✅ Conversation ID: {$responseData['conversation_id']}\n";
        }
    } else {
        echo "   ❌ Webhook processing failed: " . $responseData['message'] . "\n";
    }
    
    // Test 6: Check database records
    echo "\n6. Verifying database records...\n";
    
    $incomingMessage = IncomingMessage::where('phone_number', '255987654321')
        ->where('instance_id', $testInstance->instance_id)
        ->latest()
        ->first();
        
    if ($incomingMessage) {
        echo "   ✅ IncomingMessage created: ID {$incomingMessage->id}\n";
        echo "   📝 Status: {$incomingMessage->status}\n";
        echo "   🤖 Auto Reply: " . ($incomingMessage->auto_reply ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ❌ No IncomingMessage record found\n";
    }
    
    // Test 7: Check outgoing messages
    $outgoingMessages = \App\Models\OutgoingMessage::where('phone_number', '255987654321')
        ->where('is_ai_generated', true)
        ->latest()
        ->get();
        
    if ($outgoingMessages->count() > 0) {
        echo "   ✅ AI-generated outgoing messages: {$outgoingMessages->count()}\n";
        foreach ($outgoingMessages as $msg) {
            echo "   📤 Message: " . substr($msg->message_body, 0, 100) . "...\n";
            echo "   📊 Status: {$msg->status}\n";
        }
    } else {
        echo "   ⚠️  No AI-generated outgoing messages found\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 AI WhatsApp Integration Test Completed!\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "\nSummary:\n";
    echo "- Services: ✅ Available\n";
    echo "- Controller: ✅ Working\n";
    echo "- Webhook Processing: " . ($responseData['success'] ? '✅ Success' : '❌ Failed') . "\n";
    echo "- AI Integration: " . (($responseData['ai_processed'] ?? false) ? '✅ Working' : '⚠️ Needs Review') . "\n";
    echo "- Message Storage: " . ($incomingMessage ? '✅ Working' : '❌ Failed') . "\n";
    echo "- Response Sending: " . (($responseData['response_sent'] ?? false) ? '✅ Working' : '⚠️ Needs Review') . "\n";

} catch (\Exception $e) {
    echo "\n❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "🔍 Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest complete.\n";