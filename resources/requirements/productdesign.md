# Product & Service Design Requirements with RAG Implementation

## Critical Gaps Identified:

⚠️ **No product_type field** - Can't distinguish tangible products from services  
⚠️ **Single attachment only** - Services need multiple files (brochures, manuals, profiles, case studies)  
⚠️ **Incomplete AI context** - Services need different info than products (duration, deliverables, pricing models)  
⚠️ **Stock tracking confusion** - Services shouldn't track inventory  
⚠️ **No intelligent document retrieval** - AI cannot search and retrieve relevant information from uploaded documents  
⚠️ **No vector search capability** - Cannot find semantically similar content from product documentation  

---

## Enhanced Solution: Product/Service Management + RAG (Retrieval-Augmented Generation)

### **Core Implementation Strategy**
1. **Product Type Classification** - Distinguish tangible products from services
2. **Multi-File Attachment System** - Support multiple documents per product/service
3. **RAG Vector Database Integration** - Process and store document content for intelligent retrieval
4. **AI Context Enhancement** - Retrieve relevant document content for AI sales conversations
5. **Semantic Search** - Enable finding relevant information across all product documentation

---

# Step-by-Step Implementation Plan

## Phase 1: Database Schema Enhancement

### 1.1 Product Type Classification
```sql
-- Migration: 2025_11_22_000001_add_product_service_distinction.php
ALTER TABLE products ADD COLUMN product_type ENUM('tangible', 'service') DEFAULT 'tangible';
ALTER TABLE products ADD COLUMN service_delivery_type VARCHAR(50) NULL;
ALTER TABLE products ADD COLUMN service_duration_days INT NULL;
ALTER TABLE products ADD COLUMN service_deliverables JSON NULL;
ALTER TABLE products ADD COLUMN requires_consultation BOOLEAN DEFAULT false;
ALTER TABLE products ADD COLUMN pricing_type VARCHAR(50) NULL;
ALTER TABLE products ADD COLUMN hourly_rate DECIMAL(10,2) NULL;
ALTER TABLE products ADD COLUMN service_tiers JSON NULL;
ALTER TABLE products ADD COLUMN prerequisites TEXT NULL;
```

### 1.2 Multi-File Attachment System
```sql
-- Migration: 2025_11_22_000002_create_product_attachments_table.php
CREATE TABLE product_attachments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    attachment_type ENUM('brochure', 'manual', 'profile', 'case_study', 'certificate', 'contract_template', 'technical_spec', 'other'),
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    is_public BOOLEAN DEFAULT true,
    display_order INT DEFAULT 0,
    is_processed BOOLEAN DEFAULT false, -- RAG processing status
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    vector_count INT DEFAULT 0, -- Number of vectors generated
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_type (product_id, attachment_type),
    INDEX idx_processing_status (processing_status)
);
```

### 1.3 RAG Vector Database Schema
```sql
-- Migration: 2025_11_22_000003_create_document_vectors_table.php
CREATE TABLE document_vectors (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_attachment_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL, -- Denormalized for faster queries
    chunk_index INT NOT NULL, -- Sequential chunk number within document
    content_text TEXT NOT NULL, -- Original text chunk
    content_summary VARCHAR(500) NULL, -- AI-generated summary of chunk
    page_number INT NULL, -- PDF page number (if applicable)
    section_title VARCHAR(255) NULL, -- Extracted section heading
    embedding_vector JSON NOT NULL, -- OpenAI text-embedding-3-small vector
    metadata JSON NULL, -- Additional context (word_count, keywords, etc)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_attachment_id) REFERENCES product_attachments(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_vectors (product_id),
    INDEX idx_attachment_vectors (product_attachment_id)
);
```

### 1.4 Vector Search Cache Table
```sql
-- Migration: 2025_11_22_000004_create_vector_search_cache_table.php
CREATE TABLE vector_search_cache (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    query_hash VARCHAR(64) NOT NULL, -- SHA256 hash of search query
    query_text VARCHAR(1000) NOT NULL,
    product_ids JSON NULL, -- Filter by specific products
    search_results JSON NOT NULL, -- Cached results with scores
    expiry_time TIMESTAMP NOT NULL,
    hit_count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_query_hash (query_hash),
    INDEX idx_expiry (expiry_time)
);
```

---

## Phase 2: RAG Service Implementation

