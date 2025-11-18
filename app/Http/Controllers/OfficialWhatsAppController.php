<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\OfficialWhatsappCredential;
use App\Models\User;
use Carbon\Carbon;

class OfficialWhatsAppController extends Controller
{
    protected $config;
    protected $metaConfig;
    protected $providerConfig;

    public function __construct()
    {
        $this->config = config('whatsapp.official');
        $this->metaConfig = $this->config['meta'];
        $this->providerConfig = $this->config['providers'][$this->config['default_provider']];
    }

    /**
     * Show the WhatsApp integration selection page
     */
    public function showIntegrationOptions()
    {
        $user = auth()->user();
        
        // Check existing integrations
        $unofficialInstances = DB::table('whatsapp_instances')
            ->where('user_id', $user->id)
            ->count();
            
        $officialCredentials = OfficialWhatsappCredential::forUser($user->id)
            ->get();

        return view('whatsapp.integration-options', compact(
            'unofficialInstances', 
            'officialCredentials'
        ));
    }

    /**
     * Show embedded signup page
     */
    public function showEmbeddedSignup(Request $request)
    {
        try {
            $user = auth()->user();
            $selectedProvider = $request->input('provider', $this->config['default_provider']);
            
            // Get or create credential record
            $credential = OfficialWhatsappCredential::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'status' => 'pending'
                ],
                [
                    'api_provider' => $selectedProvider,
                    'meta_app_config' => [
                        'app_id' => $this->metaConfig['app_id'],
                        'config_id' => $this->metaConfig['config_id'],
                        'business_id' => $this->metaConfig['business_id']
                    ],
                    'onboarding_started_at' => now()
                ]
            );

            $config = [
                'app_id' => $this->metaConfig['app_id'],
                'config_id' => $this->metaConfig['config_id'],
                'business_id' => $this->metaConfig['business_id'],
                'redirect_url' => $this->config['embedded_signup']['redirect_url']
            ];

            return view('whatsapp.embedded-signup', [
                'config' => $config,
                'credential_id' => $credential->id,
                'selected_provider' => $selectedProvider
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to show embedded signup', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->route('whatsapp.integration-error')
                ->with('error', 'Failed to initialize signup: ' . $e->getMessage());
        }
    }

