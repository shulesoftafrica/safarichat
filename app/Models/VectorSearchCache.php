<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VectorSearchCache extends Model
{
    use HasFactory;

    protected $table = 'vector_search_cache';

    protected $fillable = [
        'query_hash',
        'query_text',
        'product_ids',
        'search_results',
        'expiry_time',
        'hit_count'
    ];

    protected $casts = [
        'product_ids' => 'array',
        'search_results' => 'array',
        'expiry_time' => 'datetime',
        'hit_count' => 'integer',
    ];

    /**
     * Scope for non-expired entries
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expiry_time', '>', now());
    }

    /**
     * Scope for expired entries
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_time', '<=', now());
    }

    /**
     * Scope for high-hit entries (popular searches)
     */
    public function scopePopular($query, int $minHits = 5)
    {
        return $query->where('hit_count', '>=', $minHits);
    }

    /**
     * Check if cache entry is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_time <= now();
    }

    /**
     * Increment hit count
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
    }

    /**
     * Get formatted expiry time
     */
    public function getFormattedExpiryAttribute(): string
    {
        return $this->expiry_time->format('Y-m-d H:i:s');
    }

    /**
     * Get results count
     */
    public function getResultsCountAttribute(): int
    {
        return count($this->search_results);
    }
}