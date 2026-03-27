<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\BusinessInactivityEscalated;
use App\Models\CsEscalation;

class CreateCsEscalationRecordListener
{
    public function handle(BusinessInactivityEscalated $event): void
    {
        $user    = $event->user;
        $episode = $event->episode;

        // Determine reason: if trial → no_reply_winback; paid/active → paid_churned_10d
        $reason = $user->subscription_status === 'trial'
            ? CsEscalation::REASON_NO_REPLY
            : CsEscalation::REASON_PAID_CHURNED;

        CsEscalation::createIfNotExists($user->business_id ?? $episode->business_id, $episode->id, $reason);

        // Stamp the episode so we don't fire multiple escalations
        if (! $episode->escalated_at) {
            $episode->update(['escalated_at' => now()]);
        }
    }
}
