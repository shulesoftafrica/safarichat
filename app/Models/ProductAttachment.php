<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 
        'attachment_type', 
        'file_path', 
        'original_filename',
        'mime_type', 
        'file_size', 
        'title', 
        'description', 
        'is_public',
        'display_order',
        'is_processed',
        'processing_status',
        'vector_count',
        'processing_error'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_processed' => 'boolean',
        'file_size' => 'integer',
        'display_order' => 'integer',
        'vector_count' => 'integer',
    ];

    /**
     * Belongs to product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Has many document vectors
     */
    public function vectors()
    {
        return $this->hasMany(DocumentVector::class);
    }

    /**
     * Get full URL for attachment
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute()
    {
        return route('product.attachment.download', $this->id);
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSizeAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if file is PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if file is image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is Word document
     */
    public function isWordDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }

    /**
     * Check if file is text
     */
    public function isTextFile(): bool
    {
        return $this->mime_type === 'text/plain';
    }

    /**
     * Check if file type supports RAG processing
     */
    public function supportsRAG(): bool
    {
        return $this->isPdf() || $this->isWordDocument() || $this->isTextFile();
    }

    /**
     * Get processing status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->processing_status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get processing status display text
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->processing_status) {
            'pending' => 'RAG Processing Pending',
            'processing' => 'Processing...',
            'completed' => 'RAG Ready (' . $this->vector_count . ' chunks)',
            'failed' => 'Processing Failed',
            default => 'Unknown'
        };
    }

    /**
     * Scope for processed documents
     */
    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true)->where('processing_status', 'completed');
    }

    /**
     * Scope for public documents
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for specific attachment type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('attachment_type', $type);
    }

    /**
     * Get absolute file path on disk
     */
    public function getAbsolutePathAttribute(): string
    {
        return storage_path('app/public/' . $this->file_path);
    }

    /**
     * Delete file from storage when model is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($attachment) {
            // Delete file from storage
            Storage::delete($attachment->file_path);
            
            // Delete associated vectors (cascade should handle this, but just in case)
            $attachment->vectors()->delete();
        });
    }
}