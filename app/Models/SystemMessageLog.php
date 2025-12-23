<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemMessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_instance_id',
        'phone_number',
        'message_type',
        'message_content',
        'status',
        'sent_at',
        'delivered_at',
        'error_message'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime'
    ];

    /**
     * Relationship with WhatsApp instance
     */
    public function whatsappInstance()
    {
        return $this->belongsTo(WhatsappInstance::class);
    }

    /**
     * Scope for specific message types
     */
    public function scopeByMessageType($query, string $messageType)
    {
        return $query->where('message_type', $messageType);
    }

    /**
     * Scope for successful messages
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'read']);
    }

    /**
     * Scope for failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get message statistics for a date range
     */
    public static function getStats($startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate) {
            $query->where('sent_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('sent_at', '<=', $endDate);
        }

        return $query->selectRaw('
            message_type,
            COUNT(*) as total_sent,
            SUM(CASE WHEN status IN ("sent", "delivered", "read") THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
            AVG(CASE WHEN delivered_at IS NOT NULL AND sent_at IS NOT NULL 
                THEN TIMESTAMPDIFF(SECOND, sent_at, delivered_at) ELSE NULL END) as avg_delivery_time_seconds
        ')
        ->groupBy('message_type')
        ->get();
    }

    /**
     * Log a system message
     */
    public static function logMessage(
        int $whatsappInstanceId,
        string $phoneNumber,
        string $messageType,
        string $messageContent,
        string $status = 'sent'
    ): SystemMessageLog {
        return static::create([
            'whatsapp_instance_id' => $whatsappInstanceId,
            'phone_number' => $phoneNumber,
            'message_type' => $messageType,
            'message_content' => $messageContent,
            'status' => $status,
            'sent_at' => now()
        ]);
    }

    /**
     * Update message status
     */
    public function updateStatus(string $status, ?string $errorMessage = null): bool
    {
        $updateData = ['status' => $status];
        
        if ($status === 'delivered' && !$this->delivered_at) {
            $updateData['delivered_at'] = now();
        }
        
        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }
        
        return $this->update($updateData);
    }
}