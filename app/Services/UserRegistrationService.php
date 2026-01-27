<?php

namespace App\Services;

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
            'code' => $otpCode,
            'phone' => $phoneNumber,
            'name' => $name,
            'created_at' => now()
        ], now()->addMinutes(10));
        
        // Attempt to send OTP via system WhatsApp instance
        try {
            $sent = $this->systemWhatsApp->sendOtpVerification($phoneNumber, $otpCode, $name);
            
            if ($sent) {
                return [
                    'success' => true,
                    'message' => 'OTP sent via WhatsApp',
                    'method' => 'whatsapp',
                    'expires_in' => '10 minutes'
                ];
            }
        } catch (Exception $e) {
            \Log::warning('WhatsApp OTP failed, attempting SMS fallback', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
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
        $otpData = Cache::get($cacheKey);
        
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
        
        // Check if user already exists
        $existingUser = User::where('phone', $phoneNumber)->first();
        if ($existingUser) {
            throw new Exception('User with this phone number already exists');
        }
        
        // Create user account
        $user = User::create([
            'name' => $userData['name'],
            'phone' => $phoneNumber,
            'email' => $userData['email'] ?? null,
            'password' => isset($userData['password']) ? Hash::make($userData['password']) : null,
            'phone_verified_at' => now(),
            'verified' => 1,
            'created_at' => now()
        ]);
        
        // Send welcome message via system instance
        try {
            $this->systemWhatsApp->sendWelcomeMessage($phoneNumber, $user->name);
        } catch (Exception $e) {
            \Log::warning('Failed to send welcome message', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            // Don't fail registration if welcome message fails
        }
        
        // Clear OTP from cache
        $cacheKey = "registration_otp:{$phoneNumber}";
        Cache::forget($cacheKey);
        
        \Log::info('User registration completed', [
            'user_id' => $user->id,
            'phone' => $phoneNumber,
            'method' => 'whatsapp_otp'
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
            'code' => $otpCode,
            'user_id' => $user->id,
            'created_at' => now()
        ], now()->addMinutes(10));
        
        // Send via system WhatsApp
        try {
            $sent = $this->systemWhatsApp->sendPasswordResetMessage($phoneNumber, $otpCode, $user->name);
            
            if ($sent) {
                return [
                    'success' => true,
                    'message' => 'Password reset OTP sent via WhatsApp',
                    'method' => 'whatsapp'
                ];
            }
        } catch (Exception $e) {
            \Log::warning('WhatsApp password reset failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
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
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData || $otpData['code'] !== $otpCode) {
            throw new Exception('Invalid or expired OTP code');
        }
        
        // Update user password
        $user = User::find($otpData['user_id']);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        $user->update([
            'password' => Hash::make($newPassword),
            'password_reset_at' => now()
        ]);
        
        // Clear OTP
        Cache::forget($cacheKey);
        
        \Log::info('Password reset completed', [
            'user_id' => $user->id,
            'phone' => $phoneNumber
        ]);
        
        return true;
    }
    
    /**
     * Fallback SMS sending (placeholder implementation)
     */
    private function sendOtpViaSms(string $phoneNumber, string $otpCode, string $type = 'registration'): array
    {
        // In a real implementation, you would use an SMS service like Twilio, Africa's Talking, etc.
        \Log::info('SMS OTP Fallback', [
            'phone' => $phoneNumber,
            'type' => $type,
            'otp' => $otpCode // Remove this in production
        ]);
        
        // For demonstration, we'll just return success
        // In production, implement actual SMS sending here
        
        return [
            'success' => true,
            'message' => 'OTP sent via SMS (WhatsApp unavailable)',
            'method' => 'sms',
            'expires_in' => '10 minutes'
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
            'code' => $otpCode,
            'phone' => $phoneNumber,
            'created_at' => now()
        ], now()->addMinutes(10));
        
        // Attempt to send OTP via system WhatsApp instance
        try {
            $sent = $this->systemWhatsApp->sendLoginOtp($phoneNumber, $otpCode);
            
            if ($sent) {
                return [
                    'success' => true,
                    'message' => 'Login OTP sent via WhatsApp',
                    'method' => 'whatsapp',
                    'expires_in' => '10 minutes',
                    'expires_at' => now()->addMinutes(10)->toISOString()
                ];
            }
        } catch (Exception $e) {
            \Log::warning('WhatsApp login OTP failed, attempting SMS fallback', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
        }
        
        // Fallback to SMS if WhatsApp fails
        return $this->sendOtpViaSms($phoneNumber, $otpCode, 'login');
    }
    
    /**
     * Verify login OTP code
     */
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
}