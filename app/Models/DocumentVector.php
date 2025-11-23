<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVector extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_attachment_id',
        'product_id',
        'chunk_index',
        'content_text',
        'content_summary',
        'page_number',
        'section_title',
        'embedding_vector',
        'metadata'
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'page_number' => 'integer',
        'embedding_vector' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Belongs to product attachment
     */
    public function attachment()
    {
        return $this->belongsTo(ProductAttachment::class, 'product_attachment_id');
    }

    /**
     * Belongs to product (denormalized for faster queries)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get word count from metadata
     */
    public function getWordCountAttribute(): int
    {
        return $this->metadata['word_count'] ?? 0;
    }

    /**
     * Get character count from metadata
     */
    public function getCharCountAttribute(): int
    {
        return $this->metadata['char_count'] ?? strlen($this->content_text);
    }

    /**
     * Get document type from metadata
     */
    public function getDocumentTypeAttribute(): ?string
    {
        return $this->metadata['document_type'] ?? null;
    }

    /**
     * Get original filename from metadata
     */
    public function getOriginalFilenameAttribute(): ?string
    {
        return $this->metadata['file_name'] ?? null;
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for specific attachment
     */
    public function scopeForAttachment($query, int $attachmentId)
    {
        return $query->where('product_attachment_id', $attachmentId);
    }

    /**
     * Get preview text (first 200 characters)
     */
    public function getPreviewTextAttribute(): string
    {
        return strlen($this->content_text) > 200 
            ? substr($this->content_text, 0, 200) . '...'
            : $this->content_text;
    }

    /**
     * Get formatted section info for display
     */
    public function getSectionInfoAttribute(): string
    {
        $info = [];
        
        if ($this->section_title) {
            $info[] = $this->section_title;
        }
        
        if ($this->page_number) {
            $info[] = "Page {$this->page_number}";
        }
        
        return implode(' - ', $info) ?: 'Chunk ' . ($this->chunk_index + 1);
    }
}