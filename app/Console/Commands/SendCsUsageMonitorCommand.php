<?php

namespace App\Console\Commands;

use App\Models\CsDailySnapshot;
use App\Models\CsMessageLog;
use App\Models\User;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Send one daily performance report per active business.
 *
 * Replaces the previous credit-threshold alert dispatcher.
 * Scheduled once per day; uses CsMessageLog dedup to guarantee
 * at-most-one report per business per calendar day.
 */
class SendCsUsageMonitorCommand extends Command
{
    protected $signature = 'cs:usage-monitor
                            {--dry-run : Print report data without sending messages}';

    protected $description = 'Send one daily performance report per business to their owner via WhatsApp.';

    public function handle(): int
    {
        $isDry    = $this->option('dry-run');
        $now      = Carbon::now();
        $renderer = App::make(CsMessageRenderer::class);

        // All users who own a business with an active billing account.
        $users = User::whereHas('business')
            ->whereHas('billingAccount', fn ($q) =>
                $q->where('billing_accounts.status', 'active')
            )
            ->with(['business', 'billingAccount'])
            ->get();

        $this->info(sprintf('[cs:usage-monitor] %d eligible business owner(s) found.', $users->count()));

        $sent    = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $business       = $user->business;
            $billingAccount = $user->billingAccount;

            if (!$business || !$billingAccount) {
                $skipped++;
                continue;
            }

            // ── Dedup: one report per business per 20 hours ─────────────────
            if (CsMessageLog::alreadySent($user->id, 'usage_report', hours: 20)) {
                $this->line(sprintf('  [SKIP] userId=%d %s — already sent today', $user->id, $user->email));
                $skipped++;
                continue;
            }

            // ── Build (or refresh) today's snapshot ─────────────────────────
            try {
                $snapshot = CsDailySnapshot::buildForBusiness($business->id, $user->id, $now);
            } catch (\Throwable $e) {
                Log::error('cs:usage-monitor: snapshot build failed', [
                    'user_id'     => $user->id,
                    'business_id' => $business->id,
                    'error'       => $e->getMessage(),
                ]);
                $skipped++;
                continue;
            }

            // ── Billing details ─────────────────────────────────────────────
            $planName      = ucfirst($billingAccount->subscription_plan ?? 'trial');
            $creditBalance = number_format((int) ($billingAccount->ai_credits ?? 0));

            $vars = [
                'business_name'  => $business->name ?? $user->name,
                'today_date'     => $now->format('d M Y'),
                'conversations'  => $snapshot->total_conversations,
                'new_contacts'   => $snapshot->new_prospects,
                'active_leads'   => $snapshot->active_leads,
                'deals_closed'   => $snapshot->converted,
                'stage_changes'  => $snapshot->stage_changes,
                'plan_name'      => $planName,
                'credit_balance' => $creditBalance,
            ];

            if ($isDry) {
                $this->line(sprintf(
                    '  [DRY] userId=%-6d %-30s conv=%d  prospects=%d  leads=%d  credits=%s',
                    $user->id,
                    $business->name,
                    $snapshot->total_conversations,
                    $snapshot->new_prospects,
                    $snapshot->active_leads,
                    $creditBalance
                ));
                $sent++;
                continue;
            }

            // ── Send via CsMessageRenderer (handles locale, logging, sending) ─
            $ok = $renderer->send($user, 'usage_report', $vars, $business->id);

            if ($ok) {
                $this->line(sprintf('  [OK] userId=%d %s', $user->id, $user->email));
                $sent++;
            } else {
                Log::warning('cs:usage-monitor: message send failed', [
                    'user_id'     => $user->id,
                    'business_id' => $business->id,
                ]);
                $skipped++;
            }
        }

        $this->info(sprintf(
            '[cs:usage-monitor] Done. %d report(s) %s, %d skipped.',
            $sent,
            $isDry ? 'would be sent' : 'sent',
            $skipped
        ));

        return self::SUCCESS;
    }
}
