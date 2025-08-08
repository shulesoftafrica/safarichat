<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingMessage extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'instance_id',
        'events_guest_id',
        'chat_id',
        'phone_number',
        'message',
        'message_body',
        'message_type',
        'media_path',
        'media_url',
        'caption',
        'status',
        'delivery_status',
        'job_id',
        'batch_id',
        'waapi_message_id',
        'waapi_response',
        'api_response',
        'scheduled_at',
        'queued_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
        'retry_count',
        'metadata'
    ];

    protected $casts = [
        'waapi_response' => 'array',
        'api_response' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime'
    ];

    /**
     * Get the original message
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user that owns this message
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated guest
     */
    public function guest()
    {
        return $this->belongsTo(EventsGuest::class, 'events_guest_id');
    }

    /**
     * Get the WhatsApp instance
     */
    public function whatsappInstance()
    {
        return $this->belongsTo(WhatsappInstance::class, 'instance_id', 'instance_id');
    }

    /**
     * Scope to get pending messages
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get scheduled messages
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'pending')
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope to get failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark message as processing
     */
    public function markAsProcessing($jobId = null)
    {
        $this->update([
            'status' => 'processing',
            'job_id' => $jobId,
            'queued_at' => now()
        ]);
    }

    /**
     * Mark message as sent
     */
    public function markAsSent($waapiMessageId, $waapiResponse = null)
    {
        $this->update([
            'status' => 'sent',
            'waapi_message_id' => $waapiMessageId,
            'waapi_response' => $waapiResponse,
            'sent_at' => now()
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1
        ]);
    }

    /**
     * Check if message can be retried
     */
    public function canRetry()
    {
        return $this->retry_count < 3 && $this->status === 'failed';
    }

    /**
     * Get success rate for a user
     */
    public static function getSuccessRate($userId, $days = 30)
    {
        $total = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        if ($total === 0) {
            return 0;
        }

        $successful = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['sent', 'delivered'])
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get delivery statistics
     */
    public static function getDeliveryStats($userId, $days = 30)
    {
        $query = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days));

        return [
            'total' => $query->count(),
            'sent' => $query->where('status', 'sent')->count(),
            'delivered' => $query->where('delivery_status', 'delivered')->count(),
            'failed' => $query->where('status', 'failed')->count(),
            'pending' => $query->where('status', 'pending')->count(),
        ];
    }

    /**
     * Scope for today's messages
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
