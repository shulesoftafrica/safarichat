<?php

namespace App\Services;

use App\Models\DocumentVector;
use App\Models\VectorSearchCache;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Exception;

class RagSearchService
{
    private $openAiService;
    private $similarity_threshold = 0.7; // Minimum cosine similarity
    private $cache_duration_hours = 24; // Cache results for 24 hours

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    /**
     * Search documents using vector similarity
     */
    public function searchDocuments(string $query, ?array $productIds = null, int $limit = 5): array
    {
        try {
            // Step 1: Check cache first
            $cacheKey = $this->getCacheKey($query, $productIds);
            $cached = $this->getCachedResults($cacheKey);
            if ($cached) {
                Log::debug("RAG search cache hit for query: " . substr($query, 0, 50));
                return $cached;
            }

            // Step 2: Generate embedding for search query
            $queryEmbedding = $this->openAiService->generateEmbedding($query);

            // Step 3: Find similar vectors using cosine similarity
            $results = $this->findSimilarVectors($queryEmbedding, $productIds, $limit);

            // Step 4: Cache results
            $this->cacheResults($cacheKey, $query, $productIds, $results);

            Log::info("RAG search completed with fresh results", [
                'query' => substr($query, 0, 100),
                'results_count' => count($results),
                'product_ids' => $productIds
            ]);

            return $results;

        } catch (Exception $e) {
            Log::error("RAG search failed: " . $e->getMessage(), [
                'query' => substr($query, 0, 100),
                'product_ids' => $productIds
            ]);

            return [];
        }
    }

