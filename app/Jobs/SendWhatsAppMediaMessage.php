<?php

namespace App\Jobs;

use App\Services\WaSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Job for sending media messages via WhatsApp using WaSender
 */
class SendWhatsAppMediaMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $mediaUrl;
    protected $mediaType;
    protected $caption;
    protected $userId;
    protected $instanceId;
    protected $additionalData;

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
     * @param string $phoneNumber
     * @param string $mediaUrl
     * @param string $mediaType (image, document, audio, video)
     * @param string|null $caption
     * @param int|null $userId
     * @param string|null $instanceId
     * @param array $additionalData Additional data based on media type
     */
    public function __construct(string $phoneNumber, string $mediaUrl, string $mediaType, ?string $caption = null, ?int $userId = null, ?string $instanceId = null, array $additionalData = [])
    {
        $this->phoneNumber = $phoneNumber;
        $this->mediaUrl = $mediaUrl;
        $this->mediaType = $mediaType;
        $this->caption = $caption;
        $this->userId = $userId;
        $this->instanceId = $instanceId;
        $this->additionalData = $additionalData;
        
        // Use media queue for media messages
        $this->onQueue('media_messages');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WaSenderService $waSenderService)
    {
        try {
            Log::info('Processing WhatsApp media message job', [
                'phone' => $this->phoneNumber,
                'media_type' => $this->mediaType,
                'media_url' => $this->mediaUrl,
                'user_id' => $this->userId
            ]);

            $result = null;

            switch ($this->mediaType) {
                case 'image':
                    $result = $waSenderService->sendImage(
                        $this->phoneNumber,
                        $this->mediaUrl,
                        $this->caption,
                        $this->instanceId,
                        $this->userId
                    );
                    break;

                case 'document':
                    $result = $waSenderService->sendDocument(
                        $this->phoneNumber,
                        $this->mediaUrl,
                        $this->additionalData['filename'] ?? null,
                        $this->caption,
                        $this->instanceId,
                        $this->userId
                    );
                    break;

                case 'audio':
                    $result = $waSenderService->sendAudio(
                        $this->phoneNumber,
                        $this->mediaUrl,
                        $this->instanceId,
                        $this->userId
                    );
                    break;

                case 'video':
                    $result = $waSenderService->sendVideo(
                        $this->phoneNumber,
                        $this->mediaUrl,
                        $this->caption,
                        $this->instanceId,
                        $this->userId
                    );
                    break;

                default:
                    throw new Exception("Unsupported media type: {$this->mediaType}");
            }

            Log::info('WhatsApp media message sent successfully via WaSender', [
                'phone' => $this->phoneNumber,
                'media_type' => $this->mediaType,
                'message_id' => $result['message_id'] ?? null,
                'result' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp media message via WaSender', [
                'phone' => $this->phoneNumber,
                'media_type' => $this->mediaType,
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
        Log::error('WhatsApp media message job failed permanently', [
            'phone' => $this->phoneNumber,
            'media_type' => $this->mediaType,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->job ? $this->job->attempts() : 'unknown'
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [60, 120, 300]; // Retry after 1min, 2min, 5mins (longer for media)
    }
}