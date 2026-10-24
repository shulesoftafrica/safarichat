<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A promise a lead made that a conversion event is expected to fulfil
 * ("I'll pay tomorrow"). The scheduler nudges the lead and escalates to the
 * team when the due time passes with no matching event.
 */
class LeadCommitment extends Model
{
    protected $table = 'lead_commitments';

    protected $fillable = [
        'business_id',
        'lead_id',
        'business_contact_id',
        'expected_event_type',
        'promised_text',
        'due_by',
        'status',
        'fulfilled_event_id',
        'created_by',
        'nudged_at',
        'escalated_at',
    ];

    protected $casts = [
        'due_by'       => 'datetime',
        'nudged_at'    => 'datetime',
        'escalated_at' => 'datetime',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_MISSED    = 'missed';
    const STATUS_CANCELLED = 'cancelled';

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'business_contact_id');
    }

    public function fulfilledEvent()
    {
        return $this->belongsTo(ConversionEvent::class, 'fulfilled_event_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
