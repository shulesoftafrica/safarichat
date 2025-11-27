<?php

namespace App\Jobs;

use App\Models\IncomingMessage;
use App\Services\AiWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAiMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    
    private $message;

    public function __construct(IncomingMessage $message)
    {
        $this->message = $message;
    }

    public function handle(AiWhatsAppService $aiWhatsAppService)
    {
        try {
            // Increment processing attempts
            $this->message->increment('processing_attempts');

            // Process the message with AI
            $result = $aiWhatsAppService->processIncomingWhatsAppMessageWithAI($this->message);

            if ($result['success']) {
                // Send response
                if (isset($result['response'])) {
                    $sent = $aiWhatsAppService->sendResponse(
                        $result['response'], 
                        $this->message
                    );
                    
                    if ($sent) {
                        $this->message->update([
                            'status' => 'replied',
                            'processing_method' => 'queue',
                        ]);
                    } else {
                        // Failed to send, but AI processing succeeded
                        $this->message->update([
                            'status' => 'processed',
                            'processing_method' => 'queue',
                            'failure_reason' => 'Failed to send response',
                        ]);
                    }
                }

                Log::info('AI message processed successfully via queue', [
                    'message_id' => $this->message->id,
                    'phone_number' => $this->message->phone_number,
                    'attempts' => $this->message->processing_attempts,
                ]);

            } else {
                // AI processing failed, update failure reason
                $this->message->update([
                    'failure_reason' => $result['error'] ?? 'AI processing failed',
                ]);

                if ($this->attempts() >= $this->tries) {
                    // Final failure - mark for human attention
                    $this->message->update([
                        'status' => 'failed',
                        'failure_reason' => 'Max retry attempts reached',
                    ]);

                    Log::error('AI message processing failed permanently', [
                        'message_id' => $this->message->id,
                        'phone_number' => $this->message->phone_number,
                        'attempts' => $this->attempts(),
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                } else {
                    // Retry with exponential backoff
                    $this->release(60 * pow(2, $this->attempts()));
                }
            }

        } catch (\Exception $e) {
            $this->message->update([
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Job processing exception', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->message->update([
                    'status' => 'failed',
                    'failure_reason' => 'Job processing exception: ' . $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    public function failed(\Exception $exception)
    {
        $this->message->update([
            'status' => 'failed',
            'failure_reason' => 'Job failed: ' . $exception->getMessage(),
        ]);

        Log::error('ProcessAiMessage job failed permanently', [
            'message_id' => $this->message->id,
            'phone_number' => $this->message->phone_number,
            'exception' => $exception->getMessage(),
        ]);
    }
}