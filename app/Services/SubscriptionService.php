<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\AdminPackage;
use App\Models\AdminPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Create a trial subscription for a new user
     */
    public function createTrialSubscription(User $user, AdminPackage $package): Subscription
    {
        return Subscription::create([
            'user_id' => $user->id,
            'admin_package_id' => $package->id,
            'status' => 'trial',
            'starts_at' => now(),
            'ends_at' => now()->addDays(3),
            'trial_ends_at' => now()->addDays(3),
            'auto_renew' => true
        ]);
    }

    /**
     * Activate subscription after successful payment
     */
    public function activateSubscription(User $user, AdminPayment $payment): Subscription
    {
        DB::beginTransaction();

        try {
            // Get the package from the payment
            $booking = $payment->adminBooking;
            $package = $booking->adminPackage;

            // Find existing subscription or create new one
            $subscription = $user->subscriptions()->where('status', '!=', 'cancelled')->first();
            
            if ($subscription) {
                // Update existing subscription
                $subscription->update([
                    'admin_package_id' => $package->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($payment->subscription_months ?? 1),
                    'auto_renew' => true
                ]);
            } else {
                // Create new subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'admin_package_id' => $package->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($payment->subscription_months ?? 1),
                    'auto_renew' => true
                ]);
            }

            // Update user status
            $user->update([
                'subscription_status' => 'active'
            ]);

            DB::commit();
            return $subscription;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upgrade user package
     */
    public function upgradePackage(User $user, AdminPackage $newPackage): bool
    {
        $subscription = $user->activeSubscription;
        
        if (!$subscription) {
            throw new \Exception('No active subscription found');
        }

        $subscription->update([
            'admin_package_id' => $newPackage->id
        ]);

        return true;
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Subscription $subscription, string $reason): bool
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'auto_renew' => false
        ]);

        $subscription->user->update([
            'subscription_status' => 'inactive'
        ]);

        return true;
    }

    /**
     * Check for expiring subscriptions and send notifications
     */
    public function checkExpiryAndNotify(): void
    {
        $warnings = [
            7 => 'Your subscription expires in 7 days. Renew now to avoid interruption.',
            3 => 'Your subscription expires in 3 days. Don\'t let your sales stop!',
            1 => 'Your subscription expires tomorrow. Renew immediately!'
        ];

        foreach ($warnings as $days => $message) {
            $expiringSubscriptions = Subscription::where('status', 'active')
                ->where('ends_at', '>=', now()->addDays($days))
                ->where('ends_at', '<', now()->addDays($days + 1))
                ->with('user')
                ->get();

            foreach ($expiringSubscriptions as $subscription) {
                app(NotificationService::class)->scheduleExpiryWarning(
                    $subscription->user, 
                    $subscription->ends_at, 
                    $message
                );
            }
        }
    }

    /**
     * Extend trial period
     */
    public function extendTrial(User $user, int $days): bool
    {
        $subscription = $user->subscriptions()->where('status', 'trial')->first();
        
        if ($subscription) {
            $subscription->update([
                'ends_at' => $subscription->ends_at->addDays($days),
                'trial_ends_at' => $subscription->trial_ends_at->addDays($days)
            ]);

            $user->update([
                'trial_ends_at' => $subscription->trial_ends_at
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check if user has active subscription
     */
    public function isActive(User $user): bool
    {
        return $user->subscription_status === 'active' || 
               ($user->subscription_status === 'trial' && $user->trial_ends_at > now());
    }

    /**
     * Get days until subscription expiry
     */
    public function getDaysUntilExpiry(User $user): int
    {
        $subscription = $user->activeSubscription;
        
        if (!$subscription) {
            return 0;
        }

        return max(0, now()->diffInDays($subscription->ends_at, false));
    }

    /**
     * Process expired subscriptions
     */
    public function processExpiredSubscriptions(): void
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->with('user')
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);
            
            $subscription->user->update(['subscription_status' => 'inactive']);
            
            // Freeze credits (don't delete them)
            app(CreditService::class)->freezeCredits($subscription->user);
        }
    }
}