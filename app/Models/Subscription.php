<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_package_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'auto_renew',
        'grace_period_ends',
        'cancellation_reason',
        'metadata'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_period_ends' => 'datetime',
        'auto_renew' => 'boolean',
        'metadata' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // AdminPackage relationship removed - using new billing system

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial')->where('trial_ends_at', '>', now());
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    public function isExpired(): bool
    {
        return $this->ends_at < now();
    }

    public function isInTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at > now();
    }
}
