<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Services\UserRegistrationService;
use Exception;

class AuthController extends Controller
{
    protected UserRegistrationService $registrationService;
    
    public function __construct(UserRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Request access information for CRM API authentication
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestAccess(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // Normalize phone number - remove spaces, dashes, parentheses
        $inputPhone = preg_replace('/[\s\-\(\)]/', '', $request->phone);
        
        // Try to find user with exact match first
        $user = User::where('phone', $inputPhone)->first();

        // If not found and phone doesn't start with +, try adding country code
        if (!$user && !str_starts_with($inputPhone, '+')) {
            $possibleFormats = [
                '+255' . ltrim($inputPhone, '0'), // Tanzania format
                '+' . $inputPhone,
            ];
            
            foreach ($possibleFormats as $format) {
                $user = User::where('phone', $format)->first();
                if ($user) break;
            }
        }

        // If phone starts with +, also try local format
        if (!$user && str_starts_with($inputPhone, '+')) {
            $localPhone = preg_replace('/^\+255/', '0', $inputPhone); // Convert +255 to 0
            $user = User::where('phone', $localPhone)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this phone number. Please register first.',
                'error_code' => 'USER_NOT_FOUND'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Account found. Use your phone number and user UUID for authentication.',
            'data' => [
                'phone' => $user->phone, // Return the phone as stored in database
                'user_exists' => true,
                'instruction' => 'Find your User UUID in the Settings page of your dashboard'
            ]
        ]);
    }

    /**
     * Authenticate user using phone number and UUID for CRM API access
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateUser(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'user_uuid' => 'required|string|uuid',
        ]);

        // Normalize phone number - remove spaces, dashes, parentheses
        $inputPhone = preg_replace('/[\s\-\(\)]/', '', $request->phone);
        
        // Try to find user with exact match first
        $user = User::where('phone', $inputPhone)
                   ->where('uuid', $request->user_uuid)
                   ->first();

        // If not found and phone doesn't start with +, try adding country code
        if (!$user && !str_starts_with($inputPhone, '+')) {
            // Try common formats - you may need to adjust based on your data
            $possibleFormats = [
                '+255' . ltrim($inputPhone, '0'), // Tanzania format
                '+' . $inputPhone,
                $inputPhone,
            ];
            
            foreach ($possibleFormats as $format) {
                $user = User::where('phone', $format)
                           ->where('uuid', $request->user_uuid)
                           ->first();
                if ($user) break;
            }
        }

        // If phone starts with +, also try local format
        if (!$user && str_starts_with($inputPhone, '+')) {
            $localPhone = preg_replace('/^\+255/', '0', $inputPhone); // Convert +255 to 0
            $user = User::where('phone', $localPhone)
                       ->where('uuid', $request->user_uuid)
                       ->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Phone number and User UUID do not match.',
                'error_code' => 'INVALID_CREDENTIALS'
            ], 401);
        }

        try {
            // Create new token for CRM API access
            $token = $user->createToken('crm-api-access', [
                'crm:import:contacts',
                'crm:import:conversations', 
                'crm:export:data',
                'api:access'
            ])->plainTextToken;

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'uuid' => $user->uuid
                ],
                'message' => 'Authentication successful',
                'permissions' => [
                    'crm:import:contacts',
                    'crm:import:conversations',
                    'crm:export:data'
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage(),
                'error_code' => 'AUTH_ERROR'
            ], 500);
        }
    }

    /**
     * Logout API endpoint - revoke current token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current authenticated user details
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'permissions' => $request->user()->currentAccessToken()->abilities ?? []
        ]);
    }

    /**
     * Refresh token - create new token and revoke old one
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();
        
        // Create new token
        $token = $user->createToken('api-access', $currentToken->abilities ?? ['api:access'])->plainTextToken;
        
        // Revoke old token
        $currentToken->delete();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Token refreshed successfully'
        ]);
    }
}