<?php

namespace App\Services;

use App\Models\ProductAttachment;
use App\Models\DocumentVector;
use Illuminate\Support\Facades\Log;
use Exception;

class RagDocumentService
{
    private $openAiService;
    private $chunkSize = 1000; // Characters per chunk
    private $chunkOverlap = 200; // Overlap between chunks

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    /**
     * Process document and create vectors
     */
    public function processDocument(ProductAttachment $attachment): array
    {
        // Step 1: Mark as processing
        $attachment->update(['processing_status' => 'processing']);
        
        try {
            Log::info("Starting RAG processing for attachment: {$attachment->id}");

            // Step 2: Extract text content
            $textContent = $this->extractTextContent($attachment);
            
            if (empty(trim($textContent))) {
                throw new Exception("No text content extracted from document");
            }

            // Step 3: Split into semantic chunks
            $chunks = $this->splitIntoChunks($textContent, $attachment);
            
            if (empty($chunks)) {
                throw new Exception("No chunks created from document");
            }

            // Step 4: Generate embeddings for each chunk
            $vectors = [];
            foreach ($chunks as $chunk) {
                try {
                    $vector = $this->createVectorFromChunk($chunk, $attachment);
                    $vectors[] = $vector;
                } catch (Exception $e) {
                    Log::warning("Failed to create vector for chunk {$chunk['index']}: " . $e->getMessage());
                    // Continue with other chunks
                }
            }

            if (empty($vectors)) {
                throw new Exception("No vectors created from document chunks");
            }

            // Step 5: Store vectors in database
            $this->storeVectors($vectors, $attachment);

            // Step 6: Mark as completed
            $attachment->update([
                'processing_status' => 'completed',
                'is_processed' => true,
                'vector_count' => count($vectors),
                'processing_error' => null
            ]);

            Log::info("RAG processing completed for attachment: {$attachment->id}, vectors: " . count($vectors));

            return [
                'success' => true, 
                'vectors_created' => count($vectors),
                'chunks_processed' => count($chunks)
            ];
            
        } catch (Exception $e) {
            Log::error("RAG processing failed for attachment {$attachment->id}: " . $e->getMessage());
            
            $attachment->update([
                'processing_status' => 'failed',
                'is_processed' => false,
                'processing_error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Extract text content from file
     */
    private function extractTextContent(ProductAttachment $attachment): string
    {
        $filePath = $attachment->absolute_path;
        
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        switch ($attachment->mime_type) {
            case 'application/pdf':
                return $this->extractFromPdf($filePath);
                
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return $this->extractFromWord($filePath);
                
            case 'text/plain':
                return file_get_contents($filePath);
                
            default:
                throw new Exception("Unsupported file type: {$attachment->mime_type}");
        }
    }

    /**
     * Extract text from PDF using spatie/pdf-to-text
     */
    private function extractFromPdf(string $filePath): string
    {
        try {
            $pdf = new \Spatie\PdfToText\Pdf($filePath);
            return $pdf->text();
        } catch (Exception $e) {
            throw new Exception("PDF text extraction failed: " . $e->getMessage());
        }
    }

    /**
     * Extract text from Word document (basic implementation)
     */
    private function extractFromWord(string $filePath): string
    {
        // For now, we'll use a simple approach
        // In production, you might want to use a more robust solution
        try {
            if (str_ends_with($filePath, '.docx')) {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    
                    if ($xml) {
                        // Simple XML parsing to extract text
                        $xml = preg_replace('/(<[^>]+>)/', ' ', $xml);
                        return html_entity_decode($xml);
                    }
                }
            }
            
            throw new Exception("Word document extraction not implemented for this format");
        } catch (Exception $e) {
            throw new Exception("Word document text extraction failed: " . $e->getMessage());
        }
    }

    /**
     * Split text into semantic chunks
     */
    private function splitIntoChunks(string $text, ProductAttachment $attachment): array
    {
        $chunks = [];
        $text = trim($text);
        
        // Split by paragraphs first
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        $currentChunk = '';
        $chunkIndex = 0;
        $pageNumber = 1;
        $sectionTitle = null;
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            // Detect section headings (simple heuristic)
            if ($this->isSectionHeading($paragraph)) {
                $sectionTitle = $paragraph;
                continue;
            }
            
            // Check if adding this paragraph exceeds chunk size
            $testChunk = $currentChunk . ($currentChunk ? "\n\n" : '') . $paragraph;
            
            if (strlen($testChunk) > $this->chunkSize && !empty($currentChunk)) {
                // Save current chunk
                $chunks[] = $this->createChunkData($currentChunk, $chunkIndex++, $pageNumber, $sectionTitle);
                
                // Start new chunk with overlap
                $currentChunk = $this->createOverlapChunk($currentChunk) . $paragraph;
            } else {
                $currentChunk = $testChunk;
            }
        }
        
        // Add final chunk if not empty
        if (!empty($currentChunk)) {
            $chunks[] = $this->createChunkData($currentChunk, $chunkIndex, $pageNumber, $sectionTitle);
        }
        
        return $chunks;
    }

    /**
     * Create chunk data array
     */
    private function createChunkData(string $content, int $index, int $pageNumber, ?string $sectionTitle): array
    {
        return [
            'index' => $index,
            'content' => trim($content),
            'page_number' => $pageNumber,
            'section_title' => $sectionTitle,
            'word_count' => str_word_count($content),
            'char_count' => strlen($content)
        ];
    }

    /**
     * Create overlap chunk from previous content
     */
    private function createOverlapChunk(string $content): string
    {
        if (strlen($content) <= $this->chunkOverlap) {
            return $content . "\n\n";
        }
        
        // Get last part of content for overlap
        $overlapContent = substr($content, -$this->chunkOverlap);
        
        // Try to break at word boundary
        $lastSpace = strrpos($overlapContent, ' ');
        if ($lastSpace !== false && $lastSpace > $this->chunkOverlap * 0.5) {
            $overlapContent = substr($overlapContent, $lastSpace + 1);
        }
        
        return $overlapContent . "\n\n";
    }

    /**
     * Create vector from chunk
     */
    private function createVectorFromChunk(array $chunk, ProductAttachment $attachment): array
    {
        // Generate AI summary for the chunk
        $summary = $this->openAiService->generateChunkSummary(
            $chunk['content'], 
            $attachment->product->name
        );

        // Generate embedding vector
        $embedding = $this->openAiService->generateEmbedding($chunk['content']);

        return [
            'chunk_index' => $chunk['index'],
            'content_text' => $chunk['content'],
            'content_summary' => $summary,
            'page_number' => $chunk['page_number'],
            'section_title' => $chunk['section_title'],
            'embedding_vector' => $embedding,
            'metadata' => [
                'word_count' => $chunk['word_count'],
                'char_count' => $chunk['char_count'],
                'product_name' => $attachment->product->name,
                'document_type' => $attachment->attachment_type,
                'file_name' => $attachment->original_filename
            ]
        ];
    }

    /**
     * Store vectors in database
     */
    private function storeVectors(array $vectors, ProductAttachment $attachment): void
    {
        foreach ($vectors as $vector) {
            DocumentVector::create([
                'product_attachment_id' => $attachment->id,
                'product_id' => $attachment->product_id,
                'chunk_index' => $vector['chunk_index'],
                'content_text' => $vector['content_text'],
                'content_summary' => $vector['content_summary'],
                'page_number' => $vector['page_number'],
                'section_title' => $vector['section_title'],
                'embedding_vector' => $vector['embedding_vector'],
                'metadata' => $vector['metadata']
            ]);
        }
    }

    /**
     * Simple heuristic to detect section headings
     */
    private function isSectionHeading(string $text): bool
    {
        $text = trim($text);
        
        // Too long to be a heading
        if (strlen($text) > 100) {
            return false;
        }
        
        // Common heading patterns
        $patterns = [
            '/^[A-Z][A-Za-z\s]+$/', // Title case without punctuation at end
            '/^\d+\.?\s+[A-Z]/', // Starts with number
            '/^(Chapter|Section|Part|Appendix)\s+/i', // Common heading words
            '/^[A-Z\s]+$/', // All caps (short)
            '/^#+\s+/', // Markdown style
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Reprocess an existing document
     */
    public function reprocessDocument(ProductAttachment $attachment): array
    {
        // Delete existing vectors
        $attachment->vectors()->delete();
        
        // Reset processing status
        $attachment->update([
            'is_processed' => false,
            'processing_status' => 'pending',
            'vector_count' => 0,
            'processing_error' => null
        ]);
        
        // Process again
        return $this->processDocument($attachment);
    }
}