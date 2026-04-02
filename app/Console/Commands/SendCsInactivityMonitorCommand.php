<?php

namespace App\Console\Commands;

use App\Events\BusinessInactivityEscalated;
use App\Events\BusinessReEngaged;
use App\Models\CsInactivityEpisode;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\CustomerSuccess\CsMessageRenderer;
use App\Models\CsMessageLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Churn-prevention engine:
 *  - Day 3 : at-risk nudge
 *  - Day 10: trial win-back OR paid win-back
 *  - Day 10+ 48h: escalate paid churned accounts to CS team
 *  - Connected back: fire BusinessReEngaged + close episode
 *  - Abandoned (10d inactive + WhatsApp disconnected)
 *
 * Scheduled: dailyAt('08:00') Africa/Nairobi
 */
class SendCsInactivityMonitorCommand extends Command
{
    protected $signature   = 'cs:inactivity-monitor {--dry-run : Log actions without sending messages}';
    protected $description = 'Detect inactive businesses and send churn-prevention messages';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('[DRY-RUN] cs:inactivity-monitor');
        }

        // ── 1. Re-engagement: business has an open episode but sent messages today ──
        $this->handleReEngagements($dryRun);

        // ── 2. Abandoned: 10d inactive + WhatsApp disconnected ──────────────────────
        $this->handleAbandoned($dryRun);

        // ── 3. Day-3 at-risk nudge ───────────────────────────────────────────────────
        $this->handleDay3($dryRun);

        // ── 4. Day-10 churn win-back ─────────────────────────────────────────────────
        $this->handleDay10($dryRun);

        // ── 5. Escalate paid accounts that haven't replied 48h after Day-10 alert ──
        $this->handleEscalations($dryRun);

        $this->info('cs:inactivity-monitor complete.');
        return self::SUCCESS;
    }

    // ── Re-engagement ─────────────────────────────────────────────────────────────

    private function handleReEngagements(bool $dryRun): void
    {
        // Find open episodes whose business sent at least 1 conversation TODAY
        $recovered = DB::select("
            SELECT e.id AS episode_id, e.business_id AS business_id
            FROM cs_inactivity_episodes e
            JOIN cs_daily_snapshots s
                ON s.business_id = e.business_id
               AND s.snapshot_date = CURRENT_DATE
            WHERE e.ended_at IS NULL
              AND s.total_conversations > 0
        ");

        foreach ($recovered as $row) {
            $episode = CsInactivityEpisode::find($row->episode_id);
            if (! $episode) {
                continue;
            }

            $user = $this->resolveUserForBusiness((int) $row->business_id);
            if (! $user) {
                continue;
            }

            $this->info("Re-engagement detected: business #{$row->business_id}");

            if (! $dryRun) {
                BusinessReEngaged::dispatch($user, (int) $row->business_id);
                $episode->markRecovered();
            }
        }
    }

    // ── Abandoned (10d + disconnected) ───────────────────────────────────────────

    private function handleAbandoned(bool $dryRun): void
    {
        $churned10 = $this->businessIdsWith10DaysZero();

        foreach ($churned10 as $businessId) {
            $user = $this->resolveUserForBusiness($businessId);
            if (! $user) {
                continue;
            }

            // WhatsApp disconnected?
            $disconnected = WhatsappInstance::where('user_id', $user->id)
                ->where('status', 'disconnected')
                ->exists();

            if (! $disconnected) {
                continue;
            }

            if (CsMessageLog::alreadySent($user->id, 'inactivity_abandoned', 240)) {
                continue; // already sent within 10 days
            }

            $businessName  = optional($user->business)->name ?? '';
            $dashboardLink = config('app.url') . '/dashboard';

            $this->info("Abandoned: business #{$businessId} user #{$user->id}");

            if (! $dryRun) {
                $episode = CsInactivityEpisode::openFor($businessId);
                $episode->update(['tier_reached' => CsInactivityEpisode::TIER_ABANDONED]);

                app(CsMessageRenderer::class)->send($user, 'inactivity_abandoned', [
                    'business_name'  => $businessName,
                    'dashboard_link' => $dashboardLink,
                ], $businessId);
            }
        }
    }

    // ── Day-3 at-risk ─────────────────────────────────────────────────────────────

    private function handleDay3(bool $dryRun): void
    {
        $atRisk = $this->businessIdsWith3DaysZero();

        foreach ($atRisk as $businessId) {
            $user = $this->resolveUserForBusiness($businessId);
            if (! $user) {
                continue;
            }

            $episode = CsInactivityEpisode::openFor($businessId);

            if ($episode->day3_alert_sent_at) {
                continue; // already sent for this episode
            }

            // Don't fire if also 10-day (will be handled by handleDay10)
            if (in_array($businessId, $this->businessIdsWith10DaysZero(), true)) {
                continue;
            }

            $businessName  = optional($user->business)->name ?? '';
            $yourNumber    = optional($user->business)->whatsapp_number ?? '';
            $dashboardLink = config('app.url') . '/dashboard';

            $daysLeft = $this->trialDaysLeft($user);
            $vars     = [
                'business_name'  => $businessName,
                'your_number'    => $yourNumber,
                'dashboard_link' => $dashboardLink,
            ];

            $this->info("Day-3 nudge: business #{$businessId} user #{$user->id}");

            if (! $dryRun) {
                app(CsMessageRenderer::class)->send($user, 'inactivity_day3', $vars, $businessId);

                if ($daysLeft !== null && $daysLeft > 0) {
                    app(CsMessageRenderer::class)->send($user, 'inactivity_day3_trial_note', [
                        'days_left' => $daysLeft,
                    ], $businessId);
                }

                $episode->update([
                    'day3_alert_sent_at' => now(),
                    'tier_reached'       => CsInactivityEpisode::TIER_AT_RISK,
                ]);
            }
        }
    }

    // ── Day-10 win-back ───────────────────────────────────────────────────────────

    private function handleDay10(bool $dryRun): void
    {
        $churned10 = $this->businessIdsWith10DaysZero();

        foreach ($churned10 as $businessId) {
            $user = $this->resolveUserForBusiness($businessId);
            if (! $user) {
                continue;
            }

            $episode = CsInactivityEpisode::openFor($businessId);

            if ($episode->day10_alert_sent_at) {
                continue; // already sent
            }

            // Skip truly abandoned (already handled with different message)
            if (WhatsappInstance::where('user_id', $user->id)->where('status', 'disconnected')->exists()) {
                continue;
            }

            $isTrial       = $user->subscription_status === 'trial';
            $templateKey   = $isTrial ? 'inactivity_day10_trial' : 'inactivity_day10_paid';
            $businessName  = optional($user->business)->name ?? '';
            $dashboardLink = config('app.url') . '/dashboard';
            $daysLeft      = $this->trialDaysLeft($user);
            $planName      = ucfirst(optional($user->business)->subscription_plan ?? 'starter');
            $renewalDate   = $this->nextRenewalDate($user);

            $vars = [
                'business_name'  => $businessName,
                'dashboard_link' => $dashboardLink,
                'plan_name'      => $planName,
                'renewal_date'   => $renewalDate,
                'days_left'      => $daysLeft ?? '',
            ];

            $this->info("Day-10 win-back ({$templateKey}): business #{$businessId} user #{$user->id}");

            if (! $dryRun) {
                app(CsMessageRenderer::class)->send($user, $templateKey, $vars, $businessId);

                $tier = $isTrial ? CsInactivityEpisode::TIER_CHURNED : CsInactivityEpisode::TIER_CHURNED;
                $episode->update([
                    'day10_alert_sent_at' => now(),
                    'tier_reached'        => $tier,
                ]);
            }
        }
    }

    // ── Escalations (paid churned, 48h no reply after Day-10 alert) ──────────────

    private function handleEscalations(bool $dryRun): void
    {
        $toEscalate = CsInactivityEpisode::query()
            ->whereNull('ended_at')
            ->whereNull('escalated_at')
            ->whereNotNull('day10_alert_sent_at')
            ->where('day10_alert_sent_at', '<=', now()->subHours(48))
            ->get();

        foreach ($toEscalate as $episode) {
            $user = $this->resolveUserForBusiness($episode->business_id);
            if (! $user) {
                continue;
            }

            // Only escalate paid active subscribers
            if ($user->subscription_status !== 'active') {
                continue;
            }

            $this->info("Escalate: business #{$episode->business_id} episode #{$episode->id}");

            if (! $dryRun) {
                BusinessInactivityEscalated::dispatch($user, $episode);
            }
        }
    }

    // ── SQL helpers ───────────────────────────────────────────────────────────────

    /** @return int[] */
    private function businessIdsWith3DaysZero(): array
    {
        $rows = DB::select("
            SELECT business_id
            FROM cs_daily_snapshots
            WHERE snapshot_date >= CURRENT_DATE - INTERVAL '3 days'
            GROUP BY business_id
            HAVING SUM(total_conversations) = 0
               AND COUNT(*) >= 3
        ");
        return array_column($rows, 'business_id');
    }

    /** @return int[] */
    private function businessIdsWith10DaysZero(): array
    {
        $rows = DB::select("
            SELECT business_id
            FROM cs_daily_snapshots
            WHERE snapshot_date >= CURRENT_DATE - INTERVAL '10 days'
            GROUP BY business_id
            HAVING SUM(total_conversations) = 0
               AND COUNT(*) >= 10
        ");
        return array_column($rows, 'business_id');
    }

    // ── Model helpers ─────────────────────────────────────────────────────────────

    private function resolveUserForBusiness(int $businessId): ?User
    {
        return User::whereHas('business', fn ($q) => $q->where('id', $businessId))
            ->with('business')
            ->first();
    }

    private function trialDaysLeft(User $user): ?int
    {
        if ($user->subscription_status !== 'trial') {
            return null;
        }
        $trialEndsAt = $user->trial_ends_at ?? null;
        if (! $trialEndsAt) {
            return null;
        }
        $days = (int) now()->diffInDays($trialEndsAt, false);
        return max(0, $days);
    }

    private function nextRenewalDate(User $user): string
    {
        $renewalDate = $user->subscription_renews_at ?? null;
        if ($renewalDate) {
            return \Carbon\Carbon::parse($renewalDate)->format('j M Y');
        }
        return 'your next billing date';
    }
}