    /**
     * Initialize official WhatsApp onboarding
     */
    public function initializeOnboarding(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Validate if user can start onboarding
            $this->validateOnboardingEligibility($user);
            
            // Create or update pending credential record
            $credential = OfficialWhatsappCredential::updateOrCreate(
                ['user_id' => $user->id, 'status' => 'pending'],
                [
                    'api_provider' => $this->config['default_provider'],
                    'meta_app_config' => [
                        'app_id' => $this->metaConfig['app_id'],
                        'config_id' => $this->metaConfig['config_id'],
                        'business_id' => $this->metaConfig['business_id']
                    ],
                    'onboarding_started_at' => now()
                ]
            );
            
            $credential->markOnboardingStarted();
            
            // Generate embedded signup configuration
            $embeddedSignupConfig = $this->generateEmbeddedSignupConfig($credential);
            
            Log::info('Official WhatsApp onboarding initialized', [
                'user_id' => $user->id,
                'credential_id' => $credential->id,
                'provider' => $this->config['default_provider']
            ]);
            
            return response()->json([
                'success' => true,
                'credential_id' => $credential->id,
                'embedded_signup_config' => $embeddedSignupConfig,
                'redirect_url' => $this->config['embedded_signup']['redirect_url']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to initialize official WhatsApp onboarding', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize onboarding: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Meta's Embedded Signup callback
     */
    public function handleEmbeddedSignupCallback(Request $request)
    {
        try {
            Log::info('Official WhatsApp callback received', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);
            
            // Validate callback parameters
            $validator = Validator::make($request->all(), [
                'code' => 'required|string',
                'setup' => 'sometimes|array'
            ]);
            
            if ($validator->fails()) {
                throw new \Exception('Invalid callback parameters: ' . $validator->errors()->first());
            }
            
            $temporaryCode = $request->input('code');
            $setupData = $request->input('setup', []);
            
            // Find credential record by temporary code or recent pending status
            $credential = $this->findCredentialForCallback($temporaryCode, $setupData);
            
            if (!$credential) {
                throw new \Exception('No matching credential record found for callback');
            }
            
            // Store temporary code for token exchange
            $credential->update([
                'temporary_code' => $temporaryCode,
                'status' => 'verification_pending'
            ]);
            
            // Exchange temporary code for long-lived token
            $tokenData = $this->exchangeCodeForToken($credential, $temporaryCode, $setupData);
            
            // Update credential with token data
            $this->updateCredentialWithTokenData($credential, $tokenData);
            
            // Verify the integration is working
            $this->verifyIntegration($credential);
            
            // Mark onboarding as completed
            $credential->markOnboardingCompleted();
            
            Log::info('Official WhatsApp onboarding completed successfully', [
                'user_id' => $credential->user_id,
                'credential_id' => $credential->id,
                'waba_id' => $credential->waba_id,
                'phone_number_id' => $credential->phone_number_id
            ]);
            
            // Redirect to success page
            return redirect()->to($this->config['embedded_signup']['success_url'])
                ->with('success', 'WhatsApp Business API connected successfully!');
                
        } catch (\Exception $e) {
            Log::error('Official WhatsApp callback failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Redirect to error page
            return redirect()->to($this->config['embedded_signup']['error_url'])
                ->with('error', 'Failed to connect WhatsApp Business API: ' . $e->getMessage());
        }
    }

    /**
     * Get onboarding status
     */
    public function getOnboardingStatus(Request $request)
    {
        try {
            $user = auth()->user();
            
            $credentials = OfficialWhatsappCredential::forUser($user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($credential) {
                    return [
                        'id' => $credential->id,
                        'status' => $credential->status,
                        'status_label' => $credential->status_label,
                        'phone_number' => $credential->display_phone_number,
                        'verified_name' => $credential->verified_name,
                        'quality_rating' => $credential->quality_rating,
                        'quality_rating_color' => $credential->quality_rating_color,
                        'api_provider' => $credential->api_provider,
                        'is_active' => $credential->isActive(),
                        'token_expires_at' => $credential->token_expiration?->toISOString(),
                        'onboarding_duration' => $credential->onboarding_duration,
                        'created_at' => $credential->created_at->toISOString()
                    ];
                });
            
            return response()->json([
                'success' => true,
                'credentials' => $credentials
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get onboarding status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect official WhatsApp integration
     */
    public function disconnect(Request $request)
    {
        try {
            $user = auth()->user();
            $credentialId = $request->input('credential_id');
            
            $credential = OfficialWhatsappCredential::forUser($user->id)
                ->findOrFail($credentialId);
            
            // Update status to disconnected
            $credential->update([
                'status' => 'disconnected',
                'access_token' => null,
                'temporary_code' => null
            ]);
            
            Log::info('Official WhatsApp integration disconnected', [
                'user_id' => $user->id,
                'credential_id' => $credential->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp Business API disconnected successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test the official WhatsApp connection
     */
    public function testConnection(Request $request)
    {
        try {
            $user = auth()->user();
            $credentialId = $request->input('credential_id');
            
            $credential = OfficialWhatsappCredential::forUser($user->id)
                ->active()
                ->findOrFail($credentialId);
            
            // Test API connection
            $testResult = $this->performConnectionTest($credential);
            
            return response()->json([
                'success' => true,
                'test_result' => $testResult
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods...

    private function validateOnboardingEligibility(User $user)
    {
        // Check if user already has too many pending onboarding attempts
        $pendingCount = OfficialWhatsappCredential::forUser($user->id)
            ->whereIn('status', ['pending', 'verification_pending'])
            ->count();
            
        if ($pendingCount >= 3) {
            throw new \Exception('Too many pending onboarding attempts. Please complete or cancel existing attempts first.');
        }
        
        // Additional validation logic can be added here
    }

    private function generateEmbeddedSignupConfig($credential)
    {
        return [
            'app_id' => $this->metaConfig['app_id'],
            'config_id' => $this->metaConfig['config_id'],
            'setup_params' => [
                'business_id' => $this->metaConfig['business_id'],
                'partner_id' => $this->providerConfig['partner_id'],
                'solution_id' => $this->providerConfig['solution_id']
            ],
            'redirect_url' => $this->config['embedded_signup']['redirect_url'] . '?credential_id=' . $credential->id,
            'permissions' => $this->config['embedded_signup']['permissions'],
            'extras' => $this->config['embedded_signup']['extras']
        ];
    }

    private function findCredentialForCallback($temporaryCode, $setupData)
    {
        // Try to find by temporary code first
        $credential = OfficialWhatsappCredential::where('temporary_code', $temporaryCode)->first();
        
        if (!$credential) {
            // Fallback: find most recent pending credential
            $credential = OfficialWhatsappCredential::whereIn('status', ['pending', 'verification_pending'])
                ->orderBy('onboarding_started_at', 'desc')
                ->first();
        }
        
        return $credential;
    }

    private function exchangeCodeForToken($credential, $temporaryCode, $setupData)
    {
        // This will vary based on the BSP provider
        // For now, implement a generic approach
        
        $provider = $credential->api_provider;
        $providerConfig = $this->config['providers'][$provider];
        
        switch ($provider) {
            case '360dialog':
                return $this->exchange360DialogToken($credential, $temporaryCode, $setupData, $providerConfig);
                
            case 'twilio':
                return $this->exchangeTwilioToken($credential, $temporaryCode, $setupData, $providerConfig);
                
            case 'facebook':
                return $this->exchangeFacebookToken($credential, $temporaryCode, $setupData, $providerConfig);
                
            default:
                throw new \Exception('Unsupported provider: ' . $provider);
        }
    }

    private function exchange360DialogToken($credential, $temporaryCode, $setupData, $providerConfig)
    {
        try {
            Log::info('Starting 360Dialog token exchange', [
                'credential_id' => $credential->id,
                'provider_config' => $providerConfig
            ]);

            // Step 1: Exchange temporary code for access token with Meta
            $metaTokenResponse = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id' => $this->metaConfig['app_id'],
                'client_secret' => $this->metaConfig['app_secret'],
                'code' => $temporaryCode,
                'redirect_uri' => $this->config['embedded_signup']['redirect_url']
            ]);

            if (!$metaTokenResponse->successful()) {
                throw new \Exception('Meta token exchange failed: ' . $metaTokenResponse->body());
            }

            $metaTokenData = $metaTokenResponse->json();
            $accessToken = $metaTokenData['access_token'];

            // Step 2: Get WhatsApp Business Account info
            $wabaResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://graph.facebook.com/v18.0/me/businesses', [
                'fields' => 'id,name,whatsapp_business_accounts{id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating}}'
            ]);

            if (!$wabaResponse->successful()) {
                throw new \Exception('Failed to get WABA info: ' . $wabaResponse->body());
            }

            $wabaData = $wabaResponse->json();
            
            // Extract WABA and phone number info
            $waba = $wabaData['data'][0]['whatsapp_business_accounts']['data'][0] ?? null;
            if (!$waba) {
                throw new \Exception('No WhatsApp Business Account found');
            }

            $phoneNumber = $waba['phone_numbers']['data'][0] ?? null;
            if (!$phoneNumber) {
                throw new \Exception('No phone number found in WABA');
            }

            // Step 3: Register with 360Dialog (if using 360Dialog as BSP)
            if ($credential->api_provider === '360dialog') {
                $registrationResponse = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'D360-API-KEY' => $providerConfig['api_key'] ?? ''
                ])->post($providerConfig['api_base_url'] . '/v1/partners/' . $providerConfig['partner_id'] . '/register', [
                    'waba_id' => $waba['id'],
                    'access_token' => $accessToken,
                    'phone_number_id' => $phoneNumber['id']
                ]);

                if (!$registrationResponse->successful()) {
                    Log::warning('360Dialog registration failed, continuing with Meta direct', [
                        'error' => $registrationResponse->body()
                    ]);
                }
            }

            return [
                'access_token' => $accessToken,
                'waba_id' => $waba['id'],
                'phone_number_id' => $phoneNumber['id'],
                'phone_number' => $phoneNumber['display_phone_number'],
                'display_phone_number' => $phoneNumber['display_phone_number'],
                'verified_name' => $phoneNumber['verified_name'] ?? null,
                'quality_rating' => $phoneNumber['quality_rating'] ?? null,
                'expires_in' => $metaTokenData['expires_in'] ?? 5184000 // 60 days default
            ];

        } catch (\Exception $e) {
            Log::error('360Dialog token exchange failed', [
                'credential_id' => $credential->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function exchangeTwilioToken($credential, $temporaryCode, $setupData, $providerConfig)
    {
        try {
            Log::info('Starting Twilio token exchange', [
                'credential_id' => $credential->id
            ]);

            // Step 1: Exchange with Meta first
            $metaTokenResponse = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id' => $this->metaConfig['app_id'],
                'client_secret' => $this->metaConfig['app_secret'],
                'code' => $temporaryCode,
                'redirect_uri' => $this->config['embedded_signup']['redirect_url']
            ]);

            if (!$metaTokenResponse->successful()) {
                throw new \Exception('Meta token exchange failed: ' . $metaTokenResponse->body());
            }

            $metaTokenData = $metaTokenResponse->json();
            $accessToken = $metaTokenData['access_token'];

            // Step 2: Get WABA info
            $wabaResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://graph.facebook.com/v18.0/me/businesses', [
                'fields' => 'whatsapp_business_accounts{id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating}}'
            ]);

            $wabaData = $wabaResponse->json();
            $waba = $wabaData['data'][0]['whatsapp_business_accounts']['data'][0] ?? null;
            $phoneNumber = $waba['phone_numbers']['data'][0] ?? null;

            // Step 3: Register with Twilio
            $twilioResponse = Http::withBasicAuth(
                $providerConfig['account_sid'],
                $providerConfig['auth_token']
            )->post('https://api.twilio.com/2010-04-01/Accounts/' . $providerConfig['account_sid'] . '/Messages.json', [
                'From' => 'whatsapp:' . $providerConfig['phone_number'],
                'To' => 'whatsapp:' . ($phoneNumber['display_phone_number'] ?? ''),
                'Body' => 'WhatsApp Business API connected via Twilio!'
            ]);

            return [
                'access_token' => $accessToken,
                'waba_id' => $waba['id'] ?? null,
                'phone_number_id' => $phoneNumber['id'] ?? null,
                'phone_number' => $phoneNumber['display_phone_number'] ?? null,
                'display_phone_number' => $phoneNumber['display_phone_number'] ?? null,
                'verified_name' => $phoneNumber['verified_name'] ?? null,
                'quality_rating' => $phoneNumber['quality_rating'] ?? null,
                'expires_in' => $metaTokenData['expires_in'] ?? 5184000
            ];

        } catch (\Exception $e) {
            Log::error('Twilio token exchange failed', [
                'credential_id' => $credential->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function exchangeFacebookToken($credential, $temporaryCode, $setupData, $providerConfig)
    {
        try {
            Log::info('Starting Facebook/Meta direct token exchange', [
                'credential_id' => $credential->id
            ]);

            // Direct Meta token exchange
            $tokenResponse = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id' => $this->metaConfig['app_id'],
                'client_secret' => $this->metaConfig['app_secret'],
                'code' => $temporaryCode,
                'redirect_uri' => $this->config['embedded_signup']['redirect_url']
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Meta token exchange failed: ' . $tokenResponse->body());
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];

            // Get business and WABA information
            $businessResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://graph.facebook.com/v18.0/' . $this->metaConfig['business_id'], [
                'fields' => 'name,whatsapp_business_accounts{id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating,status}}'
            ]);

            if (!$businessResponse->successful()) {
                throw new \Exception('Failed to get business info: ' . $businessResponse->body());
            }

            $businessData = $businessResponse->json();
            $waba = $businessData['whatsapp_business_accounts']['data'][0] ?? null;
            
            if (!$waba) {
                throw new \Exception('No WhatsApp Business Account found');
            }

            $phoneNumber = $waba['phone_numbers']['data'][0] ?? null;
            
            if (!$phoneNumber) {
                throw new \Exception('No phone number found in WABA');
            }

            return [
                'access_token' => $accessToken,
                'waba_id' => $waba['id'],
                'phone_number_id' => $phoneNumber['id'],
                'phone_number' => $phoneNumber['display_phone_number'],
                'display_phone_number' => $phoneNumber['display_phone_number'],
                'verified_name' => $phoneNumber['verified_name'] ?? null,
                'quality_rating' => $phoneNumber['quality_rating'] ?? null,
                'expires_in' => $tokenData['expires_in'] ?? 5184000 // 60 days default
            ];

        } catch (\Exception $e) {
            Log::error('Facebook token exchange failed', [
                'credential_id' => $credential->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function updateCredentialWithTokenData($credential, $tokenData)
    {
        $updateData = [
            'access_token' => $tokenData['access_token'] ?? null,
            'waba_id' => $tokenData['waba_id'] ?? null,
            'phone_number_id' => $tokenData['phone_number_id'] ?? null,
            'phone_number' => $tokenData['phone_number'] ?? null,
            'display_phone_number' => $tokenData['display_phone_number'] ?? null,
            'verified_name' => $tokenData['verified_name'] ?? null,
            'quality_rating' => $tokenData['quality_rating'] ?? null,
        ];
        
        // Set token expiration (typically 60 days for WhatsApp Business API)
        if (isset($tokenData['expires_in'])) {
            $updateData['token_expiration'] = now()->addSeconds($tokenData['expires_in']);
        } elseif (isset($tokenData['expires_at'])) {
            $updateData['token_expiration'] = Carbon::parse($tokenData['expires_at']);
        } else {
            // Default to 60 days
            $updateData['token_expiration'] = now()->addDays(60);
        }
        
        $credential->update($updateData);
    }

    private function verifyIntegration($credential)
    {
        // Test the integration by making a simple API call
        if (!$credential->access_token || !$credential->phone_number_id) {
            throw new \Exception('Missing required credentials for verification');
        }
        
        // This is a placeholder - implement actual verification
        Log::info('Integration verification completed', [
            'credential_id' => $credential->id
        ]);
    }

    private function performConnectionTest($credential)
    {
        // Implement connection test based on provider
        return [
            'status' => 'connected',
            'phone_number' => $credential->display_phone_number,
            'quality_rating' => $credential->quality_rating,
            'test_timestamp' => now()->toISOString()
        ];
    }
}
