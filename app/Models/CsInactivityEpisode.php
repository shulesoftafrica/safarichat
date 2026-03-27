<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * tracks each inactivity episode per business (start, tier, alerts sent, recovery).
 *
 * @property int         $id
 * @property int         $business_id
 * @property string      $started_at        (date)
 * @property string|null $ended_at          (date, null if still inactive)
 * @property string|null $tier_reached      'at_risk' | 'churned' | 'abandoned'
 * @property Carbon|null $day3_alert_sent_at
 * @property Carbon|null $day10_alert_sent_at
 * @property Carbon|null $recovery_message_sent_at
 * @property Carbon|null $escalated_at
 */
class CsInactivityEpisode extends Model
{
    protected $table = 'cs_inactivity_episodes';

    public const TIER_AT_RISK  = 'at_risk';
    public const TIER_CHURNED  = 'churned';
    public const TIER_ABANDONED = 'abandoned';

    protected $fillable = [
        'business_id',
        'started_at',
        'ended_at',
        'tier_reached',
        'day3_alert_sent_at',
        'day10_alert_sent_at',
        'recovery_message_sent_at',
        'escalated_at',
    ];

    protected $casts = [
        'started_at'               => 'date',
        'ended_at'                 => 'date',
        'day3_alert_sent_at'       => 'datetime',
        'day10_alert_sent_at'      => 'datetime',
        'recovery_message_sent_at' => 'datetime',
        'escalated_at'             => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(CsEscalation::class, 'episode_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    /**
     * Find or create the open (ended_at = NULL) inactivity episode for a business.
     */
    public static function openFor(int $businessId): self
    {
        return static::firstOrCreate(
            ['business_id' => $businessId, 'ended_at' => null],
            ['started_at' => now()->toDateString()]
        );
    }

    /**
     * Mark this episode as recovered (engagement resumed).
     */
    public function markRecovered(): void
    {
        $this->update(['ended_at' => now()->toDateString()]);
    }

    /**
     * Return true if there is currently an open episode for this business.
     */
    public static function hasOpenEpisode(int $businessId): bool
    {
        return static::where('business_id', $businessId)
            ->whereNull('ended_at')
            ->exists();
    }
}
