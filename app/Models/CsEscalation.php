<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Human handoff queue for high-value churned accounts.
 *
 * @property int         $id
 * @property int         $business_id
 * @property int|null    $episode_id
 * @property string      $reason         'paid_churned_10d' | 'no_reply_winback'
 * @property string      $status         'needs_human_followup' | 'in_progress' | 'resolved'
 * @property int|null    $assigned_to    CS team member user_id
 * @property string|null $notes
 * @property \Carbon\Carbon|null $resolved_at
 */
class CsEscalation extends Model
{
    protected $table = 'cs_escalations';

    public const STATUS_NEEDS_FOLLOWUP = 'needs_human_followup';
    public const STATUS_IN_PROGRESS    = 'in_progress';
    public const STATUS_RESOLVED       = 'resolved';

    public const REASON_PAID_CHURNED   = 'paid_churned_10d';
    public const REASON_NO_REPLY       = 'no_reply_winback';

    protected $fillable = [
        'business_id',
        'episode_id',
        'reason',
        'status',
        'assigned_to',
        'notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(CsInactivityEpisode::class, 'episode_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    /**
     * Create a new escalation record if one doesn't already exist for this episode+reason.
     */
    public static function createIfNotExists(int $businessId, ?int $episodeId, string $reason): ?self
    {
        $exists = static::where('business_id', $businessId)
            ->where('reason', $reason)
            ->whereIn('status', [self::STATUS_NEEDS_FOLLOWUP, self::STATUS_IN_PROGRESS])
            ->exists();

        if ($exists) {
            return null;
        }

        return static::create([
            'business_id' => $businessId,
            'episode_id'  => $episodeId,
            'reason'      => $reason,
            'status'      => self::STATUS_NEEDS_FOLLOWUP,
        ]);
    }
}
