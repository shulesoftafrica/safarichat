<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactChannelMetric extends Model
{
    protected $fillable = [
        'business_contact_id',
        'channel_key',
        'sent_count',
        'delivered_count',
        'replied_count',
        'converted_count',
        'failed_count',
        'response_rate',
        'conversion_rate',
        'avg_response_minutes',
        'last_sent_at',
        'last_reply_at',
        'last_success_at',
        'last_failure_at',
        'metadata',
    ];

    protected $casts = [
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'replied_count' => 'integer',
        'converted_count' => 'integer',
        'failed_count' => 'integer',
        'response_rate' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'avg_response_minutes' => 'integer',
        'last_sent_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'business_contact_id');
    }
}
