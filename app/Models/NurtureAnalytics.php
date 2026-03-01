<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurtureAnalytics extends Model
{
    use HasFactory;

    protected $table = 'nurture_analytics';

    protected $fillable = [
        'nurture_library_id',
        'campaign_id',
        'message_queue_id',
        'contact_id',
        'days_since_last_contact',
        'unanswered_messages_count',
        'did_reply',
        'reply_time_minutes',
        'reply_sentiment',
        'did_convert',
        'conversion_value',
        'sent_at',
        'replied_at',
        'converted_at',
    ];

    protected $casts = [
        'did_reply' => 'boolean',
        'did_convert' => 'boolean',
        'conversion_value' => 'decimal:2',
        'sent_at' => 'datetime',
        'replied_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Get the value nugget that was used
     */
    public function nurtureLibrary()
    {
        return $this->belongsTo(NurtureLibrary::class);
    }

    /**
     * Get the campaign this belongs to
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the message queue entry
     */
    public function messageQueue()
    {
        return $this->belongsTo(MessageQueue::class);
    }

    /**
     * Get the contact
     */
    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'contact_id');
    }

    /**
     * Mark as replied
     */
    public function markAsReplied($replyTime = null, $sentiment = null)
    {
        $this->did_reply = true;
        $this->replied_at = now();
        if ($replyTime) {
            $this->reply_time_minutes = $replyTime;
        }
        if ($sentiment) {
            $this->reply_sentiment = $sentiment;
        }
        $this->save();

        // Update the value nugget's success rate
        $this->nurtureLibrary->updateSuccessRate();

        // Update message queue
        $this->messageQueue->update([
            'nurture_success' => true,
            'nurture_reply_time' => $replyTime,
        ]);
    }

    /**
     * Mark as converted
     */
    public function markAsConverted($value = null)
    {
        $this->did_convert = true;
        $this->converted_at = now();
        if ($value) {
            $this->conversion_value = $value;
        }
        $this->save();
    }

    /**
     * Scope: Successful nurture messages (got replies)
     */
    public function scopeSuccessful($query)
    {
        return $query->where('did_reply', true);
    }

    /**
     * Scope: Converted leads
     */
    public function scopeConverted($query)
    {
        return $query->where('did_convert', true);
    }

    /**
     * Scope: By value nugget
     */
    public function scopeByNugget($query, $nuggetId)
    {
        return $query->where('nurture_library_id', $nuggetId);
    }
}
