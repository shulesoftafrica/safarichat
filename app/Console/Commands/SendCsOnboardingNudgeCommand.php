<?php

namespace App\Console\Commands;

use App\Models\CsMessageLog;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Onboarding nudge: detects users who registered but never connected
 * a WhatsApp instance and sends time-based setup prompts.
 *
 * Phase 1 (Day 1-2)  → gentle nudge       → template: onboarding_connect_day1
 * Phase 2 (Day 3-6)  → stronger prompt    → template: onboarding_connect_day3
 * Phase 3 (Day 7-30) → final urgent nudge → template: onboarding_connect_day7
 *
 * Each phase is sent AT MOST ONCE (uses CsMessageLog::everSent dedup).
 * Once a user connects their WhatsApp they no longer match the query
 * and stop receiving nudges automatically.
 *
 * Scheduled: dailyAt('10:00') Africa/Nairobi
 */
class SendCsOnboardingNudgeCommand extends Command
{
    protected $signature   = 'cs:onboarding-nudge {--dry-run : Log actions without sending}';
    protected $description = 'Nudge users who registered but have not yet connected a WhatsApp instance';

    /** [day_min, day_max_inclusive, template_key] */
    private const PHASES = [
        [1,  2,  'onboarding_connect_day1'],
        [3,  6,  'onboarding_connect_day3'],
        [7,  30, 'onboarding_connect_day7'],
    ];

    public function handle(): int
    {
        $dryRun   = $this->option('dry-run');
        $renderer = App::make(CsMessageRenderer::class);

        // ── Candidate users: billing account exists (trial or active) AND
        //    no WhatsApp instance is currently in connected/active state ──────
        $users = User::whereHas('billingAccount', fn ($q) =>
                $q->whereIn('billing_accounts.status', ['active', 'trial'])
            )
            ->where('created_at', '>=', now()->subDays(30)) // stop nudging after 30 days
            ->with(['business', 'billingAccount'])
            ->get()
            ->filter(fn (User $user) =>
                ! WhatsappInstance::where('user_id', $user->id)
                    ->whereIn('status', ['connected', 'active'])
                    ->exists()
            );

        $this->info(sprintf(
            '[cs:onboarding-nudge] %d user(s) without a connected WhatsApp instance.',
            $users->count()
        ));

        $sent    = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $daysSince = (int) $user->created_at->diffInDays(now());

            [$templateKey] = $this->resolvePhase($daysSince);

            if ($templateKey === null) {
                // Registered less than 1 full day ago — too early to nudge
                $skipped++;
                continue;
            }

            // Each phase template is sent at most ONCE, ever
            if (CsMessageLog::everSent($user->id, $templateKey)) {
                $skipped++;
                continue;
            }

            $dashboardLink = config('app.url') . '/dashboard';
            $connectLink   = config('app.url') . '/settings/whatsapp';
            $businessName  = optional($user->business)->name ?? $user->name ?? 'there';
            $businessId    = optional($user->business)->id ?? 0;

            $vars = [
                'business_name'  => $businessName,
                'dashboard_link' => $dashboardLink,
                'connect_link'   => $connectLink,
                'days_since'     => $daysSince,
            ];

            $this->line(sprintf(
                '  [%s] userId=%-6d %-30s day=%-3d → %s',
                $dryRun ? 'DRY ' : 'SEND',
                $user->id,
                $user->email,
                $daysSince,
                $templateKey
            ));

            if ($dryRun) {
                $sent++;
                continue;
            }

            $ok = $renderer->send($user, $templateKey, $vars, $businessId);

            if ($ok) {
                $sent++;
            } else {
                Log::warning('cs:onboarding-nudge: send failed', [
                    'user_id'  => $user->id,
                    'template' => $templateKey,
                    'day'      => $daysSince,
                ]);
                $skipped++;
            }
        }

        $this->info(sprintf(
            '[cs:onboarding-nudge] Done. %d message(s) %s, %d skipped.',
            $sent,
            $dryRun ? 'would be sent' : 'sent',
            $skipped
        ));

        return self::SUCCESS;
    }

    /**
     * Returns [templateKey] for the given day count,
     * or [null] if outside all defined phase windows.
     *
     * @return array{0: string|null}
     */
    private function resolvePhase(int $daysSince): array
    {
        foreach (self::PHASES as [$min, $max, $key]) {
            if ($daysSince >= $min && $daysSince <= $max) {
                return [$key];
            }
        }

        return [null];
    }
}
