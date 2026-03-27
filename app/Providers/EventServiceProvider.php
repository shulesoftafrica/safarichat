<?php

namespace App\Providers;

use App\Events\BusinessInactivityEscalated;
use App\Events\BusinessReEngaged;
use App\Events\CsFirstProductCreated;
use App\Events\CreditsAdded;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionUpgraded;
use App\Events\WhatsappInstanceConnected;
use App\Listeners\CustomerSuccess\CreateCsEscalationRecordListener;
use App\Listeners\CustomerSuccess\NotifyCsTeamListener;
use App\Listeners\CustomerSuccess\SendCreditConfirmationListener;
use App\Listeners\CustomerSuccess\SendFirstProductGuideListener;
use App\Listeners\CustomerSuccess\SendReEngagementCelebrationListener;
use App\Listeners\CustomerSuccess\SendSubscriptionSuccessMessageListener;
use App\Listeners\CustomerSuccess\SendUpgradeConfirmationListener;
use App\Listeners\CustomerSuccess\SendWelcomeMessageListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // ---------------------------------------------------------------------------
        // Customer Success – Phase 1: onboarding milestone events
        // ---------------------------------------------------------------------------
        WhatsappInstanceConnected::class => [
            SendWelcomeMessageListener::class,
        ],
        CsFirstProductCreated::class => [
            SendFirstProductGuideListener::class,
        ],

        // ---------------------------------------------------------------------------
        // Customer Success – Phase 4: billing & expansion events
        // ---------------------------------------------------------------------------
        SubscriptionActivated::class => [
            SendSubscriptionSuccessMessageListener::class,
        ],
        SubscriptionUpgraded::class => [
            SendUpgradeConfirmationListener::class,
        ],
        CreditsAdded::class => [
            SendCreditConfirmationListener::class,
        ],

        // ---------------------------------------------------------------------------
        // Customer Success – Phase 5: churn prevention events
        // ---------------------------------------------------------------------------
        BusinessReEngaged::class => [
            SendReEngagementCelebrationListener::class,
        ],
        BusinessInactivityEscalated::class => [
            CreateCsEscalationRecordListener::class,
            NotifyCsTeamListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