### 2.1 Document Processing Service
```php
// app/Services/RagDocumentService.php

class RagDocumentService
{
    private $openAiService;
    private $chunkSize = 1000; // Characters per chunk
    private $chunkOverlap = 200; // Overlap between chunks
    
    public function processDocument(ProductAttachment $attachment): array
    {
        // Step 1: Mark as processing
        $attachment->update(['processing_status' => 'processing']);
        
        try {
            // Step 2: Extract text content
            $textContent = $this->extractTextContent($attachment);
            
            // Step 3: Split into semantic chunks
            $chunks = $this->splitIntoChunks($textContent, $attachment);
            
            // Step 4: Generate embeddings for each chunk
            $vectors = $this->generateEmbeddings($chunks, $attachment);
            
            // Step 5: Store vectors in database
            $this->storeVectors($vectors, $attachment);
            
            // Step 6: Mark as completed
            $attachment->update([
                'processing_status' => 'completed',
                'is_processed' => true,
                'vector_count' => count($vectors)
            ]);
            
            return ['success' => true, 'vectors_created' => count($vectors)];
            
        } catch (Exception $e) {
            $attachment->update(['processing_status' => 'failed']);
            throw $e;
        }
    }
    
    private function extractTextContent(ProductAttachment $attachment): string
    {
        $filePath = storage_path('app/public/' . $attachment->file_path);
        
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
    
    private function splitIntoChunks(string $text, ProductAttachment $attachment): array
    {
        $chunks = [];
        $paragraphs = explode("\n\n", $text);
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
            if (strlen($currentChunk . $paragraph) > $this->chunkSize && !empty($currentChunk)) {
                // Save current chunk
                $chunks[] = [
                    'index' => $chunkIndex++,
                    'content' => $currentChunk,
                    'page_number' => $pageNumber,
                    'section_title' => $sectionTitle,
                    'word_count' => str_word_count($currentChunk)
                ];
                
                // Start new chunk with overlap
                $words = explode(' ', $currentChunk);
                $overlapWords = array_slice($words, -($this->chunkOverlap / 5)); // Rough word overlap
                $currentChunk = implode(' ', $overlapWords) . ' ' . $paragraph;
            } else {
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
            }
        }
        
        // Add final chunk if not empty
        if (!empty($currentChunk)) {
            $chunks[] = [
                'index' => $chunkIndex,
                'content' => $currentChunk,
                'page_number' => $pageNumber,
                'section_title' => $sectionTitle,
                'word_count' => str_word_count($currentChunk)
            ];
        }
        
        return $chunks;
    }
    
    private function generateEmbeddings(array $chunks, ProductAttachment $attachment): array
    {
        $vectors = [];
        
        foreach ($chunks as $chunk) {
            // Generate AI summary for the chunk
            $summary = $this->openAiService->generateChunkSummary($chunk['content'], $attachment->product->name);
            
            // Generate embedding vector
            $embedding = $this->openAiService->generateEmbedding($chunk['content']);
            
            $vectors[] = [
                'chunk_index' => $chunk['index'],
                'content_text' => $chunk['content'],
                'content_summary' => $summary,
                'page_number' => $chunk['page_number'],
                'section_title' => $chunk['section_title'],
                'embedding_vector' => json_encode($embedding),
                'metadata' => json_encode([
                    'word_count' => $chunk['word_count'],
                    'char_count' => strlen($chunk['content']),
                    'product_name' => $attachment->product->name,
                    'document_type' => $attachment->attachment_type,
                    'file_name' => $attachment->original_filename
                ])
            ];
        }
        
        return $vectors;
    }
    
    private function storeVectors(array $vectors, ProductAttachment $attachment): void
    {
        foreach ($vectors as $vector) {
            DocumentVector::create(array_merge($vector, [
                'product_attachment_id' => $attachment->id,
                'product_id' => $attachment->product_id
            ]));
        }
    }
    
    private function extractFromPdf(string $filePath): string
    {
        // Using spatie/pdf-to-text package
        return (new \Spatie\PdfToText\Pdf())
            ->setPdf($filePath)
            ->text();
    }
    
    private function isSectionHeading(string $text): bool
    {
        // Simple heuristics for detecting headings
        return (
            strlen($text) < 100 && // Short text
            (preg_match('/^[A-Z][^.]*$/', $text) || // All caps or title case without period
             preg_match('/^\d+\.?\s/', $text) || // Starts with number
             preg_match('/^(Chapter|Section|Part)\s/i', $text)) // Common heading words
        );
    }
}
```

