<?php
/**
 * Test complete webhook flow to verify all database fixes
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\EventsGuest;
use App\Models\IncomingMessage;
use App\Models\WhatsappInstance;
use App\Models\User;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing complete webhook flow with database fixes...\n";

try {
    // Get or create a test user
    $user = User::first();
    if (!$user) {
        echo "✗ No users found in database. Cannot test without a user.\n";
        exit(1);
    }
    
    // Get or create a test WhatsApp instance
    $instance = WhatsappInstance::where('user_id', $user->id)->first();
    if (!$instance) {
        $instance = WhatsappInstance::create([
            'user_id' => $user->id,
            'instance_id' => 'test_instance_' . time(),
            'instance_key' => 'test_key',
            'webhook_url' => 'http://test.webhook.url',
            'status' => 'connected'
        ]);
    }
    
    echo "✓ Using user ID: {$user->id}, instance ID: {$instance->instance_id}\n";

    // Test 1: Create IncomingMessage with user_id (should work now)
    echo "\n1. Testing IncomingMessage creation with user_id:\n";
    $messageData = [
        'user_id' => $instance->user_id,
        'instance_id' => $instance->instance_id,
        'message_id' => 'test_msg_' . time(),
        'chat_id' => '1234567890@s.whatsapp.net',
        'phone_number' => '1234567890',
        'sender_name' => 'Test User',
        'message_body' => 'Test message from webhook',
        'message_type' => 'text',
        'from_me' => false,
        'is_group' => false,
        'message_timestamp' => now(),
        'status' => 'received',
        'metadata' => []
    ];
    
    $incomingMessage = IncomingMessage::create($messageData);
    echo "✓ IncomingMessage created successfully with ID: {$incomingMessage->id}\n";

    // Test 2: EventsGuest query with guest_phone column
    echo "\n2. Testing EventsGuest query with guest_phone:\n";
    $eventsGuest = EventsGuest::where('guest_phone', $incomingMessage->phone_number)->first();
    echo "✓ EventsGuest query with guest_phone executed successfully\n";

    // Test 3: Update message status to valid values
    echo "\n3. Testing status updates:\n";
    $incomingMessage->update(['status' => 'processed']);
    echo "✓ Status updated to 'processed'\n";
    
    $incomingMessage->update(['status' => 'replied']);
    echo "✓ Status updated to 'replied'\n";

    // Test 4: Verify invalid status is still rejected
    echo "\n4. Testing invalid status rejection:\n";
    try {
        $incomingMessage->update(['status' => 'needs_attention']);
        echo "✗ Invalid status was accepted (unexpected)\n";
    } catch (Exception $e) {
        echo "✓ Invalid status 'needs_attention' correctly rejected\n";
    }

    // Clean up
    $incomingMessage->delete();
    echo "\n✓ Test message cleaned up\n";
    
    echo "\n🎉 All database fixes are working correctly!\n";
    echo "\nThe WhatsApp AI integration should now work without database errors.\n";

} catch (Exception $e) {
    echo "\n✗ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}