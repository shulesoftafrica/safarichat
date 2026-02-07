<?php

namespace App\Services;

use App\Models\User;
use App\Models\BillingAccount;
use App\Models\BusinessContact;
use Illuminate\Support\Facades\Log;

/**
 * Billing Alert Service
 * Generates dashboard warnings for approaching/exceeded limits
 */
class BillingAlertService
{
    private $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Get all active alerts for a user
     * Returns array of alert objects with severity, type, message, action
     * 
     * @param int $userId
     * @return array
     */
    public function getActiveAlerts($userId): array
    {
        $alerts = [];
        
        try {
            $user = User::find($userId);
            if (!$user || !$user->business) {
                return [];
            }
            
            $billingAccount = $user->business->billingAccount;
            
            if (!$billingAccount) {
                return [];
            }

            // Check subscription status using billing_account fields directly
            if ($billingAccount->status === 'inactive' || $billingAccount->status === 'cancelled') {
                $alerts[] = [
                    'severity' => 'critical',
                    'type' => 'subscription_inactive',
                    'title' => 'Subscription Inactive',
                    'message' => 'Your subscription is inactive. All services are paused.',
                    'action' => [
                        'text' => 'Reactivate Now',
                        'url' => url('home/settings')
                    ],
                    'icon' => '🚨'
                ];
            }

            // Check trial expiration
            if ($billingAccount->subscription_plan === 'trial' && $billingAccount->subscription_expires_at) {
                $daysRemaining = now()->diffInDays($billingAccount->subscription_expires_at, false);
                
                if ($daysRemaining <= 0) {
                    $alerts[] = [
                        'severity' => 'critical',
                        'type' => 'trial_expired',
                        'title' => 'Trial Expired',
                        'message' => 'Your trial period has ended. Upgrade to continue using SafariChat.',
                        'action' => [
                            'text' => 'Upgrade Now',
                            'url' => url('home/settings')
                        ],
                        'icon' => '⏰'
                    ];
                } elseif ($daysRemaining <= 3) {
                    $alerts[] = [
                        'severity' => 'warning',
                        'type' => 'trial_expiring',
                        'title' => 'Trial Expiring Soon',
                        'message' => "Your trial ends in {$daysRemaining} day(s). Upgrade to avoid service interruption.",
                        'action' => [
                            'text' => 'Upgrade Now',
                            'url' => url('home/settings')
                        ],
                        'icon' => '⏰'
                    ];
                }
            }

            // Check AI credits
            $creditsAlert = $this->checkAiCredits($billingAccount);
            if ($creditsAlert) {
                $alerts[] = $creditsAlert;
            }

            // Check contact limit
            $contactAlert = $this->checkContactLimit($userId, $billingAccount->subscription_plan);
            if ($contactAlert) {
                $alerts[] = $contactAlert;
            }

            // Check booking calendar limit
            $calendarAlert = $this->checkBookingCalendarLimit($userId, $subscription->plan_type);
            if ($calendarAlert) {
                $alerts[] = $calendarAlert;
            }

            // Check storage limit (if applicable)
            $storageAlert = $this->checkStorageLimit($userId, $subscription->plan_type);
            if ($storageAlert) {
                $alerts[] = $storageAlert;
            }

        } catch (\Exception $e) {
            Log::error('Failed to get billing alerts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        return $alerts;
    }

    /**
     * Check AI credits and return alert if low/depleted
     */
    private function checkAiCredits($billingAccount): ?array
    {
        $limits = config("safarichat_billing.plans.{$billingAccount->subscription_plan}.limits.ai_credits");
        
        if ($limits === 'unlimited') {
            return null;
        }

        $remaining = $billingAccount->ai_credits ?? 0;
        $percentage = $limits > 0 ? ($remaining / $limits) * 100 : 0;

        if ($remaining <= 0) {
            return [
                'severity' => 'critical',
                'type' => 'credits_depleted',
                'title' => 'AI Credits Depleted',
                'message' => 'Your AI assistant cannot respond to customers. Top up immediately.',
                'action' => [
                    'text' => 'Top Up Now',
                    'url' => url('home/settings')
                ],
                'icon' => '🚨',
                'stats' => [
                    'remaining' => 0,
                    'total' => $limits,
                    'percentage' => 0
                ]
            ];
        } elseif ($percentage <= 10) {
            return [
                'severity' => 'critical',
                'type' => 'credits_critical',
                'title' => 'AI Credits Critical',
                'message' => sprintf('Only %s credits remaining (%.1f%%). Your AI will stop soon.', number_format($remaining), $percentage),
                'action' => [
                    'text' => 'Top Up Now',
                    'url' => url('home/settings')
                ],
                'icon' => '⚠️',
                'stats' => [
                    'remaining' => $remaining,
                    'total' => $limits,
                    'percentage' => round($percentage)
                ]
            ];
        } elseif ($percentage <= 20) {
            return [
                'severity' => 'warning',
                'type' => 'credits_low',
                'title' => 'Low AI Credits',
                'message' => sprintf('%s credits remaining (%.1f%%). Consider topping up soon.', number_format($remaining), $percentage),
                'action' => [
                    'text' => 'Top Up',
                    'url' => url('home/settings')
                ],
                'icon' => '💰',
                'stats' => [
                    'remaining' => $remaining,
                    'total' => $limits,
                    'percentage' => round($percentage)
                ]
            ];
        }

        return null;
    }

    /**
     * Check contact limit and return alert if approaching/exceeded
     */
    private function checkContactLimit($userId, $planType): ?array
    {
        $limits = config("safarichat_billing.plans.{$planType}.limits.max_contacts");
        
        if ($limits === 'unlimited' || !$limits || $limits <= 0) {
            return null;
        }

        $currentCount = BusinessContact::where('user_id', $userId)->count();
        $percentage = $limits > 0 ? ($currentCount / $limits) * 100 : 0;

        if ($currentCount >= $limits) {
            return [
                'severity' => 'critical',
                'type' => 'contact_limit_reached',
                'title' => 'Contact Limit Reached',
                'message' => sprintf('Cannot add new contacts (%d/%d). Upgrade to continue receiving messages.', $currentCount, $limits),
                'action' => [
                    'text' => 'Upgrade Plan',
                    'url' => url('home/settings')
                ],
                'icon' => '📱',
                'stats' => [
                    'current' => $currentCount,
                    'max' => $limits,
                    'percentage' => 100
                ]
            ];
        } elseif ($percentage >= 90) {
            return [
                'severity' => 'warning',
                'type' => 'contact_limit_warning',
                'title' => 'Contact Limit Approaching',
                'message' => sprintf('%d/%d contacts used (%.0f%%). Upgrade soon to avoid blocking new customers.', $currentCount, $limits, $percentage),
                'action' => [
                    'text' => 'Upgrade Plan',
                    'url' => url('home/settings')
                ],
                'icon' => '📱',
                'stats' => [
                    'current' => $currentCount,
                    'max' => $limits,
                    'percentage' => round($percentage)
                ]
            ];
        } elseif ($percentage >= 80) {
            return [
                'severity' => 'info',
                'type' => 'contact_limit_info',
                'title' => 'Contact Usage',
                'message' => sprintf('%d/%d contacts used (%.0f%%).', $currentCount, $limits, $percentage),
                'action' => [
                    'text' => 'View Plans',
                    'url' => url('home/settings')
                ],
                'icon' => '📊',
                'stats' => [
                    'current' => $currentCount,
                    'max' => $limits,
                    'percentage' => round($percentage)
                ]
            ];
        }

        return null;
    }

    /**
     * Check booking calendar limit
     */
    private function checkBookingCalendarLimit($userId, $planType): ?array
    {
        $limits = config("safarichat_billing.plans.{$planType}.booking_calendars");
        
        if ($limits === 'unlimited') {
            return null;
        }

        $currentCount = \App\Models\BookingCalendar::where('user_id', $userId)->count();

        if ($currentCount >= $limits && $limits > 0) {
            return [
                'severity' => 'warning',
                'type' => 'calendar_limit_reached',
                'title' => 'Booking Calendar Limit',
                'message' => sprintf('Cannot create more calendars (%d/%d). Upgrade to add more.', $currentCount, $limits),
                'action' => [
                    'text' => 'Upgrade Plan',
                    'url' => url('home/settings')
                ],
                'icon' => '📅',
                'stats' => [
                    'current' => $currentCount,
                    'max' => $limits,
                    'percentage' => 100
                ]
            ];
        }

        return null;
    }

    /**
     * Check storage limit (placeholder - implement if storage tracking exists)
     */
    private function checkStorageLimit($userId, $planType): ?array
    {
        // TODO: Implement storage checking if you have storage limits in your plans
        return null;
    }

    /**
     * Get summary statistics for dashboard widget
     */
    public function getBillingSummary($userId): array
    {
        try {
            $user = User::find($userId);
            if (!$user || !$user->business) {
                return [];
            }
            
            $billingAccount = $user->business->billingAccount;
            
            if (!$billingAccount) {
                return [];
            }

            $planType = $billingAccount->subscription_plan;

            // AI Credits
            $creditLimit = config("safarichat_billing.plans.{$planType}.limits.ai_credits");
            $creditRemaining = $billingAccount->ai_credits ?? 0;
            $creditPercentage = ($creditLimit !== 'unlimited' && $creditLimit > 0) 
                ? round(($creditRemaining / $creditLimit) * 100) 
                : null;

            // Contacts
            $contactLimit = config("safarichat_billing.plans.{$planType}.limits.max_contacts");
            $contactCount = BusinessContact::where('user_id', $userId)->count();
            $contactPercentage = ($contactLimit !== 'unlimited' && $contactLimit > 0) 
                ? round(($contactCount / $contactLimit) * 100) 
                : null;

            // Calendars
            $calendarLimit = config("safarichat_billing.plans.{$planType}.limits.booking_calendars");
            $calendarCount = \App\Models\BookingCalendar::where('user_id', $userId)->count();

            return [
                'plan_type' => strtoupper($planType),
                'status' => $billingAccount->status,
                'trial_ends_at' => $billingAccount->subscription_expires_at,
                'credits' => [
                    'remaining' => $creditRemaining,
                    'limit' => $creditLimit,
                    'percentage' => $creditPercentage,
                    'unlimited' => $creditLimit === 'unlimited'
                ],
                'contacts' => [
                    'current' => $contactCount,
                    'limit' => $contactLimit,
                    'percentage' => $contactPercentage,
                    'unlimited' => $contactLimit === 'unlimited'
                ],
                'calendars' => [
                    'current' => $calendarCount,
                    'limit' => $calendarLimit,
                    'unlimited' => $calendarLimit === 'unlimited'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get billing summary', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}

