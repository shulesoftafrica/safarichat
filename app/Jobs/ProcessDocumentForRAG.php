<?php

namespace App\Jobs;

use App\Models\ProductAttachment;
use App\Services\RagDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessDocumentForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for large documents
    public $tries = 3;
    public $maxExceptions = 3;
    public $backoff = [60, 180, 300]; // Exponential backoff in seconds
    
    private $attachmentId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $attachmentId)
    {
        $this->attachmentId = $attachmentId;
        $this->onQueue('rag_processing'); // Dedicated queue for RAG processing
    }

    /**
     * Execute the job.
     */
    public function handle(RagDocumentService $ragService)
    {
        $attachment = ProductAttachment::find($this->attachmentId);
        
        if (!$attachment) {
            Log::warning("ProductAttachment {$this->attachmentId} not found for RAG processing");
            return;
        }
        
        try {
            Log::info("Starting RAG processing job", [
                'attachment_id' => $attachment->id,
                'filename' => $attachment->original_filename,
                'product_id' => $attachment->product_id,
                'attempt' => $this->attempts()
            ]);
            
            // Check if file exists before processing
            if (!file_exists($attachment->absolute_path)) {
                throw new Exception("File not found: {$attachment->absolute_path}");
            }

            // Check if attachment supports RAG processing
            if (!$attachment->supportsRAG()) {
                Log::info("Skipping RAG processing for unsupported file type: {$attachment->mime_type}");
                $attachment->update([
                    'processing_status' => 'completed',
                    'is_processed' => false, // Not processed but completed (no processing needed)
                    'processing_error' => 'File type does not support RAG processing'
                ]);
                return;
            }
            
            // Process the document
            $result = $ragService->processDocument($attachment);
            
            Log::info("RAG processing completed successfully", [
                'attachment_id' => $attachment->id,
                'vectors_created' => $result['vectors_created'],
                'chunks_processed' => $result['chunks_processed'],
                'product_id' => $attachment->product_id,
                'processing_time' => now()->diffInSeconds($this->job->created_at ?? now())
            ]);
            
        } catch (Exception $e) {
            Log::error("RAG processing failed", [
                'attachment_id' => $attachment->id,
                'filename' => $attachment->original_filename,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries
            ]);
            
            // Update attachment with error details
            $attachment->update([
                'processing_error' => $e->getMessage()
            ]);
            
            // If we've exhausted all attempts, mark as failed
            if ($this->attempts() >= $this->tries) {
                $attachment->update([
                    'processing_status' => 'failed',
                    'is_processed' => false
                ]);
                
                Log::error("RAG processing failed permanently after {$this->tries} attempts", [
                    'attachment_id' => $attachment->id,
                    'final_error' => $e->getMessage()
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        $attachment = ProductAttachment::find($this->attachmentId);
        
        if ($attachment) {
            $attachment->update([
                'processing_status' => 'failed',
                'is_processed' => false,
                'processing_error' => 'Job failed permanently: ' . $exception->getMessage()
            ]);
        }
        
        Log::error('ProcessDocumentForRAG job failed permanently', [
            'attachment_id' => $this->attachmentId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        return $this->backoff;
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil()
    {
        return now()->addMinutes(30); // Don't retry after 30 minutes
    }

    /**
     * Get the tags for the job.
     */
    public function tags()
    {
        return [
            'rag-processing',
            'attachment:' . $this->attachmentId,
            'document-processing'
        ];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [
            // Could add rate limiting middleware here if needed
        ];
    }
}