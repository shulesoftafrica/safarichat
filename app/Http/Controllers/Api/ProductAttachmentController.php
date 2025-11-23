<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttachment;
use App\Services\RagSearchService;
use App\Jobs\ProcessDocumentForRAG;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductAttachmentController extends Controller
{
    /**
     * Upload multiple attachments for a product
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:51200|mimes:pdf,doc,docx,txt', // 50MB max per file
            'attachment_types' => 'required|array',
            'attachment_types.*' => 'required|in:brochure,manual,profile,case_study,certificate,contract_template,technical_spec,other',
            'titles' => 'nullable|array',
            'titles.*' => 'nullable|string|max:255',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string|max:1000',
            'is_public' => 'nullable|array',
            'is_public.*' => 'boolean',
            'process_with_rag' => 'boolean' // Option to enable RAG processing
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $files = $request->file('files');
        $types = $request->input('attachment_types');
        $titles = $request->input('titles', []);
        $descriptions = $request->input('descriptions', []);
        $isPublic = $request->input('is_public', []);
        $processRAG = $request->input('process_with_rag', true);

        // Validate array lengths match
        if (count($files) !== count($types)) {
            return response()->json([
                'success' => false,
                'message' => 'Files and attachment types count mismatch'
            ], 422);
        }

        $uploadedFiles = [];
        $ragProcessingQueued = 0;

        DB::beginTransaction();
        
        try {
            foreach ($files as $index => $file) {
                $path = $file->store("products/attachments/{$product->id}", 'public');

                $attachment = $product->attachments()->create([
                    'attachment_type' => $types[$index],
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'title' => $titles[$index] ?? $file->getClientOriginalName(),
                    'description' => $descriptions[$index] ?? null,
                    'is_public' => $isPublic[$index] ?? true,
                    'display_order' => $product->attachments()->max('display_order') + 1,
                    'processing_status' => ($processRAG && $this->isRAGSupported($file->getMimeType())) ? 'pending' : 'completed',
                    'is_processed' => !($processRAG && $this->isRAGSupported($file->getMimeType()))
                ]);
                
                // Dispatch RAG processing job if enabled and file type is supported
                if ($processRAG && $this->isRAGSupported($file->getMimeType())) {
                    ProcessDocumentForRAG::dispatch($attachment->id)
                        ->onQueue('rag_processing')
                        ->delay(now()->addSeconds(10)); // Small delay to ensure transaction commits
                    
                    $ragProcessingQueued++;
                }

                $uploadedFiles[] = [
                    'id' => $attachment->id,
                    'title' => $attachment->title,
                    'type' => $attachment->attachment_type,
                    'filename' => $attachment->original_filename,
                    'size' => $attachment->formatted_size,
                    'mime_type' => $attachment->mime_type,
                    'rag_processing' => $processRAG && $this->isRAGSupported($file->getMimeType()),
                    'supports_rag' => $this->isRAGSupported($file->getMimeType()),
                    'url' => $attachment->url,
                    'processing_status' => $attachment->processing_status,
                    'is_public' => $attachment->is_public
                ];
            }
            
            DB::commit();
            
            Log::info("Files uploaded successfully for product {$product->id}", [
                'product_id' => $product->id,
                'files_count' => count($uploadedFiles),
                'rag_queued' => $ragProcessingQueued
            ]);
            
            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'files' => $uploadedFiles,
                'rag_processing_queued' => $ragProcessingQueued,
                'product_id' => $product->id
            ]);
            
        } catch (Exception $e) {
            DB::rollback();
            
            Log::error("File upload failed for product {$product->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attachments for a product
     */
    public function index(Product $product): JsonResponse
    {
        $attachments = $product->attachments()
            ->orderBy('display_order')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'title' => $attachment->title,
                    'type' => $attachment->attachment_type,
                    'filename' => $attachment->original_filename,
                    'size' => $attachment->formatted_size,
                    'mime_type' => $attachment->mime_type,
                    'url' => $attachment->url,
                    'download_url' => $attachment->download_url,
                    'is_public' => $attachment->is_public,
                    'processing_status' => $attachment->processing_status,
                    'status_text' => $attachment->status_text,
                    'status_color' => $attachment->status_color,
                    'is_processed' => $attachment->is_processed,
                    'vector_count' => $attachment->vector_count,
                    'supports_rag' => $attachment->supportsRAG(),
                    'created_at' => $attachment->created_at,
                    'description' => $attachment->description
                ];
            });

        return response()->json([
            'success' => true,
            'attachments' => $attachments,
            'total_count' => $attachments->count(),
            'processed_count' => $attachments->where('is_processed', true)->count()
        ]);
    }

    /**
     * Delete an attachment
     */
    public function destroy(ProductAttachment $attachment): JsonResponse
    {
        try {
            $productId = $attachment->product_id;
            $filename = $attachment->original_filename;
            
            $attachment->delete(); // This will also delete the file and vectors (via model events)
            
            Log::info("Attachment deleted", [
                'attachment_id' => $attachment->id,
                'product_id' => $productId,
                'filename' => $filename
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully'
            ]);
            
        } catch (Exception $e) {
            Log::error("Failed to delete attachment {$attachment->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download an attachment
     */
    public function download(ProductAttachment $attachment)
    {
        if (!file_exists($attachment->absolute_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return Storage::disk('public')->download(
            $attachment->file_path,
            $attachment->original_filename
        );
    }

    /**
     * Get processing status for an attachment
     */
    public function status(ProductAttachment $attachment): JsonResponse
    {
        return response()->json([
            'id' => $attachment->id,
            'filename' => $attachment->original_filename,
            'processing_status' => $attachment->processing_status,
            'status_text' => $attachment->status_text,
            'status_color' => $attachment->status_color,
            'is_processed' => $attachment->is_processed,
            'vector_count' => $attachment->vector_count,
            'processing_error' => $attachment->processing_error,
            'created_at' => $attachment->created_at,
            'updated_at' => $attachment->updated_at
        ]);
    }

    /**
     * Reprocess document for RAG
     */
    public function reprocessRAG(ProductAttachment $attachment): JsonResponse
    {
        try {
            if (!$attachment->supportsRAG()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File type does not support RAG processing'
                ], 422);
            }

            // Reset processing status
            $attachment->update([
                'processing_status' => 'pending',
                'is_processed' => false,
                'processing_error' => null,
                'vector_count' => 0
            ]);

            // Delete existing vectors
            $attachment->vectors()->delete();

            // Queue for reprocessing
            ProcessDocumentForRAG::dispatch($attachment->id)
                ->onQueue('rag_processing')
                ->delay(now()->addSeconds(5));

            Log::info("RAG reprocessing queued for attachment {$attachment->id}");

            return response()->json([
                'success' => true,
                'message' => 'RAG reprocessing started',
                'attachment_id' => $attachment->id
            ]);

        } catch (Exception $e) {
            Log::error("Failed to start RAG reprocessing for attachment {$attachment->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start reprocessing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search documents using RAG
     */
    public function searchDocuments(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:3|max:500',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $ragSearch = app(RagSearchService::class);
            $results = $ragSearch->searchDocuments(
                $request->input('query'),
                $request->input('product_ids'),
                $request->input('limit', 5)
            );
            
            return response()->json([
                'success' => true,
                'query' => $request->input('query'),
                'results_count' => count($results),
                'results' => $results,
                'product_filter' => $request->input('product_ids')
            ]);

        } catch (Exception $e) {
            Log::error("RAG search failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RAG search statistics
     */
    public function searchStats(): JsonResponse
    {
        try {
            $ragSearch = app(RagSearchService::class);
            $stats = $ragSearch->getSearchStats();
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            Log::error("Failed to get RAG search stats: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Update attachment metadata
     */
    public function update(Request $request, ProductAttachment $attachment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'attachment_type' => 'nullable|in:brochure,manual,profile,case_study,certificate,contract_template,technical_spec,other'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attachment->update($request->only([
                'title', 'description', 'is_public', 'display_order', 'attachment_type'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Attachment updated successfully',
                'attachment' => [
                    'id' => $attachment->id,
                    'title' => $attachment->title,
                    'description' => $attachment->description,
                    'is_public' => $attachment->is_public,
                    'display_order' => $attachment->display_order,
                    'attachment_type' => $attachment->attachment_type
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if file type supports RAG processing
     */
    private function isRAGSupported(string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }
}