### 2.2 Vector Search Service
```php
// app/Services/RagSearchService.php

class RagSearchService
{
    private $openAiService;
    private $similarity_threshold = 0.7; // Minimum cosine similarity
    private $max_results = 10;
    
    public function searchDocuments(string $query, ?array $productIds = null, int $limit = 5): array
    {
        // Step 1: Check cache first
        $cacheKey = $this->getCacheKey($query, $productIds);
        $cached = $this->getCachedResults($cacheKey);
        if ($cached) {
            return $cached;
        }
        
        // Step 2: Generate embedding for search query
        $queryEmbedding = $this->openAiService->generateEmbedding($query);
        
        // Step 3: Find similar vectors using cosine similarity
        $results = $this->findSimilarVectors($queryEmbedding, $productIds, $limit);
        
        // Step 4: Cache results
        $this->cacheResults($cacheKey, $results);
        
        return $results;
    }
    
    private function findSimilarVectors(array $queryEmbedding, ?array $productIds, int $limit): array
    {
        $query = DocumentVector::select([
            'document_vectors.*',
            'products.name as product_name',
            'product_attachments.attachment_type',
            'product_attachments.original_filename',
            'product_attachments.title as document_title'
        ])
        ->join('products', 'document_vectors.product_id', '=', 'products.id')
        ->join('product_attachments', 'document_vectors.product_attachment_id', '=', 'product_attachments.id')
        ->where('products.status', 'active');
        
        if ($productIds) {
            $query->whereIn('document_vectors.product_id', $productIds);
        }
        
        $vectors = $query->get();
        
        $results = [];
        foreach ($vectors as $vector) {
            $vectorEmbedding = json_decode($vector->embedding_vector, true);
            $similarity = $this->calculateCosineSimilarity($queryEmbedding, $vectorEmbedding);
            
            if ($similarity >= $this->similarity_threshold) {
                $results[] = [
                    'id' => $vector->id,
                    'product_id' => $vector->product_id,
                    'product_name' => $vector->product_name,
                    'document_type' => $vector->attachment_type,
                    'document_title' => $vector->document_title ?: $vector->original_filename,
                    'content' => $vector->content_text,
                    'summary' => $vector->content_summary,
                    'section_title' => $vector->section_title,
                    'page_number' => $vector->page_number,
                    'similarity_score' => $similarity,
                    'metadata' => json_decode($vector->metadata, true)
                ];
            }
        }
        
        // Sort by similarity score descending
        usort($results, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });
        
        return array_slice($results, 0, $limit);
    }
    
    private function calculateCosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;
        
        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += $vectorA[$i] * $vectorA[$i];
            $magnitudeB += $vectorB[$i] * $vectorB[$i];
        }
        
        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);
        
        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0;
        }
        
        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
    
    private function getCacheKey(string $query, ?array $productIds): string
    {
        $key = $query;
        if ($productIds) {
            $key .= '|products:' . implode(',', $productIds);
        }
        return hash('sha256', $key);
    }
    
    private function getCachedResults(string $cacheKey): ?array
    {
        $cached = VectorSearchCache::where('query_hash', $cacheKey)
            ->where('expiry_time', '>', now())
            ->first();
            
        if ($cached) {
            $cached->increment('hit_count');
            return json_decode($cached->search_results, true);
        }
        
        return null;
    }
    
    private function cacheResults(string $cacheKey, array $results): void
    {
        VectorSearchCache::updateOrCreate(
            ['query_hash' => $cacheKey],
            [
                'query_text' => substr($query, 0, 1000),
                'search_results' => json_encode($results),
                'expiry_time' => now()->addHours(24)
            ]
        );
    }
}
```

### 2.3 Enhanced OpenAI Service
```php
// Update app/Services/OpenAiService.php

class OpenAiService
{
    // ... existing methods ...
    
    public function generateEmbedding(string $text): array
    {
        $response = $this->client->embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $text,
            'encoding_format' => 'float'
        ]);
        
        return $response->embeddings[0]->embedding;
    }
    
    public function generateChunkSummary(string $content, string $productName): string
    {
        $prompt = "Summarize this product documentation chunk for '{$productName}' in 1-2 sentences, focusing on key information for sales conversations:\n\n{$content}";
        
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini', // Cheaper model for summaries
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert at summarizing product documentation for sales teams.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 150,
            'temperature' => 0.3
        ]);
        
        return $response->choices[0]->message->content;
    }
    
    // Enhanced method for RAG-augmented responses
    public function generateSalesResponseWithRAG(
        string $customerMessage,
        AiSalesAgent $agent,
        Lead $lead,
        array $conversationHistory = [],
        ?Product $product = null
    ): array {
        try {
            // Step 1: Search for relevant document content
            $ragService = app(RagSearchService::class);
            $productIds = $product ? [$product->id] : $lead->leadProducts()->pluck('product_id')->toArray();
            $relevantDocs = $ragService->searchDocuments($customerMessage, $productIds, 3);
            
            // Step 2: Build enhanced prompt with document context
            $prompt = $this->buildRAGPrompt($customerMessage, $agent, $lead, $conversationHistory, $product, $relevantDocs);
            
            // Step 3: Generate response
            $response = $this->client->chat()->create([
                'model' => $this->defaultModel,
                'messages' => $prompt,
                'max_tokens' => 1200, // Increased for more detailed responses
                'temperature' => 0.7,
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.1,
            ]);

            $aiResponse = $response->choices[0]->message->content;
            $constraints = $this->applyAgentConstraints($aiResponse, $agent, $product);

            return [
                'success' => true,
                'response' => $constraints['response'],
                'actions' => $constraints['actions'],
                'confidence' => $this->calculateConfidence($response),
                'tokens_used' => $response->usage->totalTokens,
                'rag_sources' => $relevantDocs, // Include source documents
                'rag_used' => count($relevantDocs) > 0
            ];

        } catch (\Exception $e) {
            // Fallback to regular response generation
            return $this->generateSalesResponse($customerMessage, $agent, $lead, $conversationHistory, $product);
        }
    }
    
    private function buildRAGPrompt(
        string $customerMessage,
        AiSalesAgent $agent,
        Lead $lead,
        array $conversationHistory,
        ?Product $product,
        array $relevantDocs
    ): array {
        $systemPrompt = $this->buildSystemPrompt($agent, $lead, $product);
        
        // Enhanced context with RAG documents
        $contextPrompt = $this->buildContextPrompt($agent, $lead, $product);
        
        // Add relevant document context
        if (!empty($relevantDocs)) {
            $contextPrompt .= "\n\n=== RELEVANT DOCUMENTATION ===\n";
            $contextPrompt .= "The following information from product documentation may help answer the customer's question:\n\n";
            
            foreach ($relevantDocs as $doc) {
                $contextPrompt .= "**Source:** {$doc['document_title']} ({$doc['document_type']})";
                if ($doc['section_title']) {
                    $contextPrompt .= " - {$doc['section_title']}";
                }
                if ($doc['page_number']) {
                    $contextPrompt .= " (Page {$doc['page_number']})";
                }
                $contextPrompt .= "\n";
                $contextPrompt .= "**Content:** {$doc['content']}\n";
                if ($doc['summary']) {
                    $contextPrompt .= "**Summary:** {$doc['summary']}\n";
                }
                $contextPrompt .= "**Relevance Score:** " . round($doc['similarity_score'], 2) . "\n\n";
            }
            
            $contextPrompt .= "Use this documentation to provide accurate, detailed answers. ";
            $contextPrompt .= "Reference specific sections when helpful, and mention that you can provide more detailed documentation if needed.\n";
            $contextPrompt .= "===============================\n";
        }
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => $contextPrompt],
        ];

        // Add conversation history
        foreach ($conversationHistory as $message) {
            $messages[] = [
                'role' => $message['from_customer'] ? 'user' : 'assistant',
                'content' => $message['content']
            ];
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $customerMessage];

        return $messages;
    }
}
```

