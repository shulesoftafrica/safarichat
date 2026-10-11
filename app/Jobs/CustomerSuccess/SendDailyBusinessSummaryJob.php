<?php

namespace App\Jobs\CustomerSuccess;

use App\Models\Business;
use App\Models\CsDailySnapshot;
use App\Models\CsMessageLog;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\CustomerSuccess\CsMessageRenderer;
use App\Services\CustomerSuccess\DailyRecommendationResolver;
use App\Services\SystemWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Builds today's snapshot for one business, then sends the evening CS daily
 * report to the business owner via the system WhatsApp number.
 *
 * Dispatched by SendCsDailySummaryCommand for each eligible business.
 */
class SendDailyBusinessSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly int $userId,
        public readonly int $businessId,
    ) {
        $this->onQueue('cs');
    }

    public function handle(
        CsMessageRenderer         $renderer,
        DailyRecommendationResolver $recommender,
        SystemWhatsAppService     $systemWa,
    ): void {
        $user     = User::find($this->userId);
        $business = Business::find($this->businessId);

        if (!$user || !$business) {
            Log::warning('SendDailyBusinessSummaryJob: missing user or business', [
                'user_id'     => $this->userId,
                'business_id' => $this->businessId,
            ]);
            return;
        }

        // ── Dedup: only one daily summary per business_id per calendar day ──
        if (CsMessageLog::alreadySent($user->id, 'daily_summary', hours: 20)) {
            Log::info('SendDailyBusinessSummaryJob: already sent today, skip', [
                'business_id' => $this->businessId,
            ]);
            return;
        }

        // ── Check instance connectivity ────────────────────────────────────
        $instance = WhatsappInstance::where('user_id', $this->userId)
            ->where('is_system_default', false) // don't check the CS system instance
            ->orderByDesc('connected_at')
            ->first();

        $isDisconnected = !$instance || $instance->status !== 'connected';

        if ($isDisconnected) {
            $this->sendDisconnectionAlert($user, $business, $systemWa, $renderer);
            return;
        }

        // ── Build today's snapshot ─────────────────────────────────────────
        $today     = CsDailySnapshot::buildForBusiness($this->businessId, $this->userId, now());
        $yesterday = CsDailySnapshot::yesterday($this->businessId);

        // ── Resolve recommendation ─────────────────────────────────────────
        $locale         = $renderer->resolveLocale($user);
        $recommendation = $recommender->resolve($today, $yesterday, $locale);

        // ── Build delta strings ────────────────────────────────────────────
        $convDelta     = CsDailySnapshot::delta($today->total_conversations, $yesterday?->total_conversations);
        $prospDelta    = CsDailySnapshot::delta($today->new_prospects, $yesterday?->new_prospects);

        $vars = [
            'business_name'           => $business->name ?? $user->name,
            'today_date'              => now()->format('d M Y'),
            'total_conversations'     => $today->total_conversations,
            'conversations_delta'     => $convDelta ? " ({$convDelta} vs yesterday)" : '',
            'new_prospects'           => $today->new_prospects,
            'prospects_delta'         => $prospDelta ? " ({$prospDelta} vs yesterday)" : '',
            'active_leads'            => $today->active_leads,
            'closed_today'            => $today->converted,
            'stage_changes'           => $today->stage_changes,
            'lead_new_count'          => $today->lead_new,
            'lead_interested_count'   => $today->lead_interested,
            'lead_engaged_count'      => $today->lead_engaged,
            'lead_converted_count'    => $today->lead_converted,
            'lead_churned_count'      => $today->lead_churned,
            'recommendation'          => $recommendation,
        ];

        // From the business's own WhatsApp number to the manager on its AI agent (email if that is unavailable).
        $result = $renderer->sendToOwner($user, 'daily_summary', $vars, $this->businessId);

        Log::log($result['sent'] ? 'info' : 'warning', 'SendDailyBusinessSummaryJob: daily summary ' . ($result['sent'] ? 'delivered' : 'NOT delivered'), [
            'business_id' => $this->businessId,
            'user_id'     => $user->id,
            'channel'     => $result['channel'],
            'reason'      => $result['reason'],
        ]);
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    /**
     * Send the disconnected-instance alert instead of the daily summary.
     * Deduped per disconnection episode via `cs_message_log` type `whatsapp_disconnected_alert`.
     */
    private function sendDisconnectionAlert(
        User                    $user,
        Business                $business,
        SystemWhatsAppService   $systemWa,
        CsMessageRenderer       $renderer,
    ): void {
        // A disconnection is announced once per episode, not every day: this alert is the only thing the shared
        // platform line sends for a business, so its volume has to stay small.
        if (CsMessageLog::alreadySent($user->id, 'whatsapp_disconnected_alert', hours: (int) config('cs.disconnected_alert_cooldown_hours', 72))) {
            return; // already sent for this disconnection episode
        }

        $vars = [
            'business_name' => $business->name ?? $user->name,
            'reconnect_url' => config('app.url') . '/whatsapp/connect',
        ];

        $sent = $renderer->send($user, 'disconnected_alert', $vars, $this->businessId);

        // WhatsApp is the thing that is down, and the platform line may be too: also tell them by email.
        $emailed = app(\App\Services\OwnerReportDeliveryService::class)->deliverEmail(
            $user,
            'Your WhatsApp is disconnected - customers are not being answered',
            'Your AI sales agent is offline because your WhatsApp is disconnected, so customers messaging you are not '
            . "getting replies.\n\nReconnect here: {$vars['reconnect_url']}  (Settings > WhatsApp > scan the QR code)."
        );
        $sent = $sent || $emailed;

        Log::info('SendDailyBusinessSummaryJob: sent disconnection alert instead', [
            'business_id' => $this->businessId,
            'sent'        => $sent,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendDailyBusinessSummaryJob: failed', [
            'business_id' => $this->businessId,
            'error'       => $e->getMessage(),
        ]);
    }
}
