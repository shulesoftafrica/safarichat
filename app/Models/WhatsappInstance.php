<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappInstance extends Model
{
    protected $fillable = [
        'user_id',
        'instance_id',
        'instance_name',
        'phone_number',
        'access_token',
        'webhook_url',
        'status',
        'connect_status',
        'webhook_verified',
        'metadata',
        'last_seen',
        'last_message_received',
        'total_messages_received'
    ];

    protected $casts = [
        'metadata' => 'array',
        'webhook_verified' => 'boolean',
        'last_seen' => 'datetime',
        'last_message_received' => 'datetime'
    ];

    /**
     * Get the user that owns this instance
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get incoming messages for this instance
     */
    public function incomingMessages()
    {
        return $this->hasMany(IncomingMessage::class, 'instance_id', 'instance_id');
    }

    /**
     * Get outgoing messages for this instance
     */
    public function outgoingMessages()
    {
        return $this->hasMany(OutgoingMessage::class, 'instance_id', 'instance_id');
    }

    /**
     * Check if instance is ready
     */
    public function isReady()
    {
        return $this->connect_status === 'ready';
    }

    /**
     * Check if instance is connected
     */
    public function isConnected()
    {
        return in_array($this->connect_status, ['ready', 'connecting']);
    }

    /**
     * Update message received stats
     */
    public function incrementMessageCount()
    {
        $this->increment('total_messages_received');
        $this->update(['last_message_received' => now()]);
    }

    /**
     * Get recent message statistics
     */
    public function getMessageStats($days = 7)
    {
        $from = now()->subDays($days);
        
        return [
            'incoming_count' => $this->incomingMessages()->where('created_at', '>=', $from)->count(),
            'outgoing_count' => $this->outgoingMessages()->where('created_at', '>=', $from)->count(),
            'unique_contacts' => $this->incomingMessages()->where('created_at', '>=', $from)->distinct('phone_number')->count()
        ];
    }
}
