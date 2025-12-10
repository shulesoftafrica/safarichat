<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissedAutomation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lead_id',
        'automation_type',
        'scheduled_at',
        'missed_reason',
        'target_data',
        'potential_value',
        'recovered_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'recovered_at' => 'datetime',
        'target_data' => 'array',
        'potential_value' => 'decimal:2'
    ];

    public $timestamps = false;
    protected $dates = ['created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function isRecovered(): bool
    {
        return !is_null($this->recovered_at);
    }

    public function canBeRecovered(): bool
    {
        return $this->scheduled_at > now()->subDays(7) && !$this->isRecovered();
    }
}
