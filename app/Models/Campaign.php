<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'product_id',
        'campaign_name',
        'campaign_type',
        'original_message',
        'recipient_criteria',
        'total_recipients',
        'queued_count',
        'analyzing_count',
        'refined_count',
        'scheduled_count',
        'sent_count',
        'failed_count',
        'human_review_count',
        'status',
        'has_attachments',
        'attachments',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'recipient_criteria' => 'array',
        'has_attachments' => 'boolean',
        'attachments' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_recipients' => 'integer',
        'queued_count' => 'integer',
        'analyzing_count' => 'integer',
        'refined_count' => 'integer',
        'scheduled_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'human_review_count' => 'integer',
    ];

    // Status constants
    const STATUS_STAGING = 'staging';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';

    // Campaign type constants
    const TYPE_BROADCAST = 'broadcast';
    const TYPE_TARGETED = 'targeted';
    const TYPE_DRIP = 'drip';

    /**
     * Get the user that owns the campaign
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business that owns the campaign
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The product this campaign promotes (used to keep personalization on-brand).
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    /**
     * Get the message queue entries for this campaign
     */
    public function messageQueue()
    {
        return $this->hasMany(MessageQueue::class);
    }

    /**
     * Get the attachments for this campaign
     */
    public function attachments()
    {
        return $this->hasMany(CampaignAttachment::class);
    }

    /**
     * Get the analytics for this campaign
     */
    public function analytics()
    {
        return $this->hasOne(CampaignAnalytics::class);
    }

    /**
     * Get the outgoing messages for this campaign
     */
    public function outgoingMessages()
    {
        return $this->hasMany(OutgoingMessage::class);
    }

    /**
     * Scope a query to only include active campaigns
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope a query to only include completed campaigns
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    /**
     * Calculate estimated time remaining
     */
    public function getEstimatedTimeRemainingAttribute()
    {
        if ($this->sent_count == 0) {
            return null;
        }

        $elapsed = $this->started_at ? now()->diffInMinutes($this->started_at) : 0;
        $avgTimePerMessage = $elapsed / $this->sent_count;
        $remaining = $this->total_recipients - $this->sent_count;

        return round($remaining * $avgTimePerMessage);
    }

    /**
     * Check if campaign is in progress
     */
    public function isInProgress()
    {
        return in_array($this->status, [
            self::STATUS_PROCESSING,
            self::STATUS_SENDING,
            self::STATUS_SCHEDULED
        ]);
    }

    /**
     * Check if campaign can be paused
     */
    public function canBePaused()
    {
        return $this->isInProgress();
    }

    /**
     * Check if campaign can be resumed
     */
    public function canBeResumed()
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Pause the campaign
     */
    public function pause()
    {
        if ($this->canBePaused()) {
            $this->update(['status' => self::STATUS_PAUSED]);
            return true;
        }
        return false;
    }

    /**
     * Resume the campaign
     */
    public function resume()
    {
        if ($this->canBeResumed()) {
            $this->update(['status' => self::STATUS_PROCESSING]);
            return true;
        }
        return false;
    }

    /**
     * Mark campaign as completed
     */
    public function markCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    /**
     * Increment counter
     */
    public function incrementCounter($counter)
    {
        $validCounters = [
            'queued_count',
            'analyzing_count',
            'refined_count',
            'scheduled_count',
            'sent_count',
            'failed_count',
            'human_review_count'
        ];

        if (in_array($counter, $validCounters)) {
            $this->increment($counter);
        }
    }

    /**
     * Decrement counter
     */
    public function decrementCounter($counter)
    {
        $validCounters = [
            'queued_count',
            'analyzing_count',
            'refined_count',
            'scheduled_count',
            'sent_count',
            'failed_count',
            'human_review_count'
        ];

        if (in_array($counter, $validCounters)) {
            $this->decrement($counter);
        }
    }
}
