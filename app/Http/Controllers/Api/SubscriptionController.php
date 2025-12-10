<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Services\CreditService;
use App\Services\PaymentGatewayService;
use App\Services\AutomationControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected CreditService $creditService,
        protected PaymentGatewayService $paymentService,
        protected AutomationControlService $automationService
    ) {}

    /**
     * Get subscription status for paywall modal
     */
    public function getStatus()
    {
        $user = Auth::user();
        
        if ($this->subscriptionService->isActive($user)) {
            return response()->json(['status' => 'active']);
        }

        // Get missed automations for today
        $missedAutomations = $this->automationService->getMissedAutomationsToday($user);
        $formattedMissed = $missedAutomations->map(function($automation) {
            return [
                'type' => ucfirst(str_replace('_', ' ', $automation->automation_type)),
                'target' => $automation->target_data['customer_name'] ?? 'Unknown',
                'scheduled_at' => $automation->scheduled_at->format('H:i'),
                'potential_value' => $automation->potential_value
            ];
        });

        // Generate payment options
        $paymentOptions = [];
        if ($user->country_code === 'TZ') {
            try {
                $merchantData = $this->paymentService->createLipaNumberMerchant($user);
                $paymentOptions = [
                    'merchant_id' => $merchantData['merchant_id'],
                    'qr_code' => $merchantData['qr_code'] ?? null
                ];
            } catch (\Exception $e) {
                $paymentOptions['error'] = 'Failed to generate payment method';
            }
        } else {
            $paymentOptions['stripe_available'] = true;
        }

        return response()->json([
            'status' => 'inactive',
            'subscription_status' => $user->subscription_status,
            'available_credits' => $user->available_credits,
            'missed_automations' => $formattedMissed,
            'country_code' => $user->country_code,
            'payment_options' => $paymentOptions,
            'days_since_expiry' => $user->trial_ends_at ? now()->diffInDays($user->trial_ends_at) : null
        ]);
    }

    /**
     * Get subscription analytics
     */
    public function getAnalytics()
    {
        $user = Auth::user();
        
        $creditAnalytics = $this->creditService->getCreditUsageAnalytics($user);
        $automationStats = $this->automationService->getAutomationStats($user);
        
        return response()->json([
            'subscription' => [
                'status' => $user->subscription_status,
                'is_active' => $this->subscriptionService->isActive($user),
                'days_until_expiry' => $this->subscriptionService->getDaysUntilExpiry($user),
                'current_package' => $user->activeSubscription?->adminPackage?->name
            ],
            'credits' => $creditAnalytics,
            'automations' => $automationStats,
            'recent_activity' => $this->getRecentActivity($user)
        ]);
    }

    /**
     * Get credit balance
     */
    public function getCreditBalance()
    {
        $user = Auth::user();
        
        return response()->json([
            'balance' => $this->creditService->getBalance($user),
            'can_use_credits' => $this->creditService->hasSufficientCredits($user, 1),
            'is_subscription_active' => $this->subscriptionService->isActive($user)
        ]);
    }

    /**
     * Get missed automation recovery suggestions
     */
    public function getRecoverySuggestions()
    {
        $user = Auth::user();
        $suggestions = $this->automationService->getRecoverySuggestions($user);
        
        return response()->json([
            'suggestions' => $suggestions,
            'total_missed' => count($suggestions),
            'can_recover' => $this->subscriptionService->isActive($user)
        ]);
    }

    /**
     * Check if user can execute automation
     */
    public function canExecuteAutomation()
    {
        $user = Auth::user();
        
        return response()->json([
            'can_execute' => $this->automationService->canExecuteAutomation($user),
            'reason' => $this->automationService->canExecuteAutomation($user) 
                ? 'Active subscription' 
                : 'Inactive subscription',
            'subscription_status' => $user->subscription_status
        ]);
    }

    /**
     * Get package recommendations based on usage
     */
    public function getPackageRecommendations()
    {
        $user = Auth::user();
        $currentPackage = $user->activeSubscription?->adminPackage;
        
        // Get usage stats
        $creditUsage = $this->creditService->getCreditUsageAnalytics($user, 30);
        $automationStats = $this->automationService->getAutomationStats($user, 30);
        
        $recommendations = [];
        
        // High usage - recommend upgrade
        if ($creditUsage['average_daily_usage'] > 50) {
            $recommendations[] = [
                'type' => 'upgrade',
                'reason' => 'High credit usage detected',
                'suggested_package' => 'pro',
                'benefits' => ['More contacts allowed', 'Higher credit limits']
            ];
        }
        
        // Many missed automations - recommend reactivation
        if ($automationStats['total_missed'] > 10) {
            $recommendations[] = [
                'type' => 'reactivate',
                'reason' => 'Multiple missed opportunities',
                'urgency' => 'high',
                'potential_loss' => $automationStats['potential_revenue_lost']
            ];
        }
        
        return response()->json([
            'current_package' => $currentPackage?->name,
            'recommendations' => $recommendations,
            'usage_stats' => [
                'credits' => $creditUsage,
                'automations' => $automationStats
            ]
        ]);
    }

    /**
     * Get recent activity for dashboard
     */
    private function getRecentActivity($user): array
    {
        $recentTransactions = $user->creditTransactions()
            ->latest()
            ->take(5)
            ->get()
            ->map(function($transaction) {
                return [
                    'type' => $transaction->transaction_type,
                    'amount' => $transaction->credits_amount,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->diffForHumans()
                ];
            });

        $recentMissed = $user->missedAutomations()
            ->latest()
            ->take(3)
            ->get()
            ->map(function($automation) {
                return [
                    'type' => $automation->automation_type,
                    'customer' => $automation->target_data['customer_name'] ?? 'Unknown',
                    'missed_at' => $automation->created_at->diffForHumans()
                ];
            });

        return [
            'credit_transactions' => $recentTransactions,
            'missed_automations' => $recentMissed
        ];
    }
}
