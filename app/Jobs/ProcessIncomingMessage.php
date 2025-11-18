<?php

namespace App\Jobs;

use App\Models\IncomingMessage;
use App\Models\WhatsappInstance;
use App\Models\Message;
use App\Models\MessageSentby;
use App\Http\Controllers\Api;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageData;
    protected $instanceId;
    protected $whatsappInstance;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($messageData, $instanceId, $whatsappInstance = null)
    {
        $this->messageData = $messageData;
        $this->instanceId = $instanceId;
        $this->whatsappInstance = $whatsappInstance;
        
        // Queue incoming messages with high priority
        $this->onQueue('high_priority');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Processing incoming message via queue', [
                'instance_id' => $this->instanceId,
                'message_preview' => substr($this->messageData['body'] ?? '', 0, 50) . '...'
            ]);

            // Get WhatsApp instance if not provided
            if (!$this->whatsappInstance) {
                $this->whatsappInstance = WhatsappInstance::where('instance_id', $this->instanceId)->first();
                
                if (!$this->whatsappInstance) {
                    throw new Exception("WhatsApp instance not found: {$this->instanceId}");
                }
            }

            // Use the Api controller to process the message
            $apiController = new Api();
            $reflection = new \ReflectionClass($apiController);
            $method = $reflection->getMethod('processSingleMessage');
            $method->setAccessible(true);
            
            // Process the message
            $method->invoke($apiController, $this->whatsappInstance, $this->messageData);

            // Trigger AI response if enabled for this instance
            $this->processAIResponse();

            Log::info('Successfully processed incoming message via queue', [
                'instance_id' => $this->instanceId,
                'message_id' => $this->messageData['id'] ?? 'unknown'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process incoming message via queue', [
                'instance_id' => $this->instanceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process AI response if configured
     */
    private function processAIResponse()
    {
        try {
            // Check if AI auto-reply is enabled for this instance
            if (!$this->whatsappInstance->ai_enabled) {
                return;
            }

            // Extract message info
            $messageBody = $this->messageData['body'] ?? '';
            $chatId = $this->messageData['chatId'] ?? $this->messageData['chat_id'] ?? null;
            $fromMe = $this->messageData['fromMe'] ?? false;

            // Skip if message is from us or empty
            if ($fromMe || empty(trim($messageBody))) {
                return;
            }

            // Get recent conversation context (last 5 messages)
            $phoneNumber = $this->extractPhoneFromChatId($chatId);
            $recentMessages = IncomingMessage::where('phone_number', $phoneNumber)
                ->where('instance_id', $this->instanceId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->reverse()
                ->values();

            // Build conversation context
            $context = $recentMessages->map(function ($msg) {
                return [
                    'role' => $msg->from_me ? 'assistant' : 'user',
                    'content' => $msg->message_body,
                    'timestamp' => $msg->created_at
                ];
            })->toArray();

            // Add current message to context
            $context[] = [
                'role' => 'user',
                'content' => $messageBody,
                'timestamp' => now()
            ];

            // Generate AI response based on products and FAQ
            $aiResponse = $this->generateAIResponse($context);

            if ($aiResponse) {
                // Queue the AI response
                SendWhatsAppMessage::dispatch(
                    $aiResponse,
                    $phoneNumber,
                    'whatsapp',
                    $this->whatsappInstance->user_id,
                    null,
                    $this->instanceId
                )->delay(now()->addSeconds(2)); // Small delay to feel natural
            }

        } catch (Exception $e) {
            Log::error('Error processing AI response', [
                'instance_id' => $this->instanceId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate AI response based on context and products
     */
    private function generateAIResponse($context)
    {
        // This is a simplified AI response generation
        // In production, you would integrate with OpenAI, Claude, or your preferred AI service
        
        $lastMessage = end($context)['content'];
        $lowercaseMessage = strtolower($lastMessage);

        // Simple keyword-based responses for testing
        $responses = [
            'hello' => "Hello! 👋 Welcome to SafariChat. How can I help you today?",
            'hi' => "Hi there! 👋 I'm here to help you with our WhatsApp automation solutions.",
            'price' => "Our WhatsApp Business API pricing starts from $29/month. Would you like to know more about our features?",
            'features' => "SafariChat offers:\n🔹 WhatsApp Business API\n🔹 AI-powered responses\n🔹 Bulk messaging\n🔹 Analytics & reporting\n🔹 Multi-agent support\n\nWhich feature interests you most?",
            'help' => "I'm here to help! You can ask me about:\n• Pricing and plans\n• Features and capabilities\n• Getting started\n• Technical support\n\nWhat would you like to know?",
            'thank' => "You're welcome! 😊 Is there anything else I can help you with?",
            'bye' => "Thank you for your interest in SafariChat! Feel free to reach out anytime. Have a great day! 👋"
        ];

        // Find matching response
        foreach ($responses as $keyword => $response) {
            if (str_contains($lowercaseMessage, $keyword)) {
                return $response;
            }
        }

        // Default response for unmatched queries
        return "Thanks for your message! 💬 Our team will get back to you soon. In the meantime, you can explore our features by typing 'features' or ask about 'pricing'.";
    }

    /**
     * Extract phone number from chat ID
     */
    private function extractPhoneFromChatId($chatId)
    {
        if (!$chatId) {
            return null;
        }

        // Remove @c.us or @g.us suffix
        $phone = str_replace(['@c.us', '@g.us'], '', $chatId);
        
        // Remove any non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        return $phone;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('ProcessIncomingMessage job failed permanently', [
            'instance_id' => $this->instanceId,
            'message_data' => $this->messageData,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        return [10, 30, 90]; // Retry after 10s, 30s, 90s
    }
}
