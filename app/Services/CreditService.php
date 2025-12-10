<?php

namespace App\Services;

use App\Models\User;
use App\Models\CreditTransaction;
use App\Models\AdminPayment;
use App\Models\Conversation;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Calculate credits from payment amount
     */
    public function calculateCreditsFromPayment(float $amount, $package): array
    {
        $packagePrice = $package->price;
        $excessAmount = max(0, $amount - $packagePrice);
        
        // 1 TSH = 1 Credit
        $creditAmount = (int) $excessAmount;
        
        return [
            'package_price' => $packagePrice,
            'excess_amount' => $excessAmount,
            'credits' => $creditAmount
        ];
    }

    /**
     * Add credits to user account
     */
    public function addCredits(User $user, int $credits, string $source, AdminPayment $payment = null): CreditTransaction
    {
        DB::beginTransaction();

        try {
            $balanceBefore = $user->available_credits;
            $balanceAfter = $balanceBefore + $credits;

            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'admin_payment_id' => $payment?->id,
                'transaction_type' => $this->determineTransactionType($source),
                'credits_amount' => $credits,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $source
            ]);

            $user->update(['available_credits' => $balanceAfter]);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deduct credits from user account
     */
    public function deductCredits(User $user, int $credits, string $reason, Conversation $conversation = null): CreditTransaction
    {
        if (!$this->hasSufficientCredits($user, $credits)) {
            throw new \Exception('Insufficient credits');
        }

        DB::beginTransaction();

        try {
            $balanceBefore = $user->available_credits;
            $balanceAfter = $balanceBefore - $credits;

            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'conversation_id' => $conversation?->id,
                'transaction_type' => 'usage',
                'credits_amount' => -$credits,
                'tokens_consumed' => $conversation?->tokens_used,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $reason
            ]);

            $user->update(['available_credits' => $balanceAfter]);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get user's current credit balance
     */
    public function getBalance(User $user): int
    {
        return $user->available_credits;
    }

    /**
     * Rollover credits to new subscription
     */
    public function rolloverCredits(User $user, Subscription $newSubscription): CreditTransaction
    {
        $currentCredits = $user->available_credits;
        
        if ($currentCredits > 0) {
            return CreditTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'rollover',
                'credits_amount' => 0, // No change in balance, just marking the rollover
                'balance_before' => $currentCredits,
                'balance_after' => $currentCredits,
                'description' => "Credits rolled over to new billing cycle",
                'metadata' => [
                    'subscription_id' => $newSubscription->id,
                    'rollover_date' => now()
                ]
            ]);
        }

        return null;
    }

    /**
     * Freeze credits when subscription becomes inactive
     */
    public function freezeCredits(User $user): void
    {
        // Credits remain in user account but are marked as frozen
        // This is handled by subscription status check in hasSufficientCredits
        CreditTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'rollover', // Using rollover to indicate status change
            'credits_amount' => 0,
            'balance_before' => $user->available_credits,
            'balance_after' => $user->available_credits,
            'description' => 'Credits frozen due to inactive subscription'
        ]);
    }

    /**
     * Unfreeze credits when subscription becomes active
     */
    public function unfreezeCredits(User $user): void
    {
        CreditTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'rollover',
            'credits_amount' => 0,
            'balance_before' => $user->available_credits,
            'balance_after' => $user->available_credits,
            'description' => 'Credits unfrozen due to active subscription'
        ]);
    }

    /**
     * Check if user has sufficient credits
     */
    public function hasSufficientCredits(User $user, int $required): bool
    {
        // Credits are only usable with active subscription
        if (!app(SubscriptionService::class)->isActive($user)) {
            return false;
        }

        return $user->available_credits >= $required;
    }

    /**
     * Convert tokens to credits (4 tokens = 1 credit)
     */
    public function tokensToCredits(int $tokens): int
    {
        return (int) ceil($tokens / 4);
    }

    /**
     * Convert credits to tokens
     */
    public function creditsToTokens(int $credits): int
    {
        return $credits * 4;
    }

    /**
     * Get user's credit usage analytics
     */
    public function getCreditUsageAnalytics(User $user, int $days = 30): array
    {
        $transactions = CreditTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total_credits_purchased' => $transactions->where('transaction_type', 'purchase')->sum('credits_amount'),
            'total_credits_used' => abs($transactions->where('transaction_type', 'usage')->sum('credits_amount')),
            'total_tokens_consumed' => $transactions->where('transaction_type', 'usage')->sum('tokens_consumed'),
            'average_daily_usage' => abs($transactions->where('transaction_type', 'usage')->sum('credits_amount')) / $days,
            'current_balance' => $user->available_credits
        ];
    }

    /**
     * Determine transaction type from source string
     */
    private function determineTransactionType(string $source): string
    {
        $sourceMap = [
            'payment_excess' => 'purchase',
            'Welcome bonus' => 'bonus',
            'trial' => 'bonus',
            'refund' => 'refund',
            'admin_credit' => 'bonus',
            'sms_purchase' => 'sms_purchase'
        ];

        foreach ($sourceMap as $key => $type) {
            if (str_contains(strtolower($source), strtolower($key))) {
                return $type;
            }
        }

        return 'purchase';
    }
}