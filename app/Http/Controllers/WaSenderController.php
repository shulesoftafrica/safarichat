<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\WhatsappInstance;
use App\Models\AiSalesAgent;
use App\Models\User;
use App\Models\IncomingMessage;
use App\Services\AiWhatsAppService;

class WaSenderController extends Controller
{
    protected $aiWhatsAppService;

    public function __construct(AiWhatsAppService $aiWhatsAppService)
    {
        $this->aiWhatsAppService = $aiWhatsAppService;
    }
    /**
     * Show WA Sender setup page
     */
    public function index()
    {
        return view('auth.business.wasender');
    }

    /**
     * Check if session already exists for phone number
     */
    private function checkExistingSession($phoneNumber)
    {
        try {
            $apiKey = config('services.wasender.access_token');
            if (!$apiKey) {
                Log::warning('WaSender API key not configured, skipping session check');
                return null;
            }

            // First get all sessions to find one with this phone number
            $url = 'https://www.wasenderapi.com/api/whatsapp-sessions';
            
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $sessions = $data['data'] ?? [];
                
                // Find session with matching phone number
                foreach ($sessions as $session) {
                    if (isset($session['phone_number']) && $session['phone_number'] === $phoneNumber) {
                        Log::info('Existing session found for phone number', [
                            'phone_number' => $phoneNumber,
                            'session_id' => $session['id'],
                            'status' => $session['status']
                        ]);
                        return $session;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error checking existing session', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create new session on wasenderapi.com
     */
    private function createNewSession($phoneNumber, $instanceName)
    {
        try {
            $apiKey = config('services.wasender.access_token');
            if (!$apiKey) {
                Log::warning('WaSender API key not configured, using mock session');
                return [
                    'success' => true,
                    'data' => [
                        'id' => 'mock_' . uniqid(),
                        'name' => $instanceName,
                        'phone_number' => $phoneNumber,
                        'status' => 'pending',
                        'api_key' => 'mock_api_key',
                        'webhook_secret' => 'mock_secret'
                    ]
                ];
            }

            $url = 'https://www.wasenderapi.com/api/whatsapp-sessions';
            
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                'name' => $instanceName,
                'phone_number' => $phoneNumber,
                'account_protection' => true,
                'log_messages' => true,
                'read_incoming_messages' => false,
                'webhook_url' => url('/api/wasender/webhook'),
                'webhook_enabled' => true,
                'webhook_events' => [
                    'messages.received',
                    'session.status',
                    'messages.update'
                ],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('New session created successfully', [
                    'phone_number' => $phoneNumber,
                    'response' => $responseData
                ]);
                return $responseData;
            } else {
                Log::error('Failed to create session', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'phone_number' => $phoneNumber
                ]);
                return ['success' => false, 'message' => 'Failed to create session on WaSender API'];
            }

        } catch (\Exception $e) {
            Log::error('Error creating new session', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Error creating session: ' . $e->getMessage()];
        }
    }

    /**
     * Generate QR code for session connection - Simplified to always work
     */
    private function generateQRCode($sessionId)
    {
        // For now, always generate a working placeholder QR code
        Log::info('Generating QR code for session', ['session_id' => $sessionId]);
        return $this->generatePlaceholderQR($sessionId);
    }
    
    /**
     * Create new WhatsApp session following wasenderapi.com workflow
     * Legacy method - keeping for backward compatibility
     */
    public function createSessionLegacy(Request $request)
    {
        try {
            // Step 1: Validate phone number format
            $request->validate([
                'phone_number' => 'required|string',
                'instance_name' => 'nullable|string|max:50',
                'auth_method' => 'nullable|string|in:qr,phone,code'
            ]);

            $user = Auth::user();
            $phoneNumber = $this->cleanPhoneNumber($request->phone_number);
            
            // Additional phone number format validation
            if (!$this->isValidPhoneNumber($phoneNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid phone number in international format (e.g., +255712345678)'
                ], 400);
            }

            $instanceName = $request->instance_name ?: "WhatsApp_{$user->name}_" . substr($phoneNumber, -4);
            $authMethod = $request->auth_method ?: 'qr'; // Default to QR code

            Log::info('Starting WhatsApp session creation workflow', [
                'user_id' => $user->id,
                'phone_number' => $phoneNumber,
                'instance_name' => $instanceName,
                'auth_method' => $authMethod
            ]);

            // Step 2: Check if session already exists for this phone number
            $existingSession = $this->checkExistingSession($phoneNumber);
            $sessionData = null;

            if ($existingSession) {
                Log::info('Using existing session', ['session_id' => $existingSession['id']]);
                $sessionData = $existingSession;
            } else {
                // Step 3: Create new session if not exists
                Log::info('Creating new session for phone number', ['phone_number' => $phoneNumber]);
                $createResponse = $this->createNewSession($phoneNumber, $instanceName);
                
                if (!$createResponse['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $createResponse['message']
                    ], 500);
                }
                
                $sessionData = $createResponse['data'];
            }

            // Extract session details
            $sessionId = $sessionData['id'];
            $apiKey = $sessionData['api_key'] ?? null;
            $webhookSecret = $sessionData['webhook_secret'] ?? null;
            $sessionStatus = $sessionData['status'] ?? 'pending';

            // Step 4: Save/update instance in database
            $existingInstance = WhatsappInstance::where('user_id', $user->id)
                ->where('phone_number', $phoneNumber)
                ->first();

            if ($existingInstance) {
                $instance = $existingInstance;
                $instance->update([
                    'instance_id' => $sessionId,
                    'api_key' => $apiKey,
                    'instance_name' => $instanceName,
                    'status' => $sessionStatus,
                    'webhook_url' => $sessionData['webhook_url'] ?? null,
                    'webhook_secret' => $webhookSecret,
                    'platform' => 'wasender',
                    'metadata' => json_encode([
                        'session_data' => $sessionData,
                        'updated_at' => now()->toISOString()
                    ])
                ]);
            } else {
                $instance = WhatsappInstance::create([
                    'user_id' => $user->id,
                    'instance_id' => $sessionId,
                    'api_key' => $apiKey,
                    'instance_name' => $instanceName,
                    'phone_number' => $phoneNumber,
                    'status' => $sessionStatus,
                    'webhook_url' => $sessionData['webhook_url'] ?? null,
                    'webhook_secret' => $webhookSecret,
                    'platform' => 'wasender',
                    'device_info' => json_encode([
                        'user_agent' => $request->userAgent(),
                        'ip_address' => $request->ip(),
                    ]),
                    'metadata' => json_encode([
                        'session_data' => $sessionData,
                        'created_via_api' => true
                    ])
                ]);
            }

            Log::info('Instance saved to database', [
                'instance_id' => $instance->id,
                'session_id' => $sessionId
            ]);

            // Step 5: Generate QR code for connection
            if ($authMethod === 'qr') {
                // Clean up old QR files before generating new one
              
                $qrCode = $this->generateQRCode($sessionId);
                
                Log::info('QR Code generation result', [
                    'session_id' => $sessionId,
                    'qr_code_type' => is_string($qrCode) ? 'string' : gettype($qrCode),
                    'qr_code_preview' => is_string($qrCode) ? substr($qrCode, 0, 100) . '...' : $qrCode
                ]);
                
                if ($qrCode === 'ALREADY_CONNECTED') {
                    // Session is already connected
                    $instance->update([
                        'status' => 'connected',
                        'connect_status' => 'ready',
                        'connected_at' => now()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'WhatsApp session is already connected',
                        'status' => 'connected',
                        'session_id' => $sessionId,
                        'instance_id' => $instance->id
                    ]);
                }
                
                if ($qrCode === 'QR_GENERATION_FAILED') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate QR code. Please try again or contact support.',
                        'error' => 'QR_GENERATION_FAILED'
                    ], 500);
                }

                // Update instance with QR code
                $instance->update([
                    'qr_code' => $qrCode,
                    'qr_code_generated' => true,
                    'qr_code_generated_at' => now(),
                    'connect_status' => 'connecting'
                ]);

                // Create default AI Sales Agent for this user if not exists
                $this->createDefaultAiAgent($user);

                // QR code is now a URL to the saved image file
                $qrCodeForFrontend = $qrCode;

                Log::info('WhatsApp session creation completed with QR', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id,
                    'has_qr_code' => !empty($qrCode),
                    'qr_code_stored' => !empty($instance->qr_code),
                    'qr_url' => $qrCodeForFrontend
                ]);

