<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsMessageLog extends Model
{
    public $timestamps = false;

    protected $table = 'cs_message_log';

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'sent_at',
        'delivered',
        'metadata',
    ];

    protected $casts = [
        'sent_at'   => 'datetime',
        'delivered' => 'boolean',
        'metadata'  => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Deduplication helpers ────────────────────────────────────────────────

    /**
     * True if a message of $type was already sent to $userId within $hours hours.
     */
    public static function alreadySent(int $userId, string $type, int $hours = 24): bool
    {
        return static::where('user_id', $userId)
                     ->where('type', $type)
                     ->where('sent_at', '>=', now()->subHours($hours))
                     ->exists();
    }

    /**
     * True if a message of $type was EVER sent to $userId (for once-only messages).
     */
    public static function everSent(int $userId, string $type): bool
    {
        return static::where('user_id', $userId)
                     ->where('type', $type)
                     ->exists();
    }

    /**
     * Record a sent CS message.
     */
    public static function record(
        int $userId,
        int $businessId,
        string $type,
        array $metadata = []
    ): static {
        return static::create([
            'user_id'     => $userId,
            'business_id' => $businessId,
            'type'        => $type,
            'sent_at'     => now(),
            'delivered'   => false,
            'metadata'    => $metadata,
        ]);
    }
}
