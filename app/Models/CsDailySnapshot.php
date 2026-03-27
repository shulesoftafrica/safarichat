<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CsDailySnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'snapshot_date',
        'total_conversations',
        'new_prospects',
        'active_leads',
        'converted',
        'churned',
        'stage_changes',
        'lead_new',
        'lead_interested',
        'lead_engaged',
        'lead_converted',
        'lead_churned',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'created_at'    => 'datetime',
    ];

    // ---------------------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------------------

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ---------------------------------------------------------------------------
    // Snapshot builder
    // ---------------------------------------------------------------------------

    /**
     * Build a fresh snapshot for the given business and date.
     * Upserts — safe to call idempotently.
     *
     * @param int    $businessId  Business ID (for Lead/BusinessContact queries)
     * @param int    $userId      Owner user ID (for IncomingMessage query)
     * @param Carbon $date
     */
    public static function buildForBusiness(int $businessId, int $userId, Carbon $date): static
    {
        $dateStr    = $date->toDateString();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay   = $date->copy()->endOfDay();

        // ── Conversation count: distinct senders who messaged the user today ──────
        $totalConversations = IncomingMessage::where('user_id', $userId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->distinct('phone_number')
            ->count('phone_number');

        // ── New prospects: BusinessContacts created today ─────────────────────
        $newProspects = BusinessContact::where('business_id', $businessId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // ── Lead stage distributions ──────────────────────────────────────────
        $newStatuses       = [Lead::STATUS_NEW, Lead::STATUS_OUTREACHED];
        $interestedStatuses = [Lead::STATUS_REPLIED];
        $engagedStatuses   = [
            Lead::STATUS_ENGAGED, Lead::STATUS_QUALIFIED, Lead::STATUS_PITCHED,
            Lead::STATUS_DEMO_SCHEDULED, Lead::STATUS_PROPOSAL_SENT, Lead::STATUS_NEGOTIATING,
        ];

        $leadNew        = Lead::where('business_id', $businessId)->whereIn('status', $newStatuses)->count();
        $leadInterested = Lead::where('business_id', $businessId)->whereIn('status', $interestedStatuses)->count();
        $leadEngaged    = Lead::where('business_id', $businessId)->whereIn('status', $engagedStatuses)->count();
        $leadConverted  = Lead::where('business_id', $businessId)->where('status', Lead::STATUS_CLOSED)->count();
        $leadChurned    = Lead::where('business_id', $businessId)
                              ->where(fn ($q) => $q->where('status', Lead::STATUS_LOST)->orWhere('is_churned', true))
                              ->count();

        $activeLeads    = $leadInterested + $leadEngaged;
        $stageChanges   = Lead::where('business_id', $businessId)
                              ->whereBetween('updated_at', [$startOfDay, $endOfDay])
                              ->where('status', '!=', Lead::STATUS_NEW)
                              ->count();

        // How many leads were converted or churned today specifically
        $convertedToday = Lead::where('business_id', $businessId)
                             ->where('status', Lead::STATUS_CLOSED)
                             ->whereBetween('updated_at', [$startOfDay, $endOfDay])
                             ->count();

        $churnedToday = Lead::where('business_id', $businessId)
                            ->where(fn ($q) => $q->where('status', Lead::STATUS_LOST)->orWhere('is_churned', true))
                            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
                            ->count();

        return static::updateOrCreate(
            ['business_id' => $businessId, 'snapshot_date' => $dateStr],
            [
                'total_conversations' => $totalConversations,
                'new_prospects'       => $newProspects,
                'active_leads'        => $activeLeads,
                'converted'           => $convertedToday,
                'churned'             => $churnedToday,
                'stage_changes'       => $stageChanges,
                'lead_new'            => $leadNew,
                'lead_interested'     => $leadInterested,
                'lead_engaged'        => $leadEngaged,
                'lead_converted'      => $leadConverted,
                'lead_churned'        => $leadChurned,
            ]
        );
    }

    /**
     * Fetch yesterday's snapshot for a business (null if not yet recorded).
     */
    public static function yesterday(int $businessId): ?static
    {
        return static::where('business_id', $businessId)
                     ->where('snapshot_date', now()->subDay()->toDateString())
                     ->first();
    }

    // ---------------------------------------------------------------------------
    // Delta helpers (used by DailyRecommendationResolver and message renderer)
    // ---------------------------------------------------------------------------

    /**
     * Return a signed delta string like "+5", "−3", or "" when no prior data.
     */
    public static function delta(int $current, ?int $previous): string
    {
        if ($previous === null) {
            return '';
        }
        $diff = $current - $previous;
        return $diff >= 0 ? "+{$diff}" : "{$diff}";
    }
}
