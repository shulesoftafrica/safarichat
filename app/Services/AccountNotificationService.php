<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Account Notification Service
 * Handles SMS/WhatsApp notifications to account owners for critical subscription events
 */
class AccountNotificationService
{
    private $waSenderService;

    public function __construct(WaSenderService $waSenderService)
    {
        $this->waSenderService = $waSenderService;
    }

    /**
     * Notify owner when contact limit is reached
     */
    public function notifyContactLimitReached($user, $blockedPhone, $limitCheck)
    {
        try {
            $message = "🚨 *SafariChat Alert*\n\n"
                . "Contact Limit Reached!\n\n"
                . "📱 Phone: {$blockedPhone}\n"
                . "📊 Current: {$limitCheck['current']}/{$limitCheck['max']} contacts\n"
                . "📦 Plan: " . strtoupper($limitCheck['plan']) . "\n\n"
                . "⚠️ New contacts cannot be added. Upgrade your plan to continue receiving messages from new customers.\n\n"
                . "🔗 Upgrade: https://safarichat.ai/settings";

            return $this->sendOwnerNotification($user, $message, 'contact_limit');
        } catch (\Exception $e) {
            Log::error('Failed to notify contact limit', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notify owner when AI credits are low (below 20%)
     */
    public function notifyLowCredits($user, $remainingCredits, $totalCredits)
    {
        try {
            $percentage = round(($remainingCredits / $totalCredits) * 100);
            
            $message = "⚠️ *SafariChat Alert*\n\n"
                . "Low AI Credits!\n\n"
                . "💰 Remaining: " . number_format($remainingCredits) . " credits ({$percentage}%)\n"
                . "📊 Total: " . number_format($totalCredits) . " credits\n\n"
                . "Your AI assistant will stop responding when credits run out.\n\n"
                . "🔗 Top up: https://safarichat.ai/settings";

            return $this->sendOwnerNotification($user, $message, 'low_credits');
        } catch (\Exception $e) {
            Log::error('Failed to notify low credits', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notify owner when AI credits are depleted
     */
    public function notifyCreditsDepletion($user, $blockedOperation)
    {
        try {
            $message = "🚨 *SafariChat Critical Alert*\n\n"
                . "AI Credits Depleted!\n\n"
                . "💰 Balance: 0 credits\n"
                . "⚠️ Operation blocked: {$blockedOperation}\n\n"
                . "Your AI assistant cannot respond to customers. Top up immediately to avoid losing sales.\n\n"
                . "🔗 Top up now: https://safarichat.ai/settings";

            return $this->sendOwnerNotification($user, $message, 'credits_depleted');
        } catch (\Exception $e) {
            Log::error('Failed to notify credits depleted', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notify owner when trial is about to expire (1 day before)
     */
    public function notifyTrialExpiringSoon($user, $daysRemaining)
    {
        try {
            $message = "⏰ *SafariChat Trial Expiring*\n\n"
                . "Your trial ends in {$daysRemaining} day(s)!\n\n"
                . "📦 Current Plan: Trial\n"
                . "⏳ Time left: {$daysRemaining} day(s)\n\n"
                . "Upgrade now to keep your AI assistant active and avoid service interruption.\n\n"
                . "🔗 Upgrade: https://safarichat.ai/settings";

            return $this->sendOwnerNotification($user, $message, 'trial_expiring');
        } catch (\Exception $e) {
            Log::error('Failed to notify trial expiring', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notify owner when trial has expired
     */
    public function notifyTrialExpired($user)
    {
        try {
            $message = "🚨 *SafariChat Trial Expired*\n\n"
                . "Your trial period has ended.\n\n"
                . "📦 Status: Inactive\n"
                . "⚠️ Your AI assistant is paused\n\n"
                . "Upgrade to a paid plan to resume service immediately.\n\n"
                . "🔗 Upgrade now: https://safarichat.ai/settings";

            return $this->sendOwnerNotification($user, $message, 'trial_expired');
        } catch (\Exception $e) {
            Log::error('Failed to notify trial expired', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notify owner when subscription becomes inactive
     */
    public function notifySubscriptionInactive($user, $reason = null)
    {
        try {
            $reasonText = $reason ? "\n📋 Reason: {$reason}" : "";
            
            $message = "🚨 *SafariChat Subscription Inactive*\n\n"
                . "Your subscription is currently inactive.{$reasonText}\n\n"
                . "⚠️ All services are paused\n"
                . "⚠️ AI assistant is not responding\n\n"
                . "Reactivate your subscription to resume service.\n\n"
                . "🔗 Reactivate: https://safarichat.ai/settings";

            return $this->sendOwnerNotification($user, $message, 'subscription_inactive');
        } catch (\Exception $e) {
            Log::error('Failed to notify subscription inactive', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notify owner when booking limit is reached
     */
    public function notifyBookingLimitReached($user, $limitCheck)
    {
        try {
            $message = "🚨 *SafariChat Alert*\n\n"
                . "Monthly Booking Limit Reached!\n\n"
                . "📅 Bookings: {$limitCheck['current']}/{$limitCheck['max']} this month\n"
                . "📦 Plan: " . strtoupper($limitCheck['plan']) . "\n\n"
                . "⚠️ Cannot accept new bookings this month. Upgrade to increase your limit.\n\n"
                . "🔗 Upgrade: https://safarichat.ai/settings";

            return $this->sendOwnerNotification($user, $message, 'booking_limit');
        } catch (\Exception $e) {
            Log::error('Failed to notify booking limit', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification to account owner via WhatsApp
     * 
     * @param User $user
     * @param string $message
     * @param string $type Notification type for logging
     * @return bool
     */
    private function sendOwnerNotification(User $user, string $message, string $type): bool
    {
        try {
            // Get user's phone number and format with country code if needed
            $phone = $user->phone_number ?? $user->phone ?? null;
            
            if (!$phone) {
                Log::warning("Cannot send notification - no phone number for user {$user->id}");
                return false;
            }
            
            // Ensure phone has country code (255 for Tanzania)
            if (!str_starts_with($phone, '255') && !str_starts_with($phone, '+255')) {
                $phone = '255' . $phone;
            }

            // Send via WhatsApp using WaSenderService
            $result = $this->waSenderService->sendMessage(
                $phone,
                $message,
                [
                    'priority' => 'high',
                    'metadata' => [
                        'notification_type' => $type,
                        'user_id' => (string) $user->id, // Convert to string for API validation
                        'timestamp' => now()->toIso8601String()
                    ]
                ],
                null,
                $user->id
            );

            if ($result['success'] ?? false) {
                Log::info("Account notification sent successfully", [
                    'user_id' => $user->id,
                    'type' => $type,
                    'phone' => $phone
                ]);
                return true;
            } else {
                Log::warning("Account notification failed", [
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception sending account notification", [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
