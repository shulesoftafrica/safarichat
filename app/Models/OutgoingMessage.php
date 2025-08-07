<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingMessage extends Model
{
    protected $fillable = [
        'user_id',
        'instance_id',
        'events_guest_id',
        'chat_id',
        'phone_number',
        'message_body',
        'message_type',
        'media_path',
        'media_url',
        'caption',
        'status',
        'waapi_message_id',
        'waapi_response',
        'scheduled_at',
        'sent_at',
        'error_message',
        'retry_count'
    ];

    protected $casts = [
        'waapi_response' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime'
    ];

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
}
