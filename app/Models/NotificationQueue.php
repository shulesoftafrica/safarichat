<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationQueue extends Model
{
    use HasFactory;
    
    protected $table = 'notification_queue';

    protected $fillable = [
        'user_id',
        'notification_type',
        'category',
        'priority',
        'recipient',
        'subject',
        'message',
        'template_data',
        'scheduled_for',
        'sent_at',
        'status',
        'failure_reason',
        'retry_count',
        'max_retries'
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'template_data' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries && $this->status !== 'sent';
    }
}
