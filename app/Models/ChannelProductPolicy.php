<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelProductPolicy extends Model
{
    protected $fillable = [
        'business_id',
        'product_id',
        'channel_id',
        'channel_key',
        'is_allowed',
        'priority_rank',
        'cooldown_minutes',
        'rules',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'priority_rank' => 'integer',
        'cooldown_minutes' => 'integer',
        'rules' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
