<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Guest;
use App\Models\WhatsappInstance;
use App\Models\IncomingMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IncomingMessageProcessingTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $whatsappInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
        
        // Create a WhatsApp instance for the user
        $this->whatsappInstance = WhatsappInstance::create([
            'user_id' => $this->user->id,
            'instance_id' => 'test_instance_123',
            'access_token' => 'test_token_456',
            'connect_status' => 'Connected',
            'webhook_verified' => true,
            'name' => 'Test Instance'
        ]);
    }

    public function test_webhook_can_process_incoming_message()
    {
        // Simulate a webhook payload from WAAPI
        $webhookPayload = [
            'event' => 'message',
            'data' => [
                'id' => 'msg_test_123',
                'chatId' => '255712345678@c.us',
                'body' => 'Hello, this is a test message',
                'type' => 'text',
                'timestamp' => now()->timestamp,
                'from' => '255712345678@c.us',
                'fromMe' => false
            ]
        ];

        // Make a POST request to the webhook endpoint
        $response = $this->postJson("/api/waapi/webhook/{$this->whatsappInstance->instance_id}", $webhookPayload);

        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert that the incoming message was saved
        $this->assertDatabaseHas('incoming_messages', [
            'instance_id' => $this->whatsappInstance->id,
            'message_id' => 'msg_test_123',
            'phone_number' => '255712345678',
            'message_body' => 'Hello, this is a test message',
            'message_type' => 'text'
        ]);

        // Assert that a guest was created for the unknown number
        $this->assertDatabaseHas('event_guests', [
            'phone' => '255712345678',
            'user_id' => $this->user->id
        ]);
    }

    public function test_process_incoming_messages_from_waapi()
    {
        // Make a request to process incoming messages
        $response = $this->postJson("/api/waapi/process-messages/{$this->whatsappInstance->instance_id}");

        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'processed_count'
        ]);
    }

    public function test_can_retrieve_incoming_messages()
    {
        // Create a test incoming message
        $incomingMessage = IncomingMessage::create([
            'user_id' => $this->user->id,
            'instance_id' => $this->whatsappInstance->id,
            'message_id' => 'test_msg_456',
            'chat_id' => '255712345678@c.us',
            'phone_number' => '255712345678',
            'message_body' => 'Test message content',
            'message_type' => 'text',
            'status' => 'received',
            'received_at' => now()
        ]);

        // Make a request to get incoming messages
        $response = $this->getJson("/api/waapi/incoming-messages/{$this->whatsappInstance->instance_id}");

        // Assert the response is successful and contains the message
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message_id' => 'test_msg_456',
            'phone_number' => '255712345678',
            'message_body' => 'Test message content'
        ]);
    }

    public function test_can_mark_message_as_processed()
    {
        // Create a test incoming message
        $incomingMessage = IncomingMessage::create([
            'user_id' => $this->user->id,
            'instance_id' => $this->whatsappInstance->id,
            'message_id' => 'test_msg_789',
            'chat_id' => '255712345678@c.us',
            'phone_number' => '255712345678',
            'message_body' => 'Another test message',
            'message_type' => 'text',
            'status' => 'received',
            'received_at' => now()
        ]);

        // Mark the message as processed
        $response = $this->postJson("/api/waapi/mark-processed/{$incomingMessage->id}");

        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert the message status was updated
        $this->assertDatabaseHas('incoming_messages', [
            'id' => $incomingMessage->id,
            'status' => 'processed'
        ]);
    }

    public function test_phone_number_formatting()
    {
        // Test the phone number formatting logic
        $api = new \App\Http\Controllers\Api();
        
        // Use reflection to test the private method
        $reflection = new \ReflectionClass($api);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        // Test various phone number formats
        $this->assertEquals('255712345678', $method->invoke($api, '+255712345678'));
        $this->assertEquals('255712345678', $method->invoke($api, '255712345678'));
        $this->assertEquals('255712345678', $method->invoke($api, '0712345678'));
        $this->assertEquals('255712345678', $method->invoke($api, '712345678'));
    }
}
