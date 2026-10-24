<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single conversion signal (webhook or internal), with the raw payload kept
 * for auditing. Matched to a lead/contact where possible.
 */
class ConversionEvent extends Model
{
    protected $table = 'conversion_events';

    protected $fillable = [
        'business_id',
        'event_config_id',
        'lead_id',
        'business_contact_id',
        'event_type',
        'source',
        'identifier',
        'identifier_type',
        'amount',
        'currency',
        'reference',
        'metadata',
        'idempotency_key',
        'occurred_at',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'amount'       => 'decimal:2',
        'occurred_at'  => 'datetime',
        'processed_at' => 'datetime',
    ];

    const STATUS_RECEIVED  = 'received';
    const STATUS_MATCHED   = 'matched';
    const STATUS_PROCESSED = 'processed';
    const STATUS_ORPHAN    = 'orphan';   // received but no lead/contact matched
    const STATUS_FAILED    = 'failed';

    public function config()
    {
        return $this->belongsTo(EventConfig::class, 'event_config_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'business_contact_id');
    }
}
