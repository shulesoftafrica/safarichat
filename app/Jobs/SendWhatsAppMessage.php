<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageSentby;
use App\Models\OutgoingMessage;
use App\Http\Controllers\Message as MessageController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageData;
    protected $phoneNumber;
    protected $source;
    protected $userId;
    protected $files;
    protected $instanceId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($messageData, $phoneNumber, $source = 'whatsapp', $userId = null, $files = null, $instanceId = null)
    {
        $this->messageData = $messageData;
        $this->phoneNumber = $phoneNumber;
        $this->source = $source;
        $this->userId = $userId;
        $this->files = $files;
        $this->instanceId = $instanceId;
        
        // Set queue based on message priority
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Processing WhatsApp message job', [
                'phone' => $this->phoneNumber,
                'user_id' => $this->userId,
                'source' => $this->source
            ]);

            // Store message in database first
            $message = $this->storeMessage();
            
            if (!$message) {
                throw new Exception('Failed to store message in database');
            }

            // Create outgoing message record for tracking
            $outgoingMessage = $this->createOutgoingMessage($message);

            // Send the actual message
            $result = $this->sendMessage($message, $outgoingMessage);

            // Update message status based on result
            $this->updateMessageStatus($message, $outgoingMessage, $result);

            Log::info('WhatsApp message sent successfully', [
                'message_id' => $message->id,
                'phone' => $this->phoneNumber,
                'result' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update failed message status if message was created
            if (isset($message)) {
                $this->updateFailedMessageStatus($message, $e->getMessage());
            }

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Store message in database
     */
    private function storeMessage()
    {
        return DB::transaction(function () {
            $message = Message::create([
                'body' => $this->messageData,
                'user_id' => $this->userId ?? 1,
                'type' => $this->source === 'whatsapp' ? 2 : 1,
                'phone' => str_replace('@c.us', '', $this->phoneNumber),
                'status' => 'pending'
            ]);

            // Create message sent by record
            MessageSentby::create([
                'message_id' => $message->id,
                'channel' => $this->source,
                'status' => 0,
                'return_code' => null
            ]);

            return $message;
        });
    }

    /**
     * Create outgoing message record for detailed tracking
     */
    private function createOutgoingMessage($message)
    {
        return OutgoingMessage::create([
            'message_id' => $message->id,
            'user_id' => $this->userId,
            'phone_number' => str_replace('@c.us', '', $this->phoneNumber),
            'message' => $this->messageData,
            'message_type' => $this->files ? 'media' : 'text',
            'status' => 'pending',
            'instance_id' => $this->instanceId,
            'scheduled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Send the actual message using WhatsApp API
     */
    private function sendMessage($message, $outgoingMessage)
    {
        $messageController = new MessageController();
        
        // Prepare the phone number with @c.us suffix if needed
        $chatId = $this->phoneNumber;
        if (!str_contains($chatId, '@c.us')) {
            $chatId = $chatId . '@c.us';
        }

        // Get message sent by record
        $messageSentby = MessageSentby::where('message_id', $message->id)
            ->where('channel', $this->source)
            ->first();

        if (!$messageSentby) {
            throw new Exception('MessageSentby record not found');
        }

        // Call the send method from MessageController
        return $messageController->send(
            $this->messageData,
            $chatId,
            $this->source,
            $messageSentby->id,
            $this->userId
        );
    }

    /**
     * Update message status after successful send
     */
    private function updateMessageStatus($message, $outgoingMessage, $result)
    {
        $status = 'sent';
        $deliveryStatus = 'delivered';

        // Parse result to determine actual status
        if (is_array($result) || is_object($result)) {
            $resultArray = is_object($result) ? (array) $result : $result;
            
            if (isset($resultArray['success']) && !$resultArray['success']) {
                $status = 'failed';
                $deliveryStatus = 'failed';
            }
        }

        // Update message
        $message->update([
            'status' => $status,
            'sent_at' => now()
        ]);

        // Update outgoing message
        $outgoingMessage->update([
            'status' => $deliveryStatus,
            'sent_at' => now(),
            'delivery_status' => $deliveryStatus,
            'api_response' => json_encode($result)
        ]);

        // Update message sent by
        MessageSentby::where('message_id', $message->id)
            ->where('channel', $this->source)
            ->update([
                'status' => $status === 'sent' ? 1 : 0,
                'return_code' => json_encode($result),
                'updated_at' => now()
            ]);
    }

    /**
     * Update message status for failed attempts
     */
    private function updateFailedMessageStatus($message, $error)
    {
        // Update message
        $message->update([
            'status' => 'failed',
            'error_message' => $error
        ]);

        // Update outgoing message if exists
        $outgoingMessage = OutgoingMessage::where('message_id', $message->id)->first();
        if ($outgoingMessage) {
            $outgoingMessage->update([
                'status' => 'failed',
                'delivery_status' => 'failed',
                'error_message' => $error
            ]);
        }

        // Update message sent by
        MessageSentby::where('message_id', $message->id)
            ->where('channel', $this->source)
            ->update([
                'status' => 0,
                'return_code' => json_encode(['error' => $error]),
                'updated_at' => now()
            ]);
    }

    /**
     * Determine which queue to use based on message characteristics
     */
    private function determineQueue()
    {
        // High priority for single messages or urgent sends
        if ($this->source === 'whatsapp' && !is_array($this->phoneNumber)) {
            return 'high_priority';
        }

        // Bulk queue for multiple recipients
        if (is_array($this->phoneNumber)) {
            return 'bulk_messages';
        }

        // Default messages queue
        return 'messages';
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('WhatsApp message job failed permanently', [
            'phone' => $this->phoneNumber,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts
        ]);

        // Send notification to admin about permanent failure
        // You can implement admin notification here
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff()
    {
        return [30, 60, 180]; // Retry after 30s, 1min, 3mins
    }
}
