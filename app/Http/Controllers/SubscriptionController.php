<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use App\Services\CreditService;
use App\Services\PaymentGatewayService;
use App\Models\AdminPackage;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected CreditService $creditService,
        protected PaymentGatewayService $paymentService
    ) {}

    /**
     * Show subscription dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;
        $packages = AdminPackage::where('package_type', '!=', 'corporate')->get();
        
        $data = [
            'user' => $user,
            'subscription' => $subscription,
            'packages' => $packages,
            'current_package' => $subscription?->adminPackage,
            'credit_balance' => $this->creditService->getBalance($user),
            'credit_analytics' => $this->creditService->getCreditUsageAnalytics($user),
            'days_until_expiry' => $this->subscriptionService->getDaysUntilExpiry($user),
            'is_active' => $this->subscriptionService->isActive($user)
        ];

        return view('subscription.dashboard', $data);
    }

    /**
     * Show paywall modal for inactive users
     */
    public function paywall()
    {
        $user = Auth::user();
        
        if ($this->subscriptionService->isActive($user)) {
            return redirect()->route('dashboard');
        }

        $packages = AdminPackage::where('package_type', '!=', 'corporate')->get();
        $missedToday = app(\App\Services\AutomationControlService::class)
            ->getMissedAutomationsToday($user);

        return view('subscription.paywall', [
            'user' => $user,
            'packages' => $packages,
            'missed_automations' => $missedToday,
            'credit_balance' => $user->available_credits
        ]);
    }

    /**
     * Check payment status for reactivation
     */
    public function checkPaymentStatus(Request $request)
    {
        $user = Auth::user();
        
        // Check if subscription is now active
        if ($this->subscriptionService->isActive($user)) {
            // Resume any pending automations
            $resumed = app(\App\Services\AutomationControlService::class)
                ->resumePendingAutomations($user);
            
            return response()->json([
                'status' => 'active',
                'message' => 'Subscription reactivated successfully!',
                'resumed_automations' => $resumed
            ]);
        }

        return response()->json([
            'status' => 'inactive',
            'message' => 'Payment not yet confirmed. Please try again in a few minutes.'
        ]);
    }
}
