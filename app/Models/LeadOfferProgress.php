<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Per-lead, per-offer rotation ledger.
 *
 * Tracks how many times each module/offer has been pitched to a lead and the
 * outcome, so the NextBestOfferService can rotate to a fresh angle instead of
 * repeating the same pitch. See config/sales_rotation.php.
 */
class LeadOfferProgress extends Model
{
    use HasFactory;

    protected $table = 'lead_offer_progress';

    protected $fillable = [
        'lead_id',
        'business_id',
        'product_id',
        'status',
        'touch_count',
        'first_pitched_at',
        'last_pitched_at',
        'engaged_at',
        'rejected_at',
        'last_channel',
        'last_outcome',
        'meta',
    ];

    protected $casts = [
        'touch_count'      => 'integer',
        'first_pitched_at' => 'datetime',
        'last_pitched_at'  => 'datetime',
        'engaged_at'       => 'datetime',
        'rejected_at'      => 'datetime',
        'meta'             => 'array',
    ];

    // Rotation lifecycle states.
    const STATUS_PENDING   = 'pending';
    const STATUS_PITCHED   = 'pitched';
    const STATUS_ENGAGED   = 'engaged';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_EXHAUSTED = 'exhausted';
    const STATUS_CONVERTED = 'converted';

    // States that mean "do not pitch this offer again".
    const TERMINAL_STATUSES = [
        self::STATUS_ENGAGED,
        self::STATUS_REJECTED,
        self::STATUS_EXHAUSTED,
        self::STATUS_CONVERTED,
    ];

    // Relationships

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes

    public function scopeForLead($query, $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeInFlight($query)
    {
        return $query->where('status', self::STATUS_PITCHED);
    }

    // Helpers

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    /**
     * Record that this offer was just pitched on a channel.
     * Increments the touch counter and stamps timestamps.
     */
    public function registerPitch(?string $channel = null, ?string $outcome = null): self
    {
        $now = Carbon::now();

        $this->touch_count = ($this->touch_count ?? 0) + 1;
        $this->status = self::STATUS_PITCHED;
        $this->last_pitched_at = $now;
        $this->first_pitched_at = $this->first_pitched_at ?? $now;
        $this->last_channel = $channel ?? $this->last_channel;
        $this->last_outcome = $outcome ?? $this->last_outcome;

        $meta = $this->meta ?? [];
        $meta['touches'][] = [
            'at'      => $now->toISOString(),
            'channel' => $channel,
            'outcome' => $outcome,
        ];
        // Keep the audit trail bounded.
        if (isset($meta['touches']) && count($meta['touches']) > 20) {
            $meta['touches'] = array_slice($meta['touches'], -20);
        }
        $this->meta = $meta;

        $this->save();

        return $this;
    }

    public function markEngaged(): self
    {
        // Do not overwrite a stronger terminal state (rejected/converted).
        if (in_array($this->status, [self::STATUS_REJECTED, self::STATUS_CONVERTED], true)) {
            return $this;
        }

        $this->status = self::STATUS_ENGAGED;
        $this->engaged_at = $this->engaged_at ?? Carbon::now();
        $this->save();

        return $this;
    }

    public function markRejected(?string $reason = null): self
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejected_at = Carbon::now();
        if ($reason) {
            $this->last_outcome = $reason;
        }
        $this->save();

        return $this;
    }

    public function markExhausted(): self
    {
        if (!$this->isTerminal()) {
            $this->status = self::STATUS_EXHAUSTED;
            $this->save();
        }

        return $this;
    }
}
