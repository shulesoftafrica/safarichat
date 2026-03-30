<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsConversationSession extends Model
{
    // ── Context constants ────────────────────────────────────────────────────
    const CONTEXT_TRIAL_UPGRADE        = 'trial_upgrade';
    const CONTEXT_SUBSCRIPTION_UPGRADE = 'subscription_upgrade';
    const CONTEXT_CREDIT_PURCHASE      = 'credit_purchase';

    // ── State constants ──────────────────────────────────────────────────────
    const STATE_AWAITING_PACKAGE = 'awaiting_package';
    const STATE_AWAITING_PAYMENT = 'awaiting_payment';
    const STATE_COMPLETED        = 'completed';
    const STATE_EXPIRED          = 'expired';

    protected $fillable = [
        'user_id',
        'context',
        'state',
        'payload',
        'expires_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'expires_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('state', [self::STATE_AWAITING_PACKAGE, self::STATE_AWAITING_PAYMENT])
                     ->where('expires_at', '>', now());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Find the active (non-expired) session for a user, if any.
     */
    public static function findActive(int $userId): ?static
    {
        return static::active()->where('user_id', $userId)->latest()->first();
    }

    /**
     * Create a fresh session for a user, expiring any previous ones first.
     */
    public static function startFor(int $userId, string $context): static
    {
        // Expire previous open sessions for the same user
        static::where('user_id', $userId)
              ->whereIn('state', [self::STATE_AWAITING_PACKAGE, self::STATE_AWAITING_PAYMENT])
              ->update(['state' => self::STATE_EXPIRED]);

        return static::create([
            'user_id'    => $userId,
            'context'    => $context,
            'state'      => self::STATE_AWAITING_PACKAGE,
            'payload'    => [],
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Advance session to awaiting_payment and store invoice details.
     */
    public function awaitPayment(array $payloadData): void
    {
        $this->update([
            'state'      => self::STATE_AWAITING_PAYMENT,
            'payload'    => array_merge($this->payload ?? [], $payloadData),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Mark session as complete.
     */
    public function complete(): void
    {
        $this->update(['state' => self::STATE_COMPLETED]);
    }

    /**
     * Bump the expiry by 30 minutes (user is still active).
     */
    public function touch($attribute = null): bool
    {
        return $this->update(['expires_at' => now()->addMinutes(30)]);
    }

    /**
     * Expire sessions older than the threshold (batch operation).
     */
    public static function expireStale(): int
    {
        return static::whereIn('state', [self::STATE_AWAITING_PACKAGE, self::STATE_AWAITING_PAYMENT])
                     ->where('expires_at', '<=', now())
                     ->update(['state' => self::STATE_EXPIRED]);
    }
}
