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
        'api_key',
        // Multi-instance support fields
        'uuid',
        'purpose',
        'instance_description',
        'is_primary',
        'display_name',
        // System default instance fields
        'is_system_default',
        'usage_scope',
        'allowed_message_types',
        // Unified Notification API registration
        'unified_api_registered_at',
        // CS routing
        'instance_type',
    ];

    protected $casts = [
        'metadata' => 'array',
        'device_info' => 'array',
        'webhook_verified' => 'boolean',
        'qr_code_generated' => 'boolean',
        'is_primary' => 'boolean',
        'is_system_default' => 'boolean',
        'allowed_message_types' => 'array',
        'last_seen' => 'datetime',
        'last_message_received' => 'datetime',
        'qr_code_generated_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_active_at' => 'datetime',
        'unified_api_registered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Boot method to generate UUID on creation
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Override getAttribute to handle malformed date values
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        // Handle malformed date attributes (like PostgreSQL error codes)
        if (in_array($key, ['created_at', 'updated_at']) && $value !== null) {
            // If value is not a Carbon instance and looks like an error code, return null
            if (!$value instanceof \Carbon\Carbon) {
                if (is_string($value) || is_numeric($value)) {
                    // Check if it's just a number (like error code 42703)
                    if (is_numeric($value) && strlen((string)$value) < 8) {
                        \Log::warning("Invalid date value for {$key}: {$value} in WhatsappInstance ID: {$this->id}");
                        return null;
                    }
                }
            }
        }
        
        return $value;
    }

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
     * Get incoming messages for this instance (using new relationship)
     */
    public function incomingMessages()
    {
        return $this->hasMany(IncomingMessage::class, 'whatsapp_instance_id');
    }

    /**
     * Get outgoing messages for this instance (using new relationship)
     */
    public function outgoingMessages()
    {
        return $this->hasMany(OutgoingMessage::class, 'whatsapp_instance_id');
    }

    /**
     * New scopes and methods for multi-instance support
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByPurpose($query, $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    public function getDisplayNameAttribute()
    {
        return $this->attributes['display_name'] ?: $this->phone_number;
    }

    /**
     * Schema name for message routing (replaces user UUID)
     */
    public function getSchemaNameAttribute()
    {
        return $this->uuid;
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
     * Scope: non-system user instances that are fully operational.
     * BOTH columns must confirm connectivity — if either says disconnected,
     * the instance is down (connect_status=ready with status=disconnected is
     * a stale/contradictory state and must be treated as non-operational).
     * NULL connect_status is treated as "not yet synced" — trust status alone.
     */
    public function scopeOperational($query)
    {
        return $query->where('is_system_default', false)
            ->whereIn('status', ['connected', 'active'])
            ->where(function ($q) {
                $q->whereNull('connect_status')
                  ->orWhere('connect_status', 'ready');
            });
    }

    /**
     * Instance-level check: is this instance operational?
     * Mirrors scopeOperational logic for use on already-loaded collections.
     * Rule: status must be connected/active AND connect_status must be ready
     *       (or null = not yet reported, trust the status column).
     */
    public function isOperational(): bool
    {
        // Primary indicator: status must be connected or active
        if (!in_array($this->status, ['connected', 'active'])) {
            return false;
        }
        // Secondary indicator: if connect_status IS set it must be 'ready'
        // (null means not yet synced — we trust the status column in that case)
        if ($this->connect_status !== null && $this->connect_status !== 'ready') {
            return false;
        }
        return true;
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

    /**
     * Get the system default WhatsApp instance
     */
    public static function getSystemDefault(): ?WhatsappInstance
    {
        // Accept both 'connected' and 'active' — the seeder seeds with 'active',
        // and system instances don't go through WaSender connection flow.
        return static::where('is_system_default', true)
            ->where('usage_scope', 'system')
            ->whereIn('status', ['connected', 'active'])
            ->first();
    }
    
    /**
     * Check if this instance can send specific message type
     */
    public function canSendMessageType(string $messageType): bool
    {
        if ($this->usage_scope === 'user') {
            return true; // User instances can send any message
        }

        // allowed_message_types is cast as 'array' — already decoded by Eloquent.
        // Do NOT wrap in json_decode() or it double-decodes to null.
        $allowed = $this->allowed_message_types;

        // Null column = system instance was created before the column was populated.
        // Default to allowing all known system message types rather than blocking everything.
        if (empty($allowed)) {
            return in_array($messageType, array_keys(self::getSystemMessageTypes()));
        }

        return in_array($messageType, $allowed);
    }
    
    /**
     * Scope for system instances only
     */
    public function scopeSystemOnly($query)
    {
        return $query->where('usage_scope', 'system');
    }
    
    /**
     * Scope for user instances only
     */
    public function scopeUserOnly($query)
    {
        return $query->where('usage_scope', 'user');
    }

    /**
     * Check if this is a system default instance
     */
    public function isSystemDefault(): bool
    {
        return $this->is_system_default === true && $this->usage_scope === 'system';
    }

    /**
     * Get available message types for system instances
     */
    public static function getSystemMessageTypes(): array
    {
        return [
            'otp_verification' => 'OTP Verification',
            'welcome_message' => 'Welcome Message',
            'payment_reminder' => 'Payment Reminder',
            'system_notification' => 'System Notification',
            'account_verification' => 'Account Verification',
            'password_reset' => 'Password Reset'
        ];
    }
}
