<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MessageQueue extends Model
{
    use HasFactory;

    protected $table = 'message_queue';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'contact_id',
        'phone_number',
        'contact_name',
        'original_message',
        'refined_message',
        'attachment_context',
        'status',
        'priority',
        'detected_language',
        'detected_tone',
        'relationship_stage',
        'last_interaction_at',
        'optimal_send_time',
        'scheduled_send_at',
        'sent_at',
        'ai_confidence_score',
        'sentiment_filter_result',
        'human_review_reason',
        'context_summary',
        'ai_metadata',
        'retry_count',
        'error_message',
        'external_message_id',
        'provider',
        'credits_used',
        // Nurture-specific fields
        'is_nurture_mode',
        'nurture_library_id',
        'nurture_value_type',
        'pre_nurture_message',
        'nurture_success',
        'nurture_reply_time',
        // Multi-channel selection metadata
        'selected_channel',
        'channel_selection_reason',
        'fallback_chain',
        'dispatch_attempt',
        'transport_endpoint',
        'transport_payload',
    ];

    protected $casts = [
        'context_summary' => 'array',
        'ai_metadata' => 'array',
        'last_interaction_at' => 'datetime',
        'optimal_send_time' => 'datetime',
        'scheduled_send_at' => 'datetime',
        'sent_at' => 'datetime',
        'ai_confidence_score' => 'decimal:2',
        'priority' => 'integer',
        'retry_count' => 'integer',
        'credits_used' => 'integer',
        // Nurture casts
        'is_nurture_mode' => 'boolean',
        'nurture_library_id' => 'integer',
        'nurture_success' => 'boolean',
        'nurture_reply_time' => 'integer',
        'fallback_chain' => 'array',
        'dispatch_attempt' => 'integer',
        'transport_payload' => 'array',
    ];

    // Status constants
    const STATUS_STAGED = 'staged';
    const STATUS_ANALYZING = 'analyzing';
    const STATUS_REFINED = 'refined';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_HUMAN_REVIEW = 'human_review';
    const STATUS_OPTED_OUT = 'opted_out';
    const STATUS_CANCELLED = 'cancelled';

    // Language constants
    const LANGUAGE_ENGLISH = 'en';
    const LANGUAGE_SWAHILI = 'sw';
    const LANGUAGE_MIXED = 'mixed';

    // Tone constants
    const TONE_FORMAL = 'formal';
    const TONE_CASUAL = 'casual';
    const TONE_URGENT = 'urgent';
    const TONE_FRIENDLY = 'friendly';

    // Relationship stage constants
    const STAGE_NEW = 'new';
    const STAGE_ENGAGED = 'engaged';
    const STAGE_CONVERTING = 'converting';
    const STAGE_CUSTOMER = 'customer';
    const STAGE_INACTIVE = 'inactive';

    // Sentiment filter constants
    const SENTIMENT_POSITIVE = 'positive';
    const SENTIMENT_NEUTRAL = 'neutral';
    const SENTIMENT_NEGATIVE = 'negative';
    const SENTIMENT_OPT_OUT = 'opt_out_detected';

    // Provider constants
    const PROVIDER_WASENDER = 'wasender';
    const PROVIDER_META = 'meta';

    /**
     * Get the campaign that owns this queue entry
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user that owns this queue entry
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contact for this queue entry
     */
    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'contact_id');
    }

    /**
     * Get the nurture library entry used for this message (if in nurture mode)
     */
    public function nurtureLibrary()
    {
        return $this->belongsTo(NurtureLibrary::class, 'nurture_library_id');
    }

    /**
     * Get the outgoing message for this queue entry
     */
    public function outgoingMessage()
    {
        return $this->hasOne(OutgoingMessage::class);
    }

    /**
     * Scope a query to only include pending messages
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_STAGED,
            self::STATUS_ANALYZING,
            self::STATUS_REFINED,
            self::STATUS_SCHEDULED
        ]);
    }

    /**
     * Scope a query to only include messages ready to send
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                     ->where('scheduled_send_at', '<=', now());
    }

    /**
     * Scope a query to only include failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to only include messages needing human review
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('status', self::STATUS_HUMAN_REVIEW);
    }

    /**
     * Scope a query to only include nurture mode messages
     */
    public function scopeNurtureMode($query)
    {
        return $query->where('is_nurture_mode', true);
    }

    /**
     * Scope a query to only include successful nurture messages (got replies)
     */
    public function scopeSuccessfulNurture($query)
    {
        return $query->where('is_nurture_mode', true)
                     ->where('nurture_success', true);
    }

    /**
     * Check if message is ready for personalization
     */
    public function isReadyForPersonalization()
    {
        return $this->status === self::STATUS_STAGED;
    }

    /**
     * Check if message has been personalized
     */
    public function isPersonalized()
    {
        return !empty($this->refined_message);
    }

    /**
     * Check if message needs human review
     */
    public function needsHumanReview()
    {
        return $this->status === self::STATUS_HUMAN_REVIEW;
    }

    /**
     * Mark for human review
     */
    public function markForReview($reason)
    {
        $this->update([
            'status' => self::STATUS_HUMAN_REVIEW,
            'human_review_reason' => $reason
        ]);
    }

    /**
     * Approve and schedule
     */
    public function approveAndSchedule()
    {
        if ($this->needsHumanReview()) {
            $this->update([
                'status' => self::STATUS_SCHEDULED,
                'human_review_reason' => null
            ]);
            return true;
        }
        return false;
    }

    /**
     * Mark as opted out
     */
    public function markAsOptedOut()
    {
        $this->update(['status' => self::STATUS_OPTED_OUT]);
        
        // Also update contact's opt-out status
        if ($this->contact) {
            $this->contact->update([
                'opt_out_status' => true,
                'opt_out_at' => now()
            ]);
        }
    }

    /**
     * Increment retry count
     */
    public function incrementRetry($errorMessage = null)
    {
        $this->increment('retry_count');
        
        if ($errorMessage) {
            $this->update(['error_message' => $errorMessage]);
        }

        // Mark as failed if retry limit exceeds 3
        if ($this->retry_count >= 3) {
            $this->update(['status' => self::STATUS_FAILED]);
        }
    }

    /**
     * Calculate priority score
     */
    public function calculatePriority()
    {
        $score = 5; // Default

        // Increase priority for high confidence
        if ($this->ai_confidence_score >= 0.9) {
            $score += 2;
        }

        // Increase priority for engaged contacts
        if ($this->relationship_stage === self::STAGE_CONVERTING) {
            $score += 2;
        }

        // Decrease priority for low engagement
        if ($this->relationship_stage === self::STAGE_INACTIVE) {
            $score -= 1;
        }

        return max(1, min(10, $score)); // Keep between 1-10
    }

    /**
     * Get message to send (refined or original)
     */
    public function getMessageToSend()
    {
        return $this->refined_message ?? $this->original_message;
    }

    /**
     * Mark as sent
     */
    public function markAsSent($externalMessageId = null)
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'external_message_id' => $externalMessageId
        ]);

        // Increment campaign counter
        $this->campaign->incrementCounter('sent_count');
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage
        ]);

        // Increment campaign counter
        $this->campaign->incrementCounter('failed_count');
    }
}
