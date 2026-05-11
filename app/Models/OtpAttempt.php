<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpAttempt extends Model
{
    protected $fillable = [
        'phone',
        'type',
        'delivery_channel',
        'delivery_status',
        'failure_reason',
        'ip_address',
        'user_agent',
        'verified',
        'verified_at',
    ];

    protected $casts = [
        'verified'    => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Record a new OTP attempt and return the model so callers can update it later.
     */
    public static function record(
        string $phone,
        string $type,
        string $deliveryChannel,
        string $deliveryStatus,
        ?string $failureReason = null
    ): self {
        return static::create([
            'phone'            => $phone,
            'type'             => $type,
            'delivery_channel' => $deliveryChannel,
            'delivery_status'  => $deliveryStatus,
            'failure_reason'   => $failureReason,
            'ip_address'       => request()?->ip(),
            'user_agent'       => request()?->userAgent(),
        ]);
    }

    /**
     * Mark this attempt as successfully verified.
     */
    public function markVerified(): void
    {
        $this->update([
            'verified'    => true,
            'verified_at' => now(),
        ]);
    }
}
