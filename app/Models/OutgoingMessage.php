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
        'scheduled_at',
        'queued_at',
        'error_message',
        'retry_count',
        'metadata',
        'priority',
        'provider',
        'external_id'
    ];

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'queued_at' => 'datetime'
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

    // ===== NOTIFICATION API METHODS =====

    /**
     * Scope to get messages for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get messages by batch
     */
    public function scopeBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to get messages by provider
     */
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get messages by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Update message with external API response
     */
    public function updateFromApiResponse($response, $externalId = null)
    {
        $updateData = [
            'waapi_response' => $response,
            'sent_at' => now()
        ];

        if ($externalId) {
            $updateData['external_id'] = $externalId;
        }

        // Map API status to our internal status
        if (isset($response['status'])) {
            $updateData['status'] = $this->mapApiStatus($response['status']);
        }

        $this->update($updateData);
    }

    /**
     * Map external API status to internal status
     */
    protected function mapApiStatus($apiStatus)
    {
        $mapping = [
            'sent' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
            'pending' => 'pending'
        ];

        return $mapping[$apiStatus] ?? 'pending';
    }

    /**
     * Create a notification message record
     */
    public static function createNotification($data)
    {
        return self::create([
            'user_id' => $data['user_id'],
            'phone_number' => $data['to'],
            'message_body' => $data['message'],
            'message_type' => $data['message_type'] ?? 'text',
            'status' => 'pending',
            'provider' => 'unified_api',
            'priority' => $data['priority'] ?? 'normal',
            'metadata' => $data['metadata'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'events_guest_id' => $data['events_guest_id'] ?? null,
            'media_path' => $data['attachment_path'] ?? null,
            'caption' => $data['caption'] ?? null,
        ]);
    }

    /**
     * Get notification statistics for user
     */
    public static function getNotificationStats($userId, $days = 30)
    {
        $query = self::forUser($userId)
            ->where('provider', 'unified_api')
            ->where('created_at', '>=', now()->subDays($days));

        $stats = [
            'total' => $query->count(),
            'sent' => $query->where('status', 'sent')->count(),
            'delivered' => $query->where('status', 'delivered')->count(),
            'failed' => $query->where('status', 'failed')->count(),
            'pending' => $query->where('status', 'pending')->count(),
        ];

        $stats['success_rate'] = $stats['total'] > 0 
            ? round((($stats['sent'] + $stats['delivered']) / $stats['total']) * 100, 2)
            : 0;

        return $stats;
    }

    /**
     * Get batch statistics
     */
    public static function getBatchStats($batchId)
    {
        $query = self::batch($batchId);
        
        return [
            'total' => $query->count(),
            'sent' => $query->where('status', 'sent')->count(),
            'delivered' => $query->where('status', 'delivered')->count(),
            'failed' => $query->where('status', 'failed')->count(),
            'pending' => $query->where('status', 'pending')->count(),
        ];
    }
}
