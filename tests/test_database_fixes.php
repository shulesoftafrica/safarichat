<?php
/**
 * Test script to verify database fixes
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\BusinessContact;
use App\Models\IncomingMessage;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing database fixes...\n";

// Test 1: BusinessContact query with guest_phone column
echo "\n1. Testing BusinessContact query with guest_phone column:\n";
try {
    $guest = BusinessContact::where('guest_phone', '1234567890')->first();
    echo "✓ BusinessContact query with guest_phone works\n";
} catch (Exception $e) {
    echo "✗ BusinessContact query failed: " . $e->getMessage() . "\n";
}

// Test 2: IncomingMessage status update with valid status
echo "\n2. Testing IncomingMessage status update:\n";
try {
    // Create a test message first
    $message = new IncomingMessage();
    $message->phone_number = '1234567890';
    $message->sender_name = 'Test User';
    $message->message_body = 'Test message';
    $message->message_type = 'text';
    $message->chat_id = 'test_chat';
    $message->status = 'received';
    $message->save();
    
    // Test updating to valid status
    $message->update(['status' => 'processed']);
    echo "✓ IncomingMessage status update to 'processed' works\n";
    
    // Clean up
    $message->delete();
} catch (Exception $e) {
    echo "✗ IncomingMessage status update failed: " . $e->getMessage() . "\n";
}

// Test 3: Check if database constraint still blocks invalid status
echo "\n3. Testing invalid status (should fail):\n";
try {
    $message = new IncomingMessage();
    $message->phone_number = '1234567890';
    $message->sender_name = 'Test User';
    $message->message_body = 'Test message';
    $message->message_type = 'text';
    $message->chat_id = 'test_chat';
    $message->status = 'received';
    $message->save();
    
    // This should fail
    $message->update(['status' => 'needs_attention']);
    echo "✗ Invalid status was accepted (this is unexpected)\n";
    $message->delete();
} catch (Exception $e) {
    echo "✓ Invalid status 'needs_attention' correctly rejected: " . substr($e->getMessage(), 0, 100) . "...\n";
}

echo "\nDatabase fixes test completed.\n";