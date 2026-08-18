<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Channel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'channel_key',
        'display_name',
        'provider',
        'is_active',
        'priority_rank',
        'capabilities',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority_rank' => 'integer',
        'capabilities' => 'array',
        'settings' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function contactChannelPreferences()
    {
        return $this->hasMany('App\\Models\\ContactChannelPreference');
    }

    public function productPolicies()
    {
        return $this->hasMany('App\\Models\\ChannelProductPolicy');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