---

## Phase 3: Job Queue Implementation

### 3.1 Document Processing Job
```php
// app/Jobs/ProcessDocumentForRAG.php

class ProcessDocumentForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for large documents
    public $tries = 3;
    
    private $attachmentId;

    public function __construct(int $attachmentId)
    {
        $this->attachmentId = $attachmentId;
        $this->onQueue('rag_processing'); // Dedicated queue
    }

    public function handle(RagDocumentService $ragService)
    {
        $attachment = ProductAttachment::find($this->attachmentId);
        
        if (!$attachment) {
            Log::warning("ProductAttachment {$this->attachmentId} not found for RAG processing");
            return;
        }
        
        try {
            Log::info("Starting RAG processing for attachment {$attachment->id}: {$attachment->original_filename}");
            
            $result = $ragService->processDocument($attachment);
            
            Log::info("RAG processing completed for attachment {$attachment->id}", [
                'vectors_created' => $result['vectors_created'],
                'product_id' => $attachment->product_id
            ]);
            
        } catch (Exception $e) {
            Log::error("RAG processing failed for attachment {$attachment->id}", [
                'error' => $e->getMessage(),
                'file' => $attachment->original_filename
            ]);
            
            throw $e;
        }
    }
    
    public function failed(Exception $exception)
    {
        $attachment = ProductAttachment::find($this->attachmentId);
        
        if ($attachment) {
            $attachment->update([
                'processing_status' => 'failed',
                'is_processed' => false
            ]);
        }
        
        Log::error("ProcessDocumentForRAG job failed permanently", [
            'attachment_id' => $this->attachmentId,
            'exception' => $exception->getMessage()
        ]);
    }
}
```

### 3.2 Queue Configuration
```php
// config/queue.php - Add new queue

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
    
    // Add dedicated RAG processing queue
    'rag_redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'rag_processing',
        'retry_after' => 600, // 10 minutes
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

---

## Phase 4: API Endpoints & Controllers

### 4.1 Enhanced Product Attachment Controller
```php
// app/Http/Controllers/Api/ProductAttachmentController.php

class ProductAttachmentController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'files.*' => 'required|file|max:51200', // 50MB max per file
            'attachment_types.*' => 'required|in:brochure,manual,profile,case_study,certificate,contract_template,technical_spec,other',
            'titles.*' => 'nullable|string|max:255',
            'descriptions.*' => 'nullable|string',
            'is_public.*' => 'boolean',
            'process_with_rag' => 'boolean' // Option to enable RAG processing
        ]);

        $uploadedFiles = [];
        $files = $request->file('files');
        $types = $request->input('attachment_types');
        $titles = $request->input('titles', []);
        $descriptions = $request->input('descriptions', []);
        $isPublic = $request->input('is_public', []);
        $processRAG = $request->input('process_with_rag', true);

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
                    'processing_status' => $processRAG ? 'pending' : 'completed',
                    'is_processed' => !$processRAG
                ]);
                
                // Dispatch RAG processing job if enabled and file type is supported
                if ($processRAG && $this->isRAGSupported($attachment->mime_type)) {
                    ProcessDocumentForRAG::dispatch($attachment->id)
                        ->onQueue('rag_processing')
                        ->delay(now()->addSeconds(10)); // Small delay to ensure transaction commits
                }

                $uploadedFiles[] = [
                    'id' => $attachment->id,
                    'title' => $attachment->title,
                    'type' => $attachment->attachment_type,
                    'filename' => $attachment->original_filename,
                    'size' => $attachment->formatted_size,
                    'rag_processing' => $processRAG && $this->isRAGSupported($attachment->mime_type),
                    'url' => $attachment->url
                ];
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'files' => $uploadedFiles,
                'rag_processing_queued' => $processRAG ? count(array_filter($uploadedFiles, fn($f) => $f['rag_processing'])) : 0
            ]);
            
        } catch (Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function searchDocuments(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3|max:500',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);
        
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
            'results' => $results
        ]);
    }
    
    public function getProcessingStatus(ProductAttachment $attachment)
    {
        return response()->json([
            'id' => $attachment->id,
            'filename' => $attachment->original_filename,
            'processing_status' => $attachment->processing_status,
            'is_processed' => $attachment->is_processed,
            'vector_count' => $attachment->vector_count,
            'created_at' => $attachment->created_at,
            'updated_at' => $attachment->updated_at
        ]);
    }
    
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
```

---

## Phase 5: Frontend Interface Implementation

### 5.1 Enhanced Product Form with Multi-File Upload
```javascript
// resources/js/components/ProductFormRAG.vue