    /**
     * Find vectors with similar embeddings
     */
    private function findSimilarVectors(array $queryEmbedding, ?array $productIds, int $limit): array
    {
        $query = DocumentVector::select([
            'document_vectors.*',
            'products.name as product_name',
            'products.product_type',
            'product_attachments.attachment_type',
            'product_attachments.original_filename',
            'product_attachments.title as document_title'
        ])
        ->join('products', 'document_vectors.product_id', '=', 'products.id')
        ->join('product_attachments', 'document_vectors.product_attachment_id', '=', 'product_attachments.id')
        ->where('products.status', 'active')
        ->where('product_attachments.is_processed', true)
        ->where('product_attachments.processing_status', 'completed');

        if ($productIds && !empty($productIds)) {
            $query->whereIn('document_vectors.product_id', $productIds);
        }

        $vectors = $query->get();

        if ($vectors->isEmpty()) {
            return [];
        }

        $results = [];
        foreach ($vectors as $vector) {
            try {
                $vectorEmbedding = $vector->embedding_vector;
                
                if (!is_array($vectorEmbedding)) {
                    continue;
                }

                $similarity = $this->calculateCosineSimilarity($queryEmbedding, $vectorEmbedding);

                if ($similarity >= $this->similarity_threshold) {
                    $results[] = [
                        'id' => $vector->id,
                        'product_id' => $vector->product_id,
                        'product_name' => $vector->product_name,
                        'product_type' => $vector->product_type,
                        'document_type' => $vector->attachment_type,
                        'document_title' => $vector->document_title ?: $vector->original_filename,
                        'document_filename' => $vector->original_filename,
                        'content' => $vector->content_text,
                        'summary' => $vector->content_summary,
                        'section_title' => $vector->section_title,
                        'section_info' => $vector->section_info,
                        'page_number' => $vector->page_number,
                        'similarity_score' => round($similarity, 4),
                        'chunk_index' => $vector->chunk_index,
                        'metadata' => $vector->metadata
                    ];
                }
            } catch (Exception $e) {
                Log::warning("Error processing vector {$vector->id}: " . $e->getMessage());
                continue;
            }
        }

        // Sort by similarity score descending
        usort($results, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private function calculateCosineSimilarity(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += $vectorA[$i] * $vectorA[$i];
            $magnitudeB += $vectorB[$i] * $vectorB[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0.0 || $magnitudeB == 0.0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * Generate cache key for query
     */
    private function getCacheKey(string $query, ?array $productIds): string
    {
        $key = trim(strtolower($query));
        if ($productIds && !empty($productIds)) {
            sort($productIds);
            $key .= '|products:' . implode(',', $productIds);
        }
        return hash('sha256', $key);
    }

    /**
     * Get cached search results
     */
    private function getCachedResults(string $cacheKey): ?array
    {
        $cached = VectorSearchCache::where('query_hash', $cacheKey)
            ->notExpired()
            ->first();

        if ($cached) {
            $cached->recordHit();
            return $cached->search_results;
        }

        return null;
    }

    /**
     * Cache search results
     */
    private function cacheResults(string $cacheKey, string $query, ?array $productIds, array $results): void
    {
        try {
            VectorSearchCache::updateOrCreate(
                ['query_hash' => $cacheKey],
                [
                    'query_text' => substr($query, 0, 1000),
                    'product_ids' => $productIds,
                    'search_results' => $results,
                    'expiry_time' => now()->addHours($this->cache_duration_hours),
                    'hit_count' => 1
                ]
            );
        } catch (Exception $e) {
            Log::warning("Failed to cache RAG search results: " . $e->getMessage());
        }
    }

    /**
     * Search within specific product's documents
     */
    public function searchProductDocuments(int $productId, string $query, int $limit = 3): array
    {
        return $this->searchDocuments($query, [$productId], $limit);
    }

    /**
     * Get similar content from same document
     */
    public function findSimilarChunksInDocument(int $attachmentId, string $query, int $limit = 3): array
    {
        try {
            $queryEmbedding = $this->openAiService->generateEmbedding($query);

            $vectors = DocumentVector::where('product_attachment_id', $attachmentId)->get();

            $results = [];
            foreach ($vectors as $vector) {
                $vectorEmbedding = $vector->embedding_vector;
                
                if (!is_array($vectorEmbedding)) {
                    continue;
                }

                $similarity = $this->calculateCosineSimilarity($queryEmbedding, $vectorEmbedding);

                if ($similarity >= $this->similarity_threshold) {
                    $results[] = [
                        'chunk_index' => $vector->chunk_index,
                        'content' => $vector->content_text,
                        'summary' => $vector->content_summary,
                        'section_title' => $vector->section_title,
                        'page_number' => $vector->page_number,
                        'similarity_score' => round($similarity, 4)
                    ];
                }
            }

            usort($results, function($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });

            return array_slice($results, 0, $limit);

        } catch (Exception $e) {
            Log::error("Failed to find similar chunks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get search statistics
     */
    public function getSearchStats(): array
    {
        try {
            $totalSearches = VectorSearchCache::count();
            $searchesToday = VectorSearchCache::whereDate('created_at', today())->count();
            $popularSearches = VectorSearchCache::popular(5)
                ->orderBy('hit_count', 'desc')
                ->limit(10)
                ->get(['query_text', 'hit_count']);

            $cacheHitRate = 0;
            $totalHits = VectorSearchCache::sum('hit_count');
            if ($totalSearches > 0) {
                $cacheHitRate = round((($totalHits - $totalSearches) / $totalHits) * 100, 2);
            }

            return [
                'total_searches' => $totalSearches,
                'searches_today' => $searchesToday,
                'cache_hit_rate' => $cacheHitRate,
                'popular_searches' => $popularSearches->toArray(),
                'total_vectors' => DocumentVector::count(),
                'processed_documents' => DocumentVector::distinct('product_attachment_id')->count()
            ];

        } catch (Exception $e) {
            Log::error("Failed to get search stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean expired cache entries
     */
    public function cleanExpiredCache(): int
    {
        try {
            $deleted = VectorSearchCache::expired()->delete();
            Log::info("Cleaned {$deleted} expired RAG cache entries");
            return $deleted;
        } catch (Exception $e) {
            Log::error("Failed to clean expired cache: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update similarity threshold
     */
    public function setSimilarityThreshold(float $threshold): void
    {
        $this->similarity_threshold = max(0.0, min(1.0, $threshold));
    }

    /**
     * Get current similarity threshold
     */
    public function getSimilarityThreshold(): float
    {
        return $this->similarity_threshold;
    }
}