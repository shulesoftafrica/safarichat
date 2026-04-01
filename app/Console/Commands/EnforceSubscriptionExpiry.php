<?php

namespace App\Console\Commands;

use App\Models\BillingAccount;
use App\Models\CreditAdjustment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Enforce Subscription Expiry
 *
 * Runs daily at 00:05 and marks fully expired subscriptions as 'expired'.
 *
 * Responsibility scope (intentionally narrow):
 *   ✅ Mark active subscriptions as expired when subscription_expires_at is past.
 *   ✅ Write an audit record to credit_adjustments.
 *   ✅ Notify the business owner via WhatsApp/email.
 *
 *   ❌ Monthly credit resets — NOT handled here.
 *      The billing platform sends one subscription.renewed webhook per month,
 *      which changes billing_cycle_id, which fires the DB trigger, which
 *      resets credits automatically. No scheduler involvement needed for that.
 */
class EnforceSubscriptionExpiry extends Command
{
    protected $signature   = 'billing:enforce-expiry';
    protected $description = 'Mark active subscriptions as expired when subscription_expires_at has passed.';

    public function handle(): void
    {
        $this->info('[billing:enforce-expiry] Starting subscription expiry enforcement...');

        $expired = BillingAccount::where('subscription_status', 'active')
            ->where('subscription_expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('[billing:enforce-expiry] No expired subscriptions found.');
            return;
        }

        $count = 0;

        foreach ($expired as $account) {
            try {
                $account->update(['subscription_status' => 'expired']);

                // Write expiry record to audit log
                // (trigger only fires on billing_cycle_id/plan/topup changes;
                //  expiry is a status change only, so we write this manually)
                CreditAdjustment::create([
                    'billing_account_id'     => $account->id,
                    'adjustment_type'        => 'expiry',
                    'plan_before'            => $account->subscription_plan,
                    'plan_after'             => $account->subscription_plan,
                    'base_credits_before'    => $account->base_credits,
                    'base_credits_after'     => $account->base_credits,
                    'topup_credits_before'   => $account->topup_credits,
                    'topup_credits_after'    => $account->topup_credits,
                    'ai_credits_used_before' => $account->ai_credits_used,
                    'ai_credits_used_after'  => $account->ai_credits_used,
                    'billing_cycle_id'       => $account->billing_cycle_id,
                    'notes'                  => 'Subscription expired — status set to expired by scheduler',
                    'created_at'             => now(),
                ]);

                // Notify business owner if linked
                if ($account->business) {
                    try {
                        $account->business->notify(
                            new \App\Notifications\SubscriptionExpiredNotification($account)
                        );
                    } catch (\Exception $notifyException) {
                        // Notification failure must NOT block the expiry update
                        Log::warning('[billing:enforce-expiry] Notification failed for billing_account_id=' . $account->id, [
                            'error' => $notifyException->getMessage(),
                        ]);
                    }
                }

                Log::info('[billing:enforce-expiry] Marked as expired', [
                    'billing_account_id' => $account->id,
                    'business_id'        => $account->business_id,
                    'expired_at'         => $account->subscription_expires_at?->toISOString(),
                    'plan'               => $account->subscription_plan,
                ]);

                $count++;

            } catch (\Exception $e) {
                Log::error('[billing:enforce-expiry] Failed to expire billing_account_id=' . $account->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue processing other accounts even if one fails
            }
        }

        $this->info("[billing:enforce-expiry] Done. Expired {$count} subscription(s).");
        Log::info("[billing:enforce-expiry] Completed. Expired {$count} subscription(s).");
    }
}