<template>
  <div class="product-form-container">
    <!-- Product Type Selection -->
    <div class="form-group">
      <label>Product Type</label>
      <div class="radio-group">
        <label>
          <input type="radio" v-model="form.product_type" value="tangible" @change="onProductTypeChange">
          <span>Tangible Product</span>
          <small>Physical goods with inventory tracking</small>
        </label>
        <label>
          <input type="radio" v-model="form.product_type" value="service" @change="onProductTypeChange">
          <span>Service</span>
          <small>Non-tangible offerings (consulting, software, etc.)</small>
        </label>
      </div>
    </div>

    <!-- Service-Specific Fields (shown when product_type = 'service') -->
    <div v-if="form.product_type === 'service'" class="service-fields">
      <div class="form-group">
        <label>Service Delivery Type</label>
        <select v-model="form.service_delivery_type">
          <option value="onsite">On-site</option>
          <option value="remote">Remote</option>
          <option value="hybrid">Hybrid</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Typical Duration (Days)</label>
        <input type="number" v-model="form.service_duration_days" min="1" max="3650">
      </div>
      
      <div class="form-group">
        <label>Pricing Type</label>
        <select v-model="form.pricing_type">
          <option value="project">Project-based</option>
          <option value="hourly">Hourly</option>
          <option value="daily">Daily</option>
          <option value="subscription">Subscription</option>
          <option value="one-time">One-time</option>
        </select>
      </div>
      
      <div v-if="form.pricing_type === 'hourly'" class="form-group">
        <label>Hourly Rate ($)</label>
        <input type="number" v-model="form.hourly_rate" min="0" step="0.01">
      </div>
      
      <div class="form-group">
        <label>
          <input type="checkbox" v-model="form.requires_consultation">
          Requires consultation before engagement
        </label>
      </div>
    </div>

    <!-- Multi-File Upload Section -->
    <div class="file-upload-section">
      <h3>Documentation & Attachments</h3>
      <p v-if="form.product_type === 'service'">
        <strong>Services:</strong> Upload brochures, service manuals, case studies, company profiles, and other relevant documentation.
        <br><em>RAG AI will process these documents to answer customer questions intelligently.</em>
      </p>
      
      <div class="upload-area" @drop="handleDrop" @dragover.prevent @dragenter.prevent>
        <input 
          ref="fileInput" 
          type="file" 
          multiple 
          @change="handleFileSelect"
          accept=".pdf,.doc,.docx,.txt"
          class="hidden"
        >
        
        <div class="upload-prompt" @click="$refs.fileInput.click()">
          <div class="upload-icon">📁</div>
          <p>Click to select files or drag & drop</p>
          <small>Supports: PDF, Word, Text files (Max 50MB each)</small>
        </div>
      </div>
      
      <!-- Selected Files List -->
      <div v-if="selectedFiles.length > 0" class="selected-files">
        <h4>Selected Files ({{ selectedFiles.length }})</h4>
        <div v-for="(fileData, index) in selectedFiles" :key="index" class="file-item">
          <div class="file-info">
            <strong>{{ fileData.file.name }}</strong>
            <span class="file-size">({{ formatFileSize(fileData.file.size) }})</span>
            
            <div class="file-metadata">
              <label>Type:</label>
              <select v-model="fileData.type">
                <option value="brochure">Brochure</option>
                <option value="manual">Service Manual</option>
                <option value="profile">Company Profile</option>
                <option value="case_study">Case Study</option>
                <option value="certificate">Certificate</option>
                <option value="contract_template">Contract Template</option>
                <option value="technical_spec">Technical Specification</option>
                <option value="other">Other</option>
              </select>
              
              <label>Title:</label>
              <input type="text" v-model="fileData.title" placeholder="Document title">
              
              <label>Description:</label>
              <textarea v-model="fileData.description" placeholder="Brief description"></textarea>
              
              <label>
                <input type="checkbox" v-model="fileData.isPublic" checked>
                Share with customers
              </label>
              
              <label>
                <input type="checkbox" v-model="fileData.processRAG" checked>
                Enable AI document processing (RAG)
              </label>
            </div>
          </div>
          
          <button @click="removeFile(index)" class="remove-file">×</button>
        </div>
      </div>
      
      <!-- RAG Processing Options -->
      <div v-if="selectedFiles.some(f => f.processRAG)" class="rag-info">
        <h4>🤖 AI Document Processing (RAG)</h4>
        <p>
          Selected documents will be processed by AI to enable intelligent question answering.
          The system will:
        </p>
        <ul>
          <li>Extract and chunk document content</li>
          <li>Generate embeddings for semantic search</li>
          <li>Enable AI to reference specific document sections</li>
          <li>Provide accurate, source-backed responses to customer questions</li>
        </ul>
        <div class="processing-note">
          <em>⏱️ Processing typically takes 1-5 minutes per document depending on size.</em>
        </div>
      </div>
    </div>

    <!-- Existing Attachments (for editing) -->
    <div v-if="existingAttachments.length > 0" class="existing-attachments">
      <h3>Existing Documents</h3>
      <div v-for="attachment in existingAttachments" :key="attachment.id" class="attachment-item">
        <div class="attachment-info">
          <strong>{{ attachment.title }}</strong>
          <span class="badge" :class="attachment.attachment_type">{{ attachment.attachment_type }}</span>
          
          <div class="processing-status">
            <span v-if="attachment.processing_status === 'pending'" class="status pending">⏳ RAG Processing Pending</span>
            <span v-else-if="attachment.processing_status === 'processing'" class="status processing">🔄 Processing...</span>
            <span v-else-if="attachment.processing_status === 'completed'" class="status completed">✅ RAG Ready ({{ attachment.vector_count }} chunks)</span>
            <span v-else-if="attachment.processing_status === 'failed'" class="status failed">❌ Processing Failed</span>
          </div>
        </div>
        
        <div class="attachment-actions">
          <a :href="attachment.url" target="_blank" class="btn-view">View</a>
          <button @click="reprocessRAG(attachment.id)" v-if="attachment.processing_status === 'failed'" class="btn-retry">Retry RAG</button>
          <button @click="deleteAttachment(attachment.id)" class="btn-delete">Delete</button>
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div class="form-actions">
      <button @click="saveProduct" :disabled="isSaving" class="btn-primary">
        {{ isSaving ? 'Saving...' : 'Save Product' }}
      </button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      form: {
        product_type: 'tangible',
        name: '',
        sku: '',
        category: '',
        description: '',
        retail_price: 0,
        // Service fields
        service_delivery_type: null,
        service_duration_days: null,
        pricing_type: null,
        hourly_rate: null,
        requires_consultation: false
      },
      selectedFiles: [],
      existingAttachments: [],
      isSaving: false
    };
  },
  
  methods: {
    onProductTypeChange() {
      if (this.form.product_type === 'tangible') {
        // Clear service-specific fields
        this.form.service_delivery_type = null;
        this.form.service_duration_days = null;
        this.form.pricing_type = null;
        this.form.hourly_rate = null;
        this.form.requires_consultation = false;
      }
    },
    
    handleFileSelect(event) {
      this.addFiles(event.target.files);
    },
    
    handleDrop(event) {
      event.preventDefault();
      this.addFiles(event.dataTransfer.files);
    },
    
    addFiles(fileList) {
      for (let file of fileList) {
        if (this.isFileSupported(file)) {
          this.selectedFiles.push({
            file: file,
            type: this.suggestFileType(file.name),
            title: file.name,
            description: '',
            isPublic: true,
            processRAG: true
          });
        }
      }
    },
    
    isFileSupported(file) {
      const supportedTypes = ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
      return supportedTypes.includes(file.type);
    },
    
    suggestFileType(filename) {
      const lower = filename.toLowerCase();
      if (lower.includes('brochure') || lower.includes('flyer')) return 'brochure';
      if (lower.includes('manual') || lower.includes('guide')) return 'manual';
      if (lower.includes('profile') || lower.includes('about')) return 'profile';
      if (lower.includes('case') || lower.includes('study')) return 'case_study';
      if (lower.includes('certificate') || lower.includes('cert')) return 'certificate';
      if (lower.includes('contract') || lower.includes('terms')) return 'contract_template';
      if (lower.includes('spec') || lower.includes('technical')) return 'technical_spec';
      return 'other';
    },
    
    removeFile(index) {
      this.selectedFiles.splice(index, 1);
    },
    
    formatFileSize(bytes) {
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      if (bytes === 0) return '0 Byte';
      const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
      return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    },
    
    async saveProduct() {
      this.isSaving = true;
      
      try {
        // Step 1: Save product basic info
        const productResponse = await this.$http.post('/api/products', this.form);
        const productId = productResponse.data.product.id;
        
        // Step 2: Upload files if any
        if (this.selectedFiles.length > 0) {
          const formData = new FormData();
          
          this.selectedFiles.forEach((fileData, index) => {
            formData.append(`files[${index}]`, fileData.file);
            formData.append(`attachment_types[${index}]`, fileData.type);
            formData.append(`titles[${index}]`, fileData.title);
            formData.append(`descriptions[${index}]`, fileData.description);
            formData.append(`is_public[${index}]`, fileData.isPublic);
          });
          
          formData.append('process_with_rag', this.selectedFiles.some(f => f.processRAG));
          
          const uploadResponse = await this.$http.post(`/api/products/${productId}/attachments`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
          
          this.$toast.success(`Product saved! ${uploadResponse.data.rag_processing_queued} documents queued for AI processing.`);
        } else {
          this.$toast.success('Product saved successfully!');
        }
        
        // Redirect to product list
        this.$router.push('/products');
        
      } catch (error) {
        this.$toast.error('Failed to save product: ' + error.response?.data?.message);
      } finally {
        this.isSaving = false;
      }
    },
    
    async reprocessRAG(attachmentId) {
      try {
        await this.$http.post(`/api/attachments/${attachmentId}/reprocess-rag`);
        this.$toast.success('RAG reprocessing started');
        this.loadExistingAttachments();
      } catch (error) {
        this.$toast.error('Failed to start reprocessing');
      }
    },
    
    async deleteAttachment(attachmentId) {
      if (confirm('Delete this attachment? This will also remove all AI-processed content.')) {
        try {
          await this.$http.delete(`/api/attachments/${attachmentId}`);
          this.$toast.success('Attachment deleted');
          this.loadExistingAttachments();
        } catch (error) {
          this.$toast.error('Failed to delete attachment');
        }
      }
    }
  }
};
</script>
```

---

## Phase 6: Enhanced AI Integration

### 6.1 Updated AI WhatsApp Service
```php
// Update app/Services/AiWhatsAppService.php

