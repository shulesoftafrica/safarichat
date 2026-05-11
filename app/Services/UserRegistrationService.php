<?php

namespace App\Services;

use App\Models\OtpAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserRegistrationService
{
    protected SystemWhatsAppService $systemWhatsApp;

    public function __construct(SystemWhatsAppService $systemWhatsApp)
    {
        $this->systemWhatsApp = $systemWhatsApp;
    }

    /**
     * Send OTP for user registration
     */
    public function sendRegistrationOtp(string $phoneNumber, string $name = null): array
    {
        // Generate 6-digit OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes
        $cacheKey = "registration_otp:{$phoneNumber}";
        Cache::put($cacheKey, [
            'code'       => $otpCode,
            'phone'      => $phoneNumber,
            'name'       => $name,
            'created_at' => now()
        ], now()->addMinutes(10));

        // Attempt to send OTP via system WhatsApp instance
        try {
            $sent = $this->systemWhatsApp->sendOtpVerification($phoneNumber, $otpCode, $name);

            if ($sent) {
                OtpAttempt::record($phoneNumber, 'registration', 'whatsapp', 'sent');

                return [
                    'success'    => true,
                    'message'    => 'OTP sent via WhatsApp',
                    'method'     => 'whatsapp',
                    'expires_in' => '10 minutes'
                ];
            }
        } catch (Exception $e) {
            \Log::warning('WhatsApp OTP failed, attempting SMS fallback', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);

            OtpAttempt::record($phoneNumber, 'registration', 'whatsapp', 'failed', $e->getMessage());
        }

        // Fallback to SMS if WhatsApp fails
        return $this->sendOtpViaSms($phoneNumber, $otpCode);
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(string $phoneNumber, string $otpCode): bool
    {
        $cacheKey = "registration_otp:{$phoneNumber}";
        $otpData  = Cache::get($cacheKey);

        if (!$otpData) {
            return false; // OTP expired or doesn't exist
        }

        return $otpData['code'] === $otpCode;
    }

    /**
     * Complete user registration after OTP verification
     */
    public function completeRegistration(array $userData, string $otpCode): User
    {
        $phoneNumber = $userData['phone'];

        // Verify OTP
        if (!$this->verifyOtp($phoneNumber, $otpCode)) {
            throw new Exception('Invalid or expired OTP code');
        }

        // Normalize phone to E.164 format before persisting.
        // Any phone that already carries a '+' is treated as internationally qualified.
        // Numbers with 10+ digits but no '+' just need a '+' prepended.
        // Shorter numbers are an edge case — we must not corrupt them silently.
        if (!str_starts_with($phoneNumber, '+')) {
            $digits = preg_replace('/[^0-9]/', '', $phoneNumber);
            if (strlen($digits) >= 10) {
                $phoneNumber = '+' . $digits;
            }
            // Numbers shorter than 10 digits are left as-is so the admin can
            // see them in their raw form rather than a silently corrupted value.
        }

        // Check if user already exists
        $existingUser = User::where('phone', $phoneNumber)->first();
        if ($existingUser) {
            throw new Exception('User with this phone number already exists');
        }

        // Create user account
        $user = User::create([
            'name'              => $userData['name'],
            'phone'             => $phoneNumber,
            'email'             => $userData['email'] ?? null,
            'password'          => isset($userData['password']) ? Hash::make($userData['password']) : null,
            'phone_verified_at' => now(),
            'verified'          => 1,
            'created_at'        => now()
        ]);

        // Mark the matching OTP attempt as verified for audit trail
        OtpAttempt::where('phone', $phoneNumber)
            ->where('type', 'registration')
            ->where('delivery_status', 'sent')
            ->latest()
            ->first()
            ?->markVerified();

        // Provision trial billing account so new users have credits from the start
        $this->provisionTrialBilling($user);

        // Send welcome message via system instance
        try {
            $this->systemWhatsApp->sendWelcomeMessage($phoneNumber, $user->name);
        } catch (Exception $e) {
            \Log::warning('Failed to send welcome message', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id
            ]);
            // Don't fail registration if welcome message fails
        }

        // Clear OTP from cache
        Cache::forget("registration_otp:{$phoneNumber}");

        \Log::info('User registration completed', [
            'user_id' => $user->id,
            'phone'   => $phoneNumber,
            'method'  => 'whatsapp_otp'
        ]);

        return $user;
    }

    /**
     * Send password reset OTP
     */
    public function sendPasswordResetOtp(string $phoneNumber): array
    {
        // Check if user exists
        $user = User::where('phone', $phoneNumber)->first();
        if (!$user) {
            throw new Exception('User not found with this phone number');
        }

        // Generate OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache
        $cacheKey = "password_reset_otp:{$phoneNumber}";
        Cache::put($cacheKey, [
            'code'       => $otpCode,
            'user_id'    => $user->id,
            'created_at' => now()
        ], now()->addMinutes(10));

        // Send via system WhatsApp
        try {
            $sent = $this->systemWhatsApp->sendPasswordResetMessage($phoneNumber, $otpCode, $user->name);

            if ($sent) {
                OtpAttempt::record($phoneNumber, 'password_reset', 'whatsapp', 'sent');

                return [
                    'success' => true,
                    'message' => 'Password reset OTP sent via WhatsApp',
                    'method'  => 'whatsapp'
                ];
            }
        } catch (Exception $e) {
            \Log::warning('WhatsApp password reset failed', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id
            ]);

            OtpAttempt::record($phoneNumber, 'password_reset', 'whatsapp', 'failed', $e->getMessage());
        }

        // Fallback to SMS
        return $this->sendOtpViaSms($phoneNumber, $otpCode, 'password_reset');
    }

    /**
     * Reset password with OTP verification
     */
    public function resetPassword(string $phoneNumber, string $otpCode, string $newPassword): bool
    {
        $cacheKey = "password_reset_otp:{$phoneNumber}";
        $otpData  = Cache::get($cacheKey);

        if (!$otpData || $otpData['code'] !== $otpCode) {
            throw new Exception('Invalid or expired OTP code');
        }

        // Update user password
        $user = User::find($otpData['user_id']);
        if (!$user) {
            throw new Exception('User not found');
        }

        $user->update([
            'password'          => Hash::make($newPassword),
            'password_reset_at' => now()
        ]);

        // Mark audit record
        OtpAttempt::where('phone', $phoneNumber)
            ->where('type', 'password_reset')
            ->where('delivery_status', 'sent')
            ->latest()
            ->first()
            ?->markVerified();

        // Clear OTP
        Cache::forget($cacheKey);

        \Log::info('Password reset completed', [
            'user_id' => $user->id,
            'phone'   => $phoneNumber
        ]);

        return true;
    }

    /**
     * Attempt SMS OTP delivery.
     *
     * NOTE: No SMS gateway is integrated yet. This method records the failure
     * honestly and throws so the caller can surface a real error to the user
     * rather than silently lying about a successful delivery.
     *
     * When a real SMS provider (e.g. Africa's Talking, Twilio) is integrated,
     * implement the API call here, record 'sent' on success, or catch and
     * record 'failed' on error.
     */
    private function sendOtpViaSms(string $phoneNumber, string $otpCode, string $type = 'registration'): array
    {
        \Log::error('OTP delivery fully failed — no SMS gateway configured', [
            'phone' => $phoneNumber,
            'type'  => $type,
        ]);

        OtpAttempt::record(
            $phoneNumber,
            $type,
            'sms',
            'undeliverable',
            'No SMS gateway configured'
        );

        // Surface the real failure to the caller instead of pretending success.
        return [
            'success' => false,
            'message' => 'We could not send your OTP at this time. Please try again later or contact support.',
            'method'  => 'none',
        ];
    }

    /**
     * Send OTP for user login
     */
    public function sendLoginOtp(string $phoneNumber): array
    {
        // Generate 6-digit OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes
        $cacheKey = "login_otp:{$phoneNumber}";
        Cache::put($cacheKey, [
            'code'       => $otpCode,
            'phone'      => $phoneNumber,
            'created_at' => now()
        ], now()->addMinutes(10));

        // Attempt to send OTP via system WhatsApp instance
        try {
            $sent = $this->systemWhatsApp->sendLoginOtp($phoneNumber, $otpCode);

            if ($sent) {
                OtpAttempt::record($phoneNumber, 'login', 'whatsapp', 'sent');

                return [
                    'success'    => true,
                    'message'    => 'Login OTP sent via WhatsApp',
                    'method'     => 'whatsapp',
                    'expires_in' => '10 minutes',
                    'expires_at' => now()->addMinutes(10)->toISOString()
                ];
            }
        } catch (Exception $e) {
            \Log::warning('WhatsApp login OTP failed, attempting SMS fallback', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);

            OtpAttempt::record($phoneNumber, 'login', 'whatsapp', 'failed', $e->getMessage());
        }

        // Fallback to SMS if WhatsApp fails
        return $this->sendOtpViaSms($phoneNumber, $otpCode, 'login');
    }


    public function verifyLoginOtp(string $phoneNumber, string $otpCode): bool
    {
        $cacheKey = "login_otp:{$phoneNumber}";
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return false; // OTP expired or doesn't exist
        }
        
        $isValid = $otpData['code'] === $otpCode;
        
        // Clear OTP from cache after verification (prevent reuse)
        if ($isValid) {
            Cache::forget($cacheKey);
        }
        
        return $isValid;
    }
    
    /**
     * Get registration statistics
     */
    public function getRegistrationStats($days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_registrations' => User::where('created_at', '>=', $startDate)->count(),
            'whatsapp_verified' => User::whereNotNull('phone_verified_at')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'period_days' => $days,
            'system_whatsapp_available' => $this->systemWhatsApp->isAvailable()
        ];
    }

    /**
     * Provision a 3-day trial billing account for a newly registered user.
     * Creates the business record if the user doesn't have one yet, then
     * creates a BillingAccount with base_credits set so the GENERATED
     * available_credits column starts at 1000 (not 0).
     */
    private function provisionTrialBilling(User $user): void
    {
        try {
            $limits = config('safarichat_billing.plans.trial.limits', []);
            $trialCredits = $limits['ai_credits'] ?? 1000;

            $business = $user->business;
            if (!$business) {
                $business = \App\Models\Business::create([
                    'user_id'    => $user->id,
                    'name'       => $user->business_name ?? $user->name . "'s Business",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Skip if billing account already exists
            if ($business->billingAccount) {
                return;
            }

            \App\Models\BillingAccount::create([
                'business_id'            => $business->id,
                'subscription_plan'      => 'trial',
                'subscription_started_at'=> now(),
                'subscription_expires_at'=> now()->addDays(3),
                'trial_ends_at'          => now()->addDays(3),
                'next_billing_date'      => now()->addDays(3),
                'base_credits'           => $trialCredits, // drives available_credits (GENERATED)
                'topup_credits'          => 0,
                'ai_credits'             => $trialCredits, // backward-compat reads
                'ai_credits_used'        => 0,
                'max_contacts'           => $limits['max_contacts'] ?? 10,
                'max_products'           => $limits['max_products'] ?? 1,
                'whatsapp_channels'      => $limits['whatsapp_channels'] ?? 1,
                'customer_followups'     => $limits['customer_followups'] ?? false,
                'customer_categorization'=> $limits['customer_categorization'] ?? false,
                'booking_calendars'      => $limits['booking_calendars'] ?? false,
                'sales_reports'          => $limits['sales_reports'] ?? false,
                'unlimited_messages'     => $limits['unlimited_messages'] ?? false,
                'credits_rollover'       => false,
                'status'                 => 'active',
                'notes'                  => 'Auto-created trial account during WhatsApp OTP registration',
            ]);

            \App\Models\Subscription::create([
                'user_id'       => $user->id,
                'status'        => 'active',
                'starts_at'     => now(),
                'trial_ends_at' => now()->addDays(3),
                'ends_at'       => now()->addDays(3),
                'auto_renew'    => false,
                'metadata'      => [
                    'plan_type'           => 'trial',
                    'created_during'      => 'whatsapp_otp_registration',
                    'trial_duration_days' => 3,
                ],
            ]);

            \Log::info('Trial billing provisioned for new WhatsApp OTP user', [
                'user_id'        => $user->id,
                'business_id'    => $business->id,
                'trial_credits'  => $trialCredits,
                'trial_expires'  => now()->addDays(3)->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to provision trial billing after WhatsApp OTP registration', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            // Do NOT rethrow — billing failure must not break registration
        }
    }
}