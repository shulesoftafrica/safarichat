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

    // ===== NOTIFICATION API METHODS =====

    /**
     * Scope to get active/connected instances
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['connected', 'active']);
    }

    /**
     * Scope to get instances for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get WaSender instances
     */
    public function scopeWaSender($query)
    {
        return $query->where('platform', 'wasender');
    }

    /**
     * Check if instance can send messages
     */
    public function canSendMessages()
    {
        return in_array($this->status, ['connected', 'active']) && 
               !empty($this->api_key);
    }

    /**
     * Get notification message capacity
     */
    public function getNotificationCapacity()
    {
        // Base capacity on connection status
        $baseCapacity = match($this->status) {
            'connected', 'active' => 1000,
            'connecting' => 100,
            default => 0
        };

        // Adjust based on recent activity
        $recentStats = $this->getMessageStats(1); // Last 24 hours
        $recentLoad = $recentStats['outgoing_count'];

        return max(0, $baseCapacity - $recentLoad);
    }

    /**
     * Update instance status from API response
     */
    public function updateFromApiResponse($apiResponse)
    {
        $updateData = [];

        if (isset($apiResponse['status'])) {
            $updateData['status'] = $apiResponse['status'];
        }

        if (isset($apiResponse['device_info'])) {
            $updateData['device_info'] = $apiResponse['device_info'];
        }

        if (isset($apiResponse['qrCode'])) {
            $updateData['qr_code'] = $apiResponse['qrCode'];
            $updateData['qr_code_generated'] = true;
            $updateData['qr_code_generated_at'] = now();
        }

        if ($updateData) {
            $updateData['last_active_at'] = now();
            $this->update($updateData);
        }
    }

    /**
     * Create instance for notification API
     */
    public static function createForNotificationApi($data)
    {
        return self::create([
            'user_id' => $data['user_id'],
            'instance_id' => $data['wasender_session_id'],
            'instance_name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'status' => 'disconnected',
            'platform' => 'wasender',
            'api_key' => $data['api_key'] ?? null,
            'webhook_url' => $data['webhook_url'] ?? null,
            'metadata' => [
                'created_via' => 'notification_api',
                'account_protection' => $data['account_protection'] ?? true,
                'log_messages' => $data['log_messages'] ?? true,
                'webhook_events' => $data['webhook_events'] ?? [],
            ]
        ]);
    }

    /**
     * Get instances ready for notifications
     */
    public static function getNotificationReady($userId = null)
    {
        $query = self::active()->where('platform', 'wasender');
        
        if ($userId) {
            $query->forUser($userId);
        }

        return $query->whereNotNull('api_key')->get();
    }

    /**
     * Get notification statistics for this instance
     */
    public function getNotificationStats($days = 30)
    {
        return $this->outgoingMessages()
            ->where('provider', 'unified_api')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->first();
    }
}
