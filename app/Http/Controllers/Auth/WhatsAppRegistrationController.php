<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

class WhatsAppRegistrationController extends Controller
{
    protected UserRegistrationService $registrationService;
    
    public function __construct(UserRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }
    
    /**
     * Send registration OTP
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'name' => 'nullable|string|max:255'
        ]);
        
        try {
            $result = $this->registrationService->sendRegistrationOtp(
                $request->phone,
                $request->name
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'OTP sent successfully'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Verify OTP and complete registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'otp_code' => 'required|string|size:6'
        ]);
        
        try {
            $user = $this->registrationService->completeRegistration(
                $request->only(['name', 'phone', 'email', 'password']),
                $request->otp_code
            );
            
            // Auto-login the user
            Auth::login($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'phone', 'email']),
                    'token' => $user->createToken('auth')->plainTextToken ?? null
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Send password reset OTP
     */
    public function sendPasswordResetOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/'
        ]);
        
        try {
            $result = $this->registrationService->sendPasswordResetOtp($request->phone);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Password reset OTP sent'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        try {
            $this->registrationService->resetPassword(
                $request->phone,
                $request->otp_code,
                $request->password
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Resend OTP (rate limited)
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:registration,password_reset'
        ]);
        
        // Rate limiting - allow only 3 resends per 30 minutes per phone
        $rateLimitKey = "otp_resend:{$request->phone}";
        $attempts = cache()->get($rateLimitKey, 0);
        
        if ($attempts >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Too many OTP requests. Please wait 30 minutes before trying again.'
            ], 429);
        }
        
        try {
            if ($request->type === 'registration') {
                $result = $this->registrationService->sendRegistrationOtp(
                    $request->phone,
                    $request->name
                );
            } else {
                $result = $this->registrationService->sendPasswordResetOtp($request->phone);
            }
            
            // Increment rate limit counter
            cache()->put($rateLimitKey, $attempts + 1, now()->addMinutes(30));
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'OTP resent successfully',
                'remaining_attempts' => 2 - $attempts
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Get registration statistics (admin only)
     */
    public function getStats(Request $request)
    {
        // Add authorization check for admin users
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $days = $request->get('days', 30);
        $stats = $this->registrationService->getRegistrationStats($days);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Check if phone number is available
     */
    public function checkPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/'
        ]);
        
        $exists = \App\Models\User::where('phone', $request->phone)->exists();
        
        return response()->json([
            'success' => true,
            'data' => [
                'available' => !$exists,
                'exists' => $exists
            ]
        ]);
    }
}