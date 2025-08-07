<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingMessage extends Model
{
    protected $fillable = [
        'user_id',
        'instance_id',
        'message_id',
        'events_guest_id',
        'chat_id',
        'phone_number',
        'sender_name',
        'message_body',
        'message_type',
        'media_data',
        'from_me',
        'is_group',
        'message_timestamp',
        'status',
        'auto_reply',
        'metadata'
    ];

    protected $casts = [
        'media_data' => 'array',
        'metadata' => 'array',
        'from_me' => 'boolean',
        'is_group' => 'boolean',
        'message_timestamp' => 'datetime'
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
     * Scope to get recent messages
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get unprocessed messages
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope to get messages from specific phone
     */
    public function scopeFromPhone($query, $phone)
    {
        return $query->where('phone_number', $phone);
    }

    /**
     * Mark message as processed
     */
    public function markAsProcessed($autoReply = null)
    {
        $this->update([
            'status' => 'processed',
            'auto_reply' => $autoReply
        ]);
    }

    /**
     * Mark message as replied
     */
    public function markAsReplied($replyText = null)
    {
        $this->update([
            'status' => 'replied',
            'auto_reply' => $replyText
        ]);
    }
}