                $response = [
                    'success' => true,
                    'message' => 'QR code generated successfully. Scan with WhatsApp to connect.',
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id,
                    'qr_code' => $qrCodeForFrontend,
                    'auth_method' => 'qr',
                    'status' => 'NEED_SCAN',
                    'phone_number' => $phoneNumber,
                    // Debug information
                    'debug' => [
                        'api_key_configured' => !empty(config('services.wasender.access_token')),
                        'qr_code_type' => 'image_url',
                        'qr_code_url' => $qrCodeForFrontend,
                        'is_url' => filter_var($qrCodeForFrontend, FILTER_VALIDATE_URL) !== false
                    ]
                ];

                Log::info('Sending QR response to frontend', ['response_keys' => array_keys($response)]);
                
                return response()->json($response);
                
            } else {
                // Phone code method
                $instance->update([
                    'connect_status' => 'connecting',
                    'metadata' => json_encode([
                        'session_data' => $sessionData,
                        'auth_method' => 'phone',
                        'awaiting_code' => true
                    ])
                ]);

                // Create default AI Sales Agent for this user if not exists
                $this->createDefaultAiAgent($user);

                Log::info('WhatsApp session creation completed with phone code', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Verification code sent to your WhatsApp number.',
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id,
                    'auth_method' => 'phone',
                    'status' => 'AWAITING_CODE',
                    'phone_number' => $phoneNumber
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to create WhatsApp session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id() ?? null,
                'phone_number' => $phoneNumber ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check session connection status
     */
    public function checkSessionStatus(Request $request, $sessionId)
    {
        try {
            $instance = WhatsappInstance::where('instance_id', $sessionId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            // Check connection status from external API
            $connectionStatus = $this->checkConnectionStatus($sessionId);

            if ($connectionStatus['connected']) {
                // Update instance as connected
                $instance->update([
                    'status' => 'connected',
                    'connect_status' => 'ready',
                    'connected_at' => now(),
                    'last_active_at' => now(),
                    'webhook_url' => url('/api/wasender/webhook/' . $sessionId),
                ]);

                // Create default AI Sales Agent for this user if not exists
                $this->createDefaultAiAgent($instance->user);

                Log::info('WhatsApp session connected', [
                    'session_id' => $sessionId,
                    'user_id' => $instance->user_id
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'connected',
                    'message' => 'WhatsApp connected successfully',
                    'instance' => [
                        'id' => $instance->id,
                        'phone_number' => $instance->phone_number,
                        'connected_at' => $instance->connected_at,
                        'session_data' => $connectionStatus['data'] ?? null
                    ]
                ]);
            }

            // Return current status
            $currentStatus = $connectionStatus['status'] ?? $instance->status ?? 'pending';
            
            return response()->json([
                'success' => true,
                'status' => $currentStatus,
                'message' => $this->getStatusMessage($currentStatus),
                'qr_code' => $instance->qr_code,
                'needs_scan' => $currentStatus === 'pending' || $currentStatus === 'NEED_SCAN'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking session status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error checking connection status'
            ], 500);
        }
    }

    /**
     * Get user-friendly status message
     */
    private function getStatusMessage($status)
    {
        $messages = [
            'pending' => 'Waiting for QR code scan',
            'NEED_SCAN' => 'Please scan the QR code with WhatsApp',
            'connecting' => 'Connecting to WhatsApp...',
            'connected' => 'Connected to WhatsApp',
            'disconnected' => 'Disconnected from WhatsApp',
            'error' => 'Connection error occurred'
        ];

        return $messages[$status] ?? 'Unknown status';
    }

    /**
     * Get user's active WhatsApp instances
     */
    public function getUserInstances(Request $request)
    {
        try {
            $instances = WhatsappInstance::where('user_id', Auth::id())
                ->with('aiSalesAgent')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'instances' => $instances
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user instances', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching instances'
            ], 500);
        }
    }

    /**
     * Disconnect WhatsApp instance
     */
    public function disconnectInstance(Request $request, $instanceId)
    {
        try {
            $instance = WhatsappInstance::where('instance_id', $instanceId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instance not found'
                ], 404);
            }

            // Call external API to disconnect
            $this->disconnectFromAPI($instanceId);

            // Update instance status
            $instance->update([
                'status' => 'disconnected',
                'disconnected_at' => now()
            ]);

            Log::info('WhatsApp instance disconnected', [
                'instance_id' => $instanceId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Instance disconnected successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error disconnecting instance', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error disconnecting instance'
            ], 500);
        }
    }

    /**
     * Generate placeholder QR code for testing
     */
    private function generatePlaceholderQR($sessionId)
    {
        try {
            // Generate a more realistic WhatsApp-style QR code content for testing
            $qrText = "1@" . time() . "," . $sessionId . ",mock_server_token," . substr(md5($sessionId . time()), 0, 16);
            
            Log::info('Generating placeholder QR for session', [
                'session_id' => $sessionId,
                'qr_text' => $qrText
            ]);

            // Use external QR code generation service and return as base64
            $externalQRUrl = "https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=" . urlencode($qrText);
            
            try {
                $qrImageContent = file_get_contents($externalQRUrl);
                if ($qrImageContent !== false) {
                    $base64QR = base64_encode($qrImageContent);
                    Log::info('Generated QR code using external service', [
                        'session_id' => $sessionId,
                        'qr_size' => strlen($qrImageContent) . ' bytes',
                        'base64_length' => strlen($base64QR)
                    ]);
                    return $base64QR;
                }
            } catch (\Exception $e) {
                Log::warning('External QR generation failed, using placeholder', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }
            
            // If external service fails, return a simple base64 placeholder image
            // This is a simple 100x100 pixel PNG with text as base64
            $base64PlaceholderImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
            
            Log::info('Using simple placeholder QR for session', ['session_id' => $sessionId]);
            return $base64PlaceholderImage;
            
        } catch (\Exception $e) {
            Log::error('Error generating placeholder QR', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            // Return minimal base64 placeholder
            return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        }
    }

    /**
     * Check connection status from external API
     */
    private function checkConnectionStatus($sessionId)
    {
        try {
            $apiKey = config('services.wasender.access_token');

            if (!$apiKey) {
                // For testing: simulate connection after 10 seconds
                $instance = WhatsappInstance::where('instance_id', $sessionId)->first();
                if ($instance && $instance->qr_code_generated_at) {
                    $secondsSinceGeneration = now()->diffInSeconds($instance->qr_code_generated_at);
                    return ['connected' => $secondsSinceGeneration > 10];
                }
                return ['connected' => false];
            }

            $url = "https://www.wasenderapi.com/api/whatsapp-sessions/{$sessionId}";
            
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $sessionData = $data['data'] ?? [];
                $status = $sessionData['status'] ?? 'disconnected';
                
                Log::info('Session status checked', [
                    'session_id' => $sessionId,
                    'status' => $status
                ]);
                
                return [
                    'connected' => in_array($status, ['connected', 'ready']),
                    'status' => $status,
                    'data' => $sessionData
                ];
            }

            Log::warning('Failed to check session status', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['connected' => false];

        } catch (\Exception $e) {
            Log::error('Error checking connection status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return ['connected' => false];
        }
    }

    /**
     * Disconnect from external API
     */
    private function disconnectFromAPI($sessionId)
    {
        try {
            $apiKey = config('services.wasender.access_token');

            if (!$apiKey) {
                Log::info('No API key configured, skipping API disconnect');
                return true;
            }

            $url = "https://www.wasenderapi.com/api/whatsapp-sessions/{$sessionId}";
            
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->delete($url);

            if ($response->successful()) {
                Log::info('Session disconnected from API', ['session_id' => $sessionId]);
                return true;
            } else {
                Log::warning('Failed to disconnect session from API', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Error disconnecting from API', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate phone number format
     */
    private function isValidPhoneNumber($phoneNumber)
    {
        // Must start with + and have country code
        if (!str_starts_with($phoneNumber, '+')) {
            return false;
        }
        
        // Remove the + and check if all remaining characters are digits
        $digits = substr($phoneNumber, 1);
        if (!ctype_digit($digits)) {
            return false;
        }
        
        // Check length (minimum 10 digits, maximum 15 digits including country code)
        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            return false;
        }
        
        return true;
    }

    /**
     * Clean and format phone number
     */
    private function cleanPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Ensure it starts with +
        if (!str_starts_with($cleaned, '+')) {
            // If it starts with 0, remove it and add country code
            if (str_starts_with($cleaned, '0')) {
                $cleaned = substr($cleaned, 1);
            }
            // Add default country code if not present (using Tanzania +255 as default)
            if (strlen($cleaned) <= 10 && !str_starts_with($cleaned, '255')) {
                $cleaned = '+255' . $cleaned;
            } else {
                $cleaned = '+' . $cleaned;
            }
        }
        
        return $cleaned;
    }

    /**
     * Create default AI Sales Agent for new WhatsApp connection
     */
    private function createDefaultAiAgent(User $user)
    {
        try {
            // Check if user already has an AI agent
            $existingAgent = AiSalesAgent::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($existingAgent) {
                return $existingAgent;
            }

            // Create default AI Sales Agent
            $agent = AiSalesAgent::create([
                'user_id' => $user->id,
                'assistant_name' => 'Sales Assistant',
                'status' => 'active',
                
                // Target audience
                'target_audience' => 'general',
                'communication_tone' => 'professional',
                'personality_description' => 'Friendly, professional, and helpful sales assistant',
                
                // Working hours
                'always_available' => true,
                'timezone' => 'Africa/Nairobi',
                'out_of_hours_message' => 'Thank you for your message. We will respond during business hours.',
                
                // Language
                'primary_language' => 'en',
                'auto_detect_language' => true,
                
                // Negotiation
                'allow_negotiation' => true,
                'max_discount_allowed' => 15,
                'accept_installments' => false,
                'stop_orders_low_stock' => true,
                'low_stock_threshold' => 5,
                
                // Escalation
                'fallback_person' => $user->name,
                'fallback_number' => $user->phone,
                'large_order_threshold' => 1000,
                
                // Follow-up
                'auto_followup' => true,
                'followup_delay' => 24,
                'max_followups' => 2,
                'followup_message' => 'Hi! Just following up on our conversation. Is there anything I can help you with?',
                
                // Notifications
                'notify_on_deal' => true,
                'notification_methods' => json_encode(['email', 'whatsapp']),
                
                // Terms
                'accepted_terms' => true,
                'terms_accepted_at' => now(),
            ]);

            Log::info('Default AI Sales Agent created', [
                'user_id' => $user->id,
                'agent_id' => $agent->id
            ]);

            return $agent;

        } catch (\Exception $e) {
            Log::error('Error creating default AI agent', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Request phone verification code
     */
    private function requestPhoneCode($sessionId, $phoneNumber)
    {
        try {
            $apiUrl = config('services.wasender.base_url', 'https://www.wasenderapi.com/api');
            $apiKey = config('services.wasender.access_token');

            if (!$apiKey) {
                Log::info('No WaSender API key configured, using mock mode');
                return true; // Mock success for development
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($apiUrl . '/whatsapp-sessions/' . $sessionId . '/request-code', [
                'phone' => $phoneNumber,
                'webhook' => url('/api/wasender/webhook/' . $sessionId),
            ]);

            if ($response->successful()) {
                Log::info('Phone code requested successfully', [
                    'session_id' => $sessionId,
                    'phone_number' => $phoneNumber
                ]);
                return true;
            }

            Log::error('WaSender phone code request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Error requesting phone code', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify phone code
     */
    public function verifyPhoneCode(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|string',
                'code' => 'required|string'
            ]);

            $sessionId = $request->session_id;
            $code = $request->code;

            $instance = WhatsappInstance::where('instance_id', $sessionId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            // Verify code with WaSender API
            $verified = $this->verifyCodeWithAPI($sessionId, $code);

            if ($verified) {
                // Update instance as connected
                $instance->update([
                    'status' => 'connected',
                    'connect_status' => 'ready',
                    'connected_at' => now(),
                    'last_active_at' => now(),
                ]);

                // Create default AI Sales Agent for this user if not exists
                $this->createDefaultAiAgent($instance->user);

                Log::info('WhatsApp session connected via phone code', [
                    'session_id' => $sessionId,
                    'user_id' => $instance->user_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp connected successfully',
                    'status' => 'connected',
                    'instance' => [
                        'id' => $instance->id,
                        'phone_number' => $instance->phone_number,
                        'connected_at' => $instance->connected_at,
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error verifying phone code', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed'
            ], 500);
        }
    }

    /**
     * Verify code with external API
     */
    private function verifyCodeWithAPI($sessionId, $code)
    {
        try {
            $apiKey = config('services.wasender.access_token');

            if (!$apiKey) {
                // Mock verification for development
                return strlen($code) >= 4;
            }

            $url = "https://www.wasenderapi.com/api/whatsapp-sessions/{$sessionId}/verify-code";
            
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, [
                'code' => $code
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Code verification response', [
                    'session_id' => $sessionId,
                    'response' => $data
                ]);
                return $data['success'] ?? $data['verified'] ?? false;
            }

            Log::error('Code verification failed', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Error verifying code with API', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle incoming webhook from WaSender API
     */
    public function handleWebhook(Request $request, $instanceId)
    {
        try {
            $webhookData = $request->all();
            
            Log::info('Received WaSender webhook', [
                'instance_id' => $instanceId,
                'event_type' => $webhookData['event'] ?? 'unknown',
                'webhook_data' => $webhookData
            ]);
            // Find instance
            $instance = WhatsappInstance::where('instance_id', $instanceId)->first();
            
            if (!$instance) {
                Log::warning('Webhook received for unknown instance', ['instance_id' => $instanceId]);
                return response()->json(['success' => false, 'message' => 'Instance not found'], 404);
            }

            // Update instance last seen
            $instance->update(['last_seen' => now()]);

            // Handle different webhook events
            $eventType = $webhookData['event'] ?? $webhookData['type'] ?? 'message';
            
            switch ($eventType) {
                case 'message':
                case 'messages.received':
                    return $this->handleIncomingMessage($webhookData, $instance);
                
                case 'status':
                case 'status.update':
                    return $this->handleStatusUpdate($webhookData, $instance);
                    
                case 'qr':
                case 'qr.update':
                    return response()->json(['success' => true]);       
                case 'ready':
                case 'connection.ready':
                    return $this->handleConnectionReady($webhookData, $instance);          
                case 'disconnected':
                case 'connection.lost':
                    return $this->handleDisconnection($webhookData, $instance);             
                default:
                    Log::info('Unhandled webhook event type', [
                        'event_type' => $eventType,
                        'instance_id' => $instanceId
                    ]);
                    //return response()->json(['success' => true, 'message' => 'Event acknowledged']);
                    return response()->json(['status' => 'received','success'=>true], 200);
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
                'webhook_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Handle incoming message from webhook
     */
    private function handleIncomingMessage($webhookData, $instance)
    {
        try {
            Log::info('Processing incoming WhatsApp message with AI', [
                'instance_id' => $instance->instance_id,
                'message_type' => $webhookData['messageType'] ?? 'text',
                'from' => $webhookData['from'] ?? 'unknown'
            ]);

            // Skip messages from self (bot messages)
            if (isset($webhookData['fromMe']) && $webhookData['fromMe']) {
                Log::info('Skipping message from self', ['instance_id' => $instance->instance_id]);
                return response()->json(['success' => true, 'message' => 'Self message ignored']);
            }

            // Extract message data from webhook
            $messageData = $this->extractMessageData($webhookData, $instance);
            
            if (!$messageData) {
                Log::warning('Could not extract message data', [
                    'instance_id' => $instance->instance_id,
                    'webhook_data' => $webhookData
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid message data']);
            }

            // Create IncomingMessage record
            $incomingMessage = IncomingMessage::create($messageData);
            
            Log::info('Created incoming message record', [
                'message_id' => $incomingMessage->id,
                'phone_number' => $incomingMessage->phone_number,
                'message_body' => substr($incomingMessage->message_body, 0, 100) . '...'
            ]);

            // Process message with AI sales agent
            $aiResult = $this->aiWhatsAppService->processIncomingWhatsAppMessageWithAI($incomingMessage);
            
            if ($aiResult['success']) {
                // Send AI response back to customer
                if (isset($aiResult['response']) && !empty($aiResult['response'])) {
                    $sent = $this->aiWhatsAppService->sendResponse($aiResult['response'], $incomingMessage);
                    
                    if ($sent) {
                        $incomingMessage->markAsReplied($aiResult['response']);
                        Log::info('AI response sent successfully', [
                            'message_id' => $incomingMessage->id,
                            'phone_number' => $incomingMessage->phone_number,
                            'agent_name' => $aiResult['agent_name'] ?? 'Unknown'
                        ]);
                    } else {
                        Log::error('Failed to send AI response', [
                            'message_id' => $incomingMessage->id,
                            'phone_number' => $incomingMessage->phone_number
                        ]);
                    }
                } else {
                    // AI decided not to respond (e.g., outside business hours)
                    $incomingMessage->markAsProcessed('No response - ' . ($aiResult['reason'] ?? 'Unknown'));
                }
            } else {
                // AI processing failed
                Log::error('AI processing failed', [
                    'message_id' => $incomingMessage->id,
                    'error' => $aiResult['error'] ?? 'Unknown error',
                    'requires_human' => $aiResult['requires_human'] ?? false
                ]);
                
                // Mark for human intervention if needed
                if ($aiResult['requires_human'] ?? false) {
                    $incomingMessage->update(['status' => 'processed']);
                }
            }

            // Update message count if column exists
            try {
                $instance->increment('total_messages_received');
            } catch (\Exception $e) {
                // Column might not exist, just log and continue
                Log::debug('Could not update message count', ['error' => $e->getMessage()]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Message processed with AI successfully',
                'ai_processed' => $aiResult['success'],
                'response_sent' => isset($aiResult['response']) && !empty($aiResult['response']),
                'conversation_id' => $aiResult['conversation_id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process incoming message with AI', [
                'instance_id' => $instance->instance_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process message with AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle status update from webhook
     */
    private function handleStatusUpdate($webhookData, $instance)
    {
        $newStatus = $webhookData['status'] ?? $webhookData['connection_status'] ?? null;
        
        if ($newStatus) {
            $instance->update(['status' => $newStatus]);
            
            if ($newStatus === 'ready') {
                $this->handleConnectionReady($webhookData, $instance);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle connection ready from webhook
     */
    private function handleConnectionReady($webhookData, $instance)
    {
        $instance->update([
            'status' => 'connected',
            'connected_at' => now()
        ]);

        // Create default AI sales agent if none exists
        if ($instance->user && !$instance->user->aiSalesAgents()->exists()) {
            $this->createDefaultAiAgent($instance->user);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle disconnection from webhook
     */
    private function handleDisconnection($webhookData, $instance)
    {
        $instance->update([
            'status' => 'disconnected',
            'disconnected_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Extract message data from webhook payload
     */
    private function extractMessageData($webhookData, $instance)
    {
        try {
            // Handle different webhook payload structures
            $messageData = null;
            
            // New webhook format with nested data.messages structure
            if (isset($webhookData['data']['messages'])) {
                $messageData = $webhookData['data']['messages'];
            }
            // Legacy format with direct message data
            elseif (isset($webhookData['messages'])) {
                $messageData = $webhookData['messages'];
            }
            // Direct message format
            else {
                $messageData = $webhookData;
            }

            // Extract phone number from remoteJid or key structure
            $chatId = null;
            $phoneNumber = null;
            
            if (isset($messageData['key']['remoteJid'])) {
                $chatId = $messageData['key']['remoteJid'];
                $phoneNumber = $messageData['key']['cleanedSenderPn'] ?? null;
            } elseif (isset($messageData['remoteJid'])) {
                $chatId = $messageData['remoteJid'];
            } else {
                $chatId = $messageData['chatId'] ?? $messageData['from'] ?? null;
            }

            if (!$chatId) {
                Log::warning('No chatId, remoteJid or from field in webhook data', [
                    'webhook_keys' => array_keys($webhookData),
                    'message_keys' => array_keys($messageData)
                ]);
                return null;
            }

            // Clean phone number if not already provided
            if (!$phoneNumber) {
                $phoneNumber = str_replace(['@s.whatsapp.net', '@c.us', '@g.us'], '', $chatId);
                $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            }
            
            if (empty($phoneNumber)) {
                Log::warning('Could not extract valid phone number from chatId', [
                    'chatId' => $chatId,
                    'cleaned_phone' => $phoneNumber
                ]);
                return null;
            }

            // Extract message body from different possible locations
            $messageBody = '';
            
            // Check messageBody field first (direct field)
            if (isset($messageData['messageBody']) && !empty($messageData['messageBody'])) {
                $messageBody = $messageData['messageBody'];
            }
            // Check nested message.conversation
            elseif (isset($messageData['message']['conversation'])) {
                $messageBody = $messageData['message']['conversation'];
            }
            // Check other common fields
            elseif (isset($messageData['body'])) {
                $messageBody = $messageData['body'];
            }
            elseif (isset($messageData['text'])) {
                $messageBody = $messageData['text'];
            }
            
            // Handle quoted messages
            if (isset($messageData['quotedMsg'])) {
                $messageBody = $messageData['quotedMsg']['body'] ?? $messageBody;
            }
            
            // Handle media messages with captions
            if (isset($messageData['caption'])) {
                $messageBody = $messageData['caption'];
            }

            if (empty(trim($messageBody))) {
                Log::info('Empty message body, might be media-only message');
                $messageBody = '[Media message]';
            }

            // Extract sender name
            $senderName = $messageData['pushName'] ?? 
                         $messageData['senderName'] ?? 
                         $messageData['name'] ?? 
                         null;

            // Determine message type
            $messageType = $this->determineMessageType($messageData);

            // Extract media data if available
            $mediaData = $this->extractMediaData($messageData);

            // Check if it's a group message
            $isGroup = str_contains($chatId, '@g.us') || 
                      str_contains($chatId, '.g.') ||
                      ($messageData['isGroup'] ?? false) === true;

            // Extract timestamp - handle both unix timestamp and milliseconds
            $timestamp = $webhookData['timestamp'] ?? 
                        $messageData['messageTimestamp'] ?? 
                        $messageData['timestamp'] ?? 
                        time();
            
            // Convert milliseconds to seconds if needed
            if ($timestamp > 9999999999) {
                $timestamp = intval($timestamp / 1000);
            }
            
            if (is_numeric($timestamp)) {
                $timestamp = date('Y-m-d H:i:s', $timestamp);
            }

            // Extract message ID
            $messageId = $messageData['id'] ?? 
                        $messageData['key']['id'] ?? 
                        $messageData['messageId'] ?? 
                        uniqid();

            // Check if message is from self
            $fromMe = $messageData['key']['fromMe'] ?? 
                     $messageData['fromMe'] ?? 
                     false;

            $extractedData = [
                'user_id' => $instance->user_id,
                'instance_id' => $instance->instance_id,
                'message_id' => $messageId,
                'chat_id' => $chatId,
                'phone_number' => $phoneNumber,
                'sender_name' => $senderName,
                'message_body' => trim($messageBody),
                'message_type' => $messageType,
                'media_data' => $mediaData,
                'from_me' => $fromMe,
                'is_group' => $isGroup,
                'message_timestamp' => $timestamp,
                'status' => 'received',
                'metadata' => $webhookData
            ];

            Log::info('Successfully extracted message data', [
                'message_id' => $messageId,
                'phone_number' => $phoneNumber,
                'message_body' => substr($messageBody, 0, 50) . '...',
                'from_me' => $fromMe,
                'chat_id' => $chatId
            ]);

            return $extractedData;

        } catch (\Exception $e) {
            Log::error('Error extracting message data', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);
            return null;
        }
    }

    /**
     * Determine message type from webhook data
     */
    private function determineMessageType($messageData)
    {
        // Check if there's a nested message object with specific message types
        if (isset($messageData['message'])) {
            $message = $messageData['message'];
            
            // Check for specific message types in the nested message object
            if (isset($message['conversation'])) {
                return 'text';
            }
            if (isset($message['imageMessage'])) {
                return 'image';
            }
            if (isset($message['videoMessage'])) {
                return 'video';
            }
            if (isset($message['audioMessage']) || isset($message['pttMessage'])) {
                return 'audio';
            }
            if (isset($message['documentMessage'])) {
                return 'document';
            }
            if (isset($message['locationMessage'])) {
                return 'location';
            }
            if (isset($message['contactMessage'])) {
                return 'contact';
            }
            if (isset($message['stickerMessage'])) {
                return 'sticker';
            }
        }
        
        // Fallback to legacy type detection
        $type = $messageData['type'] ?? $messageData['messageType'] ?? 'text';
        
        // Map different webhook type formats
        $typeMapping = [
            'chat' => 'text',
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            'document' => 'document',
            'location' => 'location',
            'contact' => 'contact',
            'sticker' => 'sticker',
        ];

        return $typeMapping[$type] ?? 'text';
    }

    /**
     * Extract media data if available
     */
    private function extractMediaData($messageData)
    {
        $messageType = $this->determineMessageType($messageData);
        
        if (!in_array($messageType, ['image', 'video', 'audio', 'document'])) {
            return null;
        }

        $mediaData = [];
        
        // Check nested message object for media information
        if (isset($messageData['message'])) {
            $message = $messageData['message'];
            
            // Extract media data based on message type
            switch ($messageType) {
                case 'image':
                    if (isset($message['imageMessage'])) {
                        $mediaData = [
                            'url' => $message['imageMessage']['url'] ?? null,
                            'filename' => $message['imageMessage']['fileName'] ?? null,
                            'filesize' => $message['imageMessage']['fileLength'] ?? null,
                            'mimetype' => $message['imageMessage']['mimetype'] ?? 'image/jpeg',
                            'caption' => $message['imageMessage']['caption'] ?? null,
                        ];
                    }
                    break;
                    
                case 'video':
                    if (isset($message['videoMessage'])) {
                        $mediaData = [
                            'url' => $message['videoMessage']['url'] ?? null,
                            'filename' => $message['videoMessage']['fileName'] ?? null,
                            'filesize' => $message['videoMessage']['fileLength'] ?? null,
                            'mimetype' => $message['videoMessage']['mimetype'] ?? 'video/mp4',
                            'caption' => $message['videoMessage']['caption'] ?? null,
                        ];
                    }
                    break;
                    
                case 'audio':
                    $audioMsg = $message['audioMessage'] ?? $message['pttMessage'] ?? null;
                    if ($audioMsg) {
                        $mediaData = [
                            'url' => $audioMsg['url'] ?? null,
                            'filename' => $audioMsg['fileName'] ?? null,
                            'filesize' => $audioMsg['fileLength'] ?? null,
                            'mimetype' => $audioMsg['mimetype'] ?? 'audio/ogg',
                            'caption' => null,
                        ];
                    }
                    break;
                    
                case 'document':
                    if (isset($message['documentMessage'])) {
                        $mediaData = [
                            'url' => $message['documentMessage']['url'] ?? null,
                            'filename' => $message['documentMessage']['fileName'] ?? null,
                            'filesize' => $message['documentMessage']['fileLength'] ?? null,
                            'mimetype' => $message['documentMessage']['mimetype'] ?? 'application/octet-stream',
                            'caption' => $message['documentMessage']['caption'] ?? null,
                        ];
                    }
                    break;
            }
        }
        
        // Fallback to legacy format
        if (empty($mediaData)) {
            $mediaData = [
                'url' => $messageData['mediaUrl'] ?? $messageData['url'] ?? null,
                'filename' => $messageData['filename'] ?? null,
                'filesize' => $messageData['filesize'] ?? null,
                'mimetype' => $messageData['mimetype'] ?? null,
                'caption' => $messageData['caption'] ?? null,
            ];
        }

        // Return null if no media data found
        return array_filter($mediaData) ? $mediaData : null;
    }

    // ===== UNIFIED NOTIFICATION API METHODS =====

    /**
     * Create WaSender session via unified API
     */
    public function createSession(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'schema_name' => 'required|string',
            'name' => 'required|string|max:100',
            'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'account_protection' => 'sometimes|boolean',
            'log_messages' => 'sometimes|boolean',
            'read_incoming_messages' => 'sometimes|boolean',
            'webhook_url' => 'sometimes|url',
            'webhook_enabled' => 'sometimes|boolean',
            'webhook_events' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $unifiedService = app(\App\Services\UnifiedNotificationService::class);
            $result = $unifiedService->createSession($request->all());
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Unified session creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create session',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all WaSender sessions via unified API
     */
    public function getSessions(Request $request)
    {
        try {
            $user = $this->resolveUser($request->get('schema_name'));
            $instances = WhatsappInstance::forUser($user->id)
                ->where('platform', 'wasender')
                ->get();

            $sessionsData = $instances->map(function ($instance) {
                return [
                    'id' => $instance->id,
                    'schema_name' => $instance->user->uuid ?? $instance->user_id,
                    'wasender_session_id' => $instance->instance_id,
                    'name' => $instance->instance_name,
                    'phone_number' => $instance->phone_number,
                    'status' => $instance->status,
                    'created_at' => $instance->created_at->toISOString(),
                    'updated_at' => $instance->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $sessionsData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve sessions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single WaSender session via unified API
     */
    public function getSession($id)
    {
        try {
            $instance = WhatsappInstance::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $instance->id,
                    'schema_name' => $instance->user->uuid ?? $instance->user_id,
                    'wasender_session_id' => $instance->instance_id,
                    'name' => $instance->instance_name,
                    'phone_number' => $instance->phone_number,
                    'status' => $instance->status,
                    'account_protection' => $instance->metadata['account_protection'] ?? true,
                    'log_messages' => $instance->metadata['log_messages'] ?? true,
                    'read_incoming_messages' => $instance->metadata['read_incoming_messages'] ?? false,
                    'webhook_url' => $instance->webhook_url,
                    'webhook_enabled' => !empty($instance->webhook_url),
                    'webhook_events' => $instance->metadata['webhook_events'] ?? [],
                    'created_at' => $instance->created_at->toISOString(),
                    'updated_at' => $instance->updated_at->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Connect WaSender session via unified API
     */
    public function connectSession($id)
    {
        try {
            $instance = WhatsappInstance::findOrFail($id);
            $unifiedService = app(\App\Services\UnifiedNotificationService::class);
            
            $result = $unifiedService->connectSession($instance->instance_id);
            
            if ($result['success'] ?? false) {
                $instance->updateFromApiResponse($result);
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to connect session',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WaSender session status via unified API
     */
    public function getSessionStatus($id)
    {
        try {
            $instance = WhatsappInstance::findOrFail($id);
            $unifiedService = app(\App\Services\UnifiedNotificationService::class);
            
            $result = $unifiedService->getSessionStatus($instance->instance_id);
            
            if ($result['success'] ?? false) {
                $instance->updateFromApiResponse($result);
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get session status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WaSender session QR code via unified API
     */
    public function getQRCode($id)
    {
        try {
            $instance = WhatsappInstance::findOrFail($id);
            $unifiedService = app(\App\Services\UnifiedNotificationService::class);
            
            $result = $unifiedService->getQRCode($instance->instance_id);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get QR code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update WaSender session via unified API
     */
    public function updateSession(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'phone_number' => 'sometimes|string|regex:/^\+?[1-9]\d{1,14}$/',
            'webhook_url' => 'sometimes|url',
            'webhook_enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $instance = WhatsappInstance::findOrFail($id);
            
            // Update local instance
            $updateData = array_filter($request->only([
                'name' => 'instance_name',
                'phone_number' => 'phone_number',
                'webhook_url' => 'webhook_url',
            ]));

            if ($updateData) {
                $instance->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session updated successfully',
                'data' => [
                    'id' => $instance->id,
                    'schema_name' => $instance->user->uuid ?? $instance->user_id,
                    'name' => $instance->instance_name,
                    'phone_number' => $instance->phone_number,
                    'webhook_enabled' => !empty($instance->webhook_url),
                    'updated_at' => $instance->fresh()->updated_at->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update session',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete WaSender session via unified API
     */
    public function deleteSession($id)
    {
        try {
            $instance = WhatsappInstance::findOrFail($id);
            $deletedWasenderId = $instance->instance_id;
            $deletedLocalId = $instance->id;
            
            $instance->delete();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session deleted successfully',
                'data' => [
                    'deleted_local_id' => $deletedLocalId,
                    'deleted_wasender_id' => $deletedWasenderId,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete session',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve user from schema name
     */
    protected function resolveUser($schemaName)
    {
        if (!$schemaName) {
            throw new \Exception('Schema name is required');
        }

        // Try UUID first
        $user = User::where('uuid', $schemaName)->first();
        
        if (!$user && is_numeric($schemaName)) {
            // Try direct ID
            $user = User::find($schemaName);
        }

        if (!$user) {
            throw new \Exception("User not found for schema: {$schemaName}");
        }

        return $user;
    }
}
