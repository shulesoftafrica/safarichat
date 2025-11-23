<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageSentby;
use App\Models\OutgoingMessage;
use App\Services\WaSenderService;
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
    public function handle(WaSenderService $waSenderService)
    {
        try {
            Log::info('Processing WhatsApp message job', [
                'phone' => $this->phoneNumber,
                'user_id' => $this->userId,
                'source' => $this->source
            ]);

            // Send the message using WaSenderService
            $result = $waSenderService->sendTextMessage(
                $this->phoneNumber,
                $this->messageData,
                $this->instanceId,
                $this->userId
            );

            Log::info('WhatsApp message sent successfully via WaSender', [
                'phone' => $this->phoneNumber,
                'message_id' => $result['message_id'] ?? null,
                'result' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp message via WaSender', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
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
            'attempts' => $this->attempts()
        ]);

        // Send notification to admin about permanent failure
        // You can implement admin notification here
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
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [30, 60, 180]; // Retry after 30s, 1min, 3mins
    }
}
