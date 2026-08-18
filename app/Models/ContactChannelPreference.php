<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactChannelPreference extends Model
{
    protected $fillable = [
        'business_contact_id',
        'ai_sales_agent_id',
        'channel_id',
        'channel_key',
        'is_preferred',
        'is_allowed',
        'priority_rank',
        'opt_out_at',
        'formal_only',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
        'is_allowed' => 'boolean',
        'priority_rank' => 'integer',
        'formal_only' => 'boolean',
        'opt_out_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'business_contact_id');
    }

    public function aiSalesAgent()
    {
        return $this->belongsTo(AiSalesAgent::class);
    }

    public function channel()
    {
        return $this->belongsTo('App\\Models\\Channel');
    }
}
