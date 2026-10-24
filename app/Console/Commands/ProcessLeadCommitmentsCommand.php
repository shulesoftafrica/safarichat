<?php

namespace App\Console\Commands;

use App\Models\LeadCommitment;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\WaSenderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Watches lead commitments ("I'll pay tomorrow"). When the promised time passes
 * with no matching conversion event:
 *   - first, gently nudge the customer (once),
 *   - after a grace window, escalate to the owner/sales team and mark it missed.
 *
 * A commitment is fulfilled automatically by ConversionEventService when the
 * matching event arrives, so this command only ever sees unmet promises.
 */
class ProcessLeadCommitmentsCommand extends Command
{
    protected $signature = 'commitments:process {--grace-hours=24 : Hours after due before escalating}';

    protected $description = 'Nudge customers and escalate missed conversion commitments';

    public function handle(WaSenderService $waSender): int
    {
        $graceHours = (int) $this->option('grace-hours');
        $now = now();

        // 1. Nudge: due time passed, not yet nudged.
        $toNudge = LeadCommitment::where('status', LeadCommitment::STATUS_PENDING)
            ->whereNull('nudged_at')
            ->whereNotNull('due_by')
            ->where('due_by', '<=', $now)
            ->limit(200)->get();

        foreach ($toNudge as $commitment) {
            try {
                $this->nudgeCustomer($commitment, $waSender);
            } catch (\Throwable $e) {
                Log::warning('Commitment nudge failed', ['id' => $commitment->id, 'error' => $e->getMessage()]);
            }
            $commitment->update(['nudged_at' => $now]);
        }

        // 2. Escalate: still pending a grace window past due, not yet escalated.
        $toEscalate = LeadCommitment::where('status', LeadCommitment::STATUS_PENDING)
            ->whereNull('escalated_at')
            ->whereNotNull('due_by')
            ->where('due_by', '<=', $now->copy()->subHours($graceHours))
            ->limit(200)->get();

        foreach ($toEscalate as $commitment) {
            try {
                $this->escalateToTeam($commitment, $waSender);
            } catch (\Throwable $e) {
                Log::warning('Commitment escalation failed', ['id' => $commitment->id, 'error' => $e->getMessage()]);
            }
            $commitment->update([
                'escalated_at' => $now,
                'status'       => LeadCommitment::STATUS_MISSED,
            ]);
        }

        $this->info("Commitments processed: {$toNudge->count()} nudged, {$toEscalate->count()} escalated.");
        return self::SUCCESS;
    }

    private function nudgeCustomer(LeadCommitment $commitment, WaSenderService $waSender): void
    {
        $lead = $commitment->lead;
        if (!$lead) { return; }

        $contact = $lead->contact ?? $lead->businessContact ?? null;
        $phone = $contact->guest_phone ?? $lead->phone_number ?? null;
        if (!$phone) { return; }

        $name = $contact->guest_name ?? $lead->name ?? 'there';
        $msg  = "Hi {$name}! 😊 Just a friendly reminder about what we discussed. "
              . "Let me know if there's anything you need from me to move ahead — I'm here to help.";

        $userId   = $lead->user_id ?? $lead->business->user_id ?? null;
        $instance = $this->instanceFor($userId);
        if ($instance) {
            $waSender->sendMessage($phone, $msg, [], $instance, $userId);
        }
    }

    private function escalateToTeam(LeadCommitment $commitment, WaSenderService $waSender): void
    {
        $lead = $commitment->lead;
        if (!$lead) { return; }

        $business = $lead->business;
        $contact  = $lead->contact ?? $lead->businessContact ?? null;
        $customer = $contact->guest_name ?? $lead->name ?? 'A customer';
        $cphone   = $contact->guest_phone ?? $lead->phone_number ?? '';

        $text  = "⚠️ *Unmet commitment*\n\n";
        $text .= "{$customer} promised to " . ($commitment->expected_event_type ? str_replace('_', ' ', $commitment->expected_event_type) : 'complete a conversion');
        $text .= " by " . ($commitment->due_by ? $commitment->due_by->format('M j, g:i A') : 'the agreed time') . ", but nothing has come through yet.\n";
        if ($commitment->promised_text) {
            $text .= "🗣️ \"" . $commitment->promised_text . "\"\n";
        }
        if ($cphone !== '') { $text .= "📞 {$cphone}\n"; }
        $text .= "\nWorth a personal follow-up.";

        $recipients = [];
        foreach ([$business->user_id ?? null, $lead->assigned_agent_id] as $uid) {
            if (!$uid) { continue; }
            $u = User::find($uid);
            if ($u && !empty($u->phone)) {
                $recipients[preg_replace('/\D+/', '', $u->phone)] = ['phone' => $u->phone, 'user_id' => $u->id];
            }
        }

        foreach ($recipients as $r) {
            $instance = $this->instanceFor($r['user_id']);
            if ($instance) {
                $waSender->sendMessage($r['phone'], $text, [], $instance, $r['user_id']);
            }
        }
    }

    private function instanceFor(?int $userId): ?WhatsappInstance
    {
        if (!$userId) { return null; }
        return WhatsappInstance::where('user_id', $userId)
                ->whereIn('status', ['connected', 'ready'])->latest()->first()
            ?: WhatsappInstance::where('user_id', $userId)->latest()->first();
    }
}