class AiWhatsAppService
{
    private $openAiService;
    private $ragSearchService;

    public function __construct(OpenAiService $openAiService, RagSearchService $ragSearchService)
    {
        $this->openAiService = $openAiService;
        $this->ragSearchService = $ragSearchService;
    }

    public function processIncomingMessage(IncomingMessage $message): array
    {
        try {
            DB::beginTransaction();

            $lead = $this->findOrCreateLead($message);
            $agent = $this->findBestAgent($message, $lead);
            
            if (!$agent || !$agent->isAvailableNow()) {
                DB::rollback();
                return $this->handleUnavailableAgent($agent);
            }

            $conversationHistory = $this->getConversationHistory($lead, 10);
            $sentiment = $this->openAiService->analyzeSentiment($message->message_body);
            $product = $this->identifyProduct($message, $lead);

            // Enhanced: Use RAG-augmented AI response
            $aiResult = $this->openAiService->generateSalesResponseWithRAG(
                $message->message_body,
                $agent,
                $lead,
                $conversationHistory,
                $product
            );

            if (!$aiResult['success']) {
                DB::rollback();
                return $aiResult;
            }

            $actionResults = $this->processAiActions($aiResult['actions'], $agent, $lead, $product);
            
            // Enhanced conversation save with RAG sources
            $conversation = $this->saveConversation(
                $lead,
                $message,
                $aiResult,
                $sentiment,
                $product,
                $aiResult['rag_sources'] ?? []
            );

            $this->updateLeadEngagement($lead, $sentiment, $aiResult);

            DB::commit();

            return [
                'success' => true,
                'response' => $aiResult['response'],
                'conversation_id' => $conversation->id,
                'actions_taken' => $actionResults,
                'rag_enhanced' => $aiResult['rag_used'] ?? false,
                'sources_used' => count($aiResult['rag_sources'] ?? [])
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('AI message processing error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function saveConversation(Lead $lead, IncomingMessage $message, array $aiResult, string $sentiment, ?Product $product, array $ragSources = []): Conversation
    {
        $conversation = Conversation::create([
            'lead_id' => $lead->id,
            'product_id' => $product?->id,
            'incoming_message_id' => $message->id,
            'ai_response' => $aiResult['response'],
            'ai_confidence' => $aiResult['confidence'] ?? 0,
            'sentiment' => $sentiment,
            'tokens_used' => $aiResult['tokens_used'] ?? 0,
            'rag_sources' => json_encode($ragSources), // Store RAG sources
            'rag_enhanced' => $aiResult['rag_used'] ?? false,
            'conversation_context' => json_encode([
                'customer_message' => $message->message_body,
                'phone_number' => $message->phone_number,
                'message_type' => $message->message_type,
                'sources_count' => count($ragSources)
            ]),
            'created_at' => now()
        ]);
        
        return $conversation;
    }
}
```

---

## Phase 7: Performance & Monitoring

### 7.1 RAG Performance Monitoring
```php
// app/Console/Commands/MonitorRAGPerformance.php

class MonitorRAGPerformance extends Command
{
    protected $signature = 'rag:monitor';
    protected $description = 'Monitor RAG system performance and health';

    public function handle()
    {
        $this->info('RAG System Performance Report');
        $this->info('================================');
        
        // Document processing status
        $pending = ProductAttachment::where('processing_status', 'pending')->count();
        $processing = ProductAttachment::where('processing_status', 'processing')->count();
        $completed = ProductAttachment::where('processing_status', 'completed')->count();
        $failed = ProductAttachment::where('processing_status', 'failed')->count();
        
        $this->table(['Status', 'Count'], [
            ['Pending', $pending],
            ['Processing', $processing],
            ['Completed', $completed],
            ['Failed', $failed]
        ]);
        
        // Vector database stats
        $totalVectors = DocumentVector::count();
        $vectorsToday = DocumentVector::whereDate('created_at', today())->count();
        $avgVectorsPerDoc = round(DocumentVector::count() / max(ProductAttachment::where('is_processed', true)->count(), 1), 2);
        
        $this->info("\nVector Database Stats:");
        $this->info("Total vectors: {$totalVectors}");
        $this->info("Vectors created today: {$vectorsToday}");
        $this->info("Average vectors per document: {$avgVectorsPerDoc}");
        
        // Search performance
        $searchesToday = VectorSearchCache::whereDate('created_at', today())->count();
        $avgHitRate = VectorSearchCache::where('hit_count', '>', 1)->count() / max(VectorSearchCache::count(), 1) * 100;
        
        $this->info("\nSearch Performance:");
        $this->info("Searches today: {$searchesToday}");
        $this->info("Cache hit rate: " . round($avgHitRate, 1) . "%");
        
        // RAG-enhanced conversations
        $ragConversationsToday = Conversation::where('rag_enhanced', true)->whereDate('created_at', today())->count();
        $totalConversationsToday = Conversation::whereDate('created_at', today())->count();
        $ragUsageRate = $totalConversationsToday > 0 ? round($ragConversationsToday / $totalConversationsToday * 100, 1) : 0;
        
        $this->info("\nRAG Usage:");
        $this->info("RAG-enhanced conversations today: {$ragConversationsToday}");
        $this->info("Total conversations today: {$totalConversationsToday}");
        $this->info("RAG usage rate: {$ragUsageRate}%");
        
        // Failed processing alerts
        if ($failed > 0) {
            $this->warn("\n⚠️  {$failed} documents failed processing. Check logs for details.");
        }
        
        if ($pending > 50) {
            $this->warn("\n⚠️  High processing queue: {$pending} documents pending. Consider scaling workers.");
        }
    }
}
```

### 7.2 Cleanup Commands
```php
// app/Console/Commands/CleanupRAGData.php

class CleanupRAGData extends Command
{
    protected $signature = 'rag:cleanup {--days=30 : Days to keep}';
    protected $description = 'Clean up old RAG data and cache';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);
        
        // Clean expired search cache
        $expiredCache = VectorSearchCache::where('expiry_time', '<', now())->count();
        VectorSearchCache::where('expiry_time', '<', now())->delete();
        $this->info("Deleted {$expiredCache} expired search cache entries");
        
        // Clean old cache with low hit count
        $lowHitCache = VectorSearchCache::where('hit_count', '<=', 1)
            ->where('created_at', '<', $cutoffDate)
            ->count();
        VectorSearchCache::where('hit_count', '<=', 1)
            ->where('created_at', '<', $cutoffDate)
            ->delete();
        $this->info("Deleted {$lowHitCache} low-usage cache entries older than {$days} days");
        
        // Report on document processing status
        $oldFailedDocs = ProductAttachment::where('processing_status', 'failed')
            ->where('updated_at', '<', $cutoffDate)
            ->count();
            
        if ($oldFailedDocs > 0) {
            $this->warn("Found {$oldFailedDocs} documents that failed processing over {$days} days ago");
            $this->warn("Consider reviewing and reprocessing these documents");
        }
        
        $this->info('RAG cleanup completed');
    }
}
```

---

## Implementation Timeline

### **Week 1: Foundation**
- [ ] Create database migrations (product_type, attachments, vectors, cache)
- [ ] Set up basic models and relationships
- [ ] Install required packages (spatie/pdf-to-text, doctrine/dbal)
- [ ] Configure RAG processing queue

### **Week 2: Core RAG Services**
- [ ] Implement RagDocumentService for text extraction and chunking
- [ ] Build RagSearchService for vector similarity search
- [ ] Create ProcessDocumentForRAG job
- [ ] Enhance OpenAiService with embedding generation

### **Week 3: API & Integration**
- [ ] Build ProductAttachmentController with multi-file upload
- [ ] Update AiWhatsAppService to use RAG responses
- [ ] Create search and monitoring endpoints
- [ ] Implement conversation tracking with RAG sources

### **Week 4: Frontend & Testing**
- [ ] Build Vue.js file upload component with product type handling
- [ ] Create document management interface
- [ ] Implement processing status indicators
- [ ] Performance testing and optimization

### **Week 5: Deployment & Monitoring**
- [ ] Deploy to production with queue workers
- [ ] Set up monitoring commands and alerts
- [ ] Create documentation and training materials
- [ ] Performance tuning and cache optimization

---

## Success Metrics

### **Technical Metrics**
- **Document Processing Rate:** 95%+ success rate for supported file types
- **Vector Search Speed:** <500ms average response time
- **Cache Hit Rate:** >70% for repeated queries
- **RAG Usage Rate:** >80% of service-related conversations enhanced

### **Business Metrics**
- **AI Response Quality:** Improved accuracy with source citations
- **Customer Engagement:** Longer conversation threads with relevant information
- **Lead Conversion:** Higher conversion rate for service products
- **Agent Efficiency:** Reduced need for human escalation

### **User Experience**
- **Upload Experience:** Intuitive drag-drop with clear processing status
- **AI Responses:** Contextual answers with document references
- **Search Functionality:** Fast, relevant document content retrieval
- **Mobile Compatibility:** Full functionality on mobile devices

---

This enhanced implementation provides a complete RAG-enabled product and service management system that processes uploaded documents, stores content in vector databases, and enables intelligent AI conversations with source-backed responses.