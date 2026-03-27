<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\BusinessInactivityEscalated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyCsTeamListener
{
    public function handle(BusinessInactivityEscalated $event): void
    {
        $user       = $event->user;
        $episode    = $event->episode;
        $businessId = $episode->business_id;

        $tier    = $episode->tier_reached ?? 'churned';
        $subject = "[CS Alert] Business #{$businessId} Escalated — tier: {$tier}";
        $body    = implode("\n", [
            "A business has been escalated for human follow-up.",
            "",
            "Business ID : {$businessId}",
            "User ID     : {$user->id}",
            "User email  : {$user->email}",
            "Tier reached: {$tier}",
            "Episode ID  : {$episode->id}",
            "Started at  : {$episode->started_at}",
            "Plan        : " . ($user->business->subscription_plan ?? 'unknown'),
            "",
            "Action needed: reach out within 24 hours.",
        ]);

        // Log regardless
        Log::channel('stack')->critical($subject, [
            'user_id'     => $user->id,
            'business_id' => $businessId,
            'episode_id'  => $episode->id,
            'tier'        => $tier,
        ]);

        // Email the CS team if configured
        $csEmail = config('mail.cs_team_email');
        if (filter_var($csEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw($body, static function ($message) use ($csEmail, $subject) {
                    $message->to($csEmail)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            } catch (\Throwable $e) {
                Log::warning('NotifyCsTeamListener: failed to send email', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
