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
        'total_messages_received',
        // WA Sender fields
        'qr_code_generated',
        'qr_code_generated_at',
        'qr_code',
        'connected_at',
        'disconnected_at',
        'last_active_at',
        'platform',
        'device_info',
        'api_key'
    ];

    protected $casts = [
        'metadata' => 'array',
        'device_info' => 'array',
        'webhook_verified' => 'boolean',
        'qr_code_generated' => 'boolean',
        'last_seen' => 'datetime',
        'last_message_received' => 'datetime',
        'qr_code_generated_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_active_at' => 'datetime'
    ];

    /**
     * Get the user that owns this instance
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI Sales Agent associated with this instance's user
     */
    public function aiSalesAgent()
    {
        return $this->hasOneThrough(
            AiSalesAgent::class,
            User::class,
            'id', // Foreign key on users table
            'user_id', // Foreign key on ai_sales_agents table
            'user_id', // Local key on whatsapp_instances table
            'id' // Local key on users table
        );
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
