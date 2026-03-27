<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\BusinessReEngaged;
use App\Models\CsMessageLog;
use App\Services\CustomerSuccess\CsMessageRenderer;

class SendReEngagementCelebrationListener
{
    public function handle(BusinessReEngaged $event): void
    {
        $user       = $event->user;
        $businessId = $event->businessId;

        // Only send once per 72 hours to avoid duplicates if the event fires multiple times
        if (CsMessageLog::alreadySent($user->id, 're_engagement_celebration', 72)) {
            return;
        }

        $locale        = CsMessageRenderer::resolveLocale($user);
        $businessName  = optional($user->business)->name ?? 'your business';
        $dashboardLink = config('app.url') . '/dashboard';

        CsMessageRenderer::send($user, 're_engagement_celebration', [
            'business_name'  => $businessName,
            'dashboard_link' => $dashboardLink,
        ], $businessId);
    }
}
