<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingWebhookEvent extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'event_type',
        'transaction_id',
        'billing_account_id',
        'payload',
        'processing_status',
        'error_message',
        'signature',
        'source_ip',
        'processed_at'
    ];
    
    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime'
    ];
    
    /**
     * Relationship: Belongs to BillingAccount
     */
    public function billingAccount()
    {
        return $this->belongsTo(BillingAccount::class);
    }
    
    /**
     * Scope: Successfully processed webhooks
     */
    public function scopeSuccessful($query)
    {
        return $query->where('processing_status', 'success');
    }
    
    /**
     * Scope: Failed webhooks
     */
    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }
    
    /**
     * Scope: Processing webhooks (stuck/timeout)
     */
    public function scopeProcessing($query)
    {
        return $query->where('processing_status', 'processing');
    }
    
    /**
     * Check if transaction was already processed successfully
     * This is the core idempotency check
     * 
     * @param string $transactionId The transaction ID from the webhook
     * @param string $eventType The event type (e.g., payment.success)
     * @return bool True if already processed, false otherwise
     */
    public static function isProcessed(string $transactionId, string $eventType): bool
    {
        return self::where('transaction_id', $transactionId)
            ->where('event_type', $eventType)
            ->where('processing_status', 'success')
            ->exists();
    }
    
    /**
     * Get webhooks that have been processing for too long (likely stuck)
     * 
     * @param int $minutes Number of minutes to consider as timeout
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getStuckWebhooks(int $minutes = 5)
    {
        return self::where('processing_status', 'processing')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->get();
    }
}
