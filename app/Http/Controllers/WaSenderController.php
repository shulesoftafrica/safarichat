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
        // Check if user already has a connected WhatsApp instance
        $connectedInstance = Auth::user()->whatsappInstances()
            ->where('status', 'connected')
            ->first();

        if ($connectedInstance) {
            // User already has connected WhatsApp, redirect to product setup
            $hasProducts = Auth::user()->products()->exists();
            
            if (!$hasProducts) {
                // Redirect to product setup
                return redirect()->route('products.index')
                    ->with('success', 'Your WhatsApp is already connected. Please define your products/services.');
            }
            
            // Redirect to dashboard if products also exist
            return redirect()->route('home')
                ->with('info', 'Your WhatsApp is already connected.');
        }

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
            
            // Webhook is configured AFTER creation via updateSessionWebhook() so we can
            // use the numeric session ID assigned by WaSender, not the human-readable name.
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
     * PATCH the webhook URL on WaSender using the numeric session ID.
     * Must be called AFTER session creation so the real numeric ID is known.
     * This ensures the webhook URL is always /api/wasender/webhook/{id}
     * and never /api/wasender/webhook/{instance_name}.
     */
    private function updateSessionWebhook(string $sessionId): bool
    {
        try {
            $apiKey = config('services.wasender.access_token');
            if (!$apiKey) {
                Log::warning('WaSender API key not configured, skipping webhook update', [
                    'session_id' => $sessionId,
                ]);
                return false;
            }

            $webhookUrl = url('/api/wasender/webhook/' . $sessionId);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->put('https://www.wasenderapi.com/api/whatsapp-sessions/' . $sessionId, [
                'webhook_url'     => $webhookUrl,
                'webhook_enabled' => true,
                'webhook_events'  => [
                    'messages.received',
                    'session.status',
                    'messages.update',
                ],
            ]);

            if ($response->successful()) {
                Log::info('Webhook URL updated on WaSender with correct session ID', [
                    'session_id'  => $sessionId,
                    'webhook_url' => $webhookUrl,
                ]);
                return true;
            }

            Log::warning('Failed to update webhook URL on WaSender', [
                'session_id' => $sessionId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Exception while updating webhook URL on WaSender', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate QR code for session connection via WaSender API
     * Uses POST /api/whatsapp-sessions/{id}/connect as per WaSender documentation
     */
    private function generateQRCode($sessionId)
    {
        try {
            Log::info('Generating QR code via WaSender API', ['session_id' => $sessionId]);
            $apiKey = config('services.wasender.access_token', 'de042e1a46b394de63bed34c5b2d9c55108db5061b075b29ce9225be30d7cca2');
            if (!$apiKey) {
                Log::warning('WaSender API key not configured, using placeholder QR');
                return $this->generatePlaceholderQR($sessionId);
            }

            // WaSender API endpoint for connecting and getting QR code (as per documentation)
            // POST /api/whatsapp-sessions/{whatsappSession}/connect
            $url = "https://www.wasenderapi.com/api/whatsapp-sessions/{$sessionId}/connect";
            $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->post($url);

            if ($response->successful()) {
                $data = $response->json();
                // WaSender returns: {"success": true, "data": {"status": "NEED_SCAN", "qrCode": "..."}}
                $qrCodeString = $data['data']['qrCode'] ?? $data['data']['qr_code'] ?? null;
                $status = $data['data']['status'] ?? null;

                Log::info('QR code response from WaSender API', [
                    'session_id' => $sessionId,
                    'status' => $status,
                    'has_qr_code' => !empty($qrCodeString),
                    'response_keys' => array_keys($data['data'] ?? [])
                ]);

                if ($status === 'CONNECTED' || $status === 'connected') {
                    return 'ALREADY_CONNECTED';
                }
                if (!$qrCodeString) {
                    Log::warning('QR code not found in WaSender API response', [
                        'session_id' => $sessionId,
                        'response' => $data
                    ]);
                    return 'QR_GENERATION_FAILED';
                }
                
                // Convert WaSender QR string to image data URL
                $qrCodeImageData = $this->convertQRStringToImage($qrCodeString);
                
                Log::info('QR code string converted to image', [
                    'session_id' => $sessionId,
                    'type' => $qrCodeImageData['type'],
                    'data_length' => strlen($qrCodeImageData['data'])
                ]);
                
                // Return as array with type and data for frontend to handle properly
                return $qrCodeImageData;
            } else {
                // Check if the error is because session is already connected
                $responseBody = $response->json();
                $errorMessage = $responseBody['message'] ?? '';
                
                if (stripos($errorMessage, 'already connected') !== false) {
                    Log::info('WhatsApp session already connected', [
                        'session_id' => $sessionId,
                        'message' => $errorMessage
                    ]);
                    return 'ALREADY_CONNECTED';
                }
                
                Log::error('Failed to get QR code from WaSender API', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500)
                ]);
                return 'QR_GENERATION_FAILED';
            }
        } catch (\Exception $e) {
            Log::error('Error generating QR via WaSender API', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return 'QR_GENERATION_FAILED';
        }
    }
    
    /**
     * Convert WaSender QR string to image data URL
     * WaSender returns a QR string like "2@DTMUHeYfa9/...", we need to convert it to an image
     * Returns: {"type": "base64|url", "data": "..."}
     */
    private function convertQRStringToImage($qrString)
    {
        try {
            // If it's already a data URL, extract the base64 part
            if (strpos($qrString, 'data:image') === 0) {
                preg_match('/data:image\/\w+;base64,(.+)/', $qrString, $matches);
                if (isset($matches[1])) {
                    return ['type' => 'base64', 'data' => $matches[1]];
                }
                return ['type' => 'base64', 'data' => $qrString];
            }
            
            // If it's already a URL, return it as URL type
            if (filter_var($qrString, FILTER_VALIDATE_URL)) {
                return ['type' => 'url', 'data' => $qrString];
            }

            // Check if SimpleSoftwareIO QrCode is available
            if (class_exists('SimpleSoftwareIO\\QrCode\\Generator')) {
                $qrGenerator = new \SimpleSoftwareIO\QrCode\Generator();
                $qrCodeImage = $qrGenerator->format('png')->size(300)->margin(2)->generate($qrString);
                
                // Convert to base64 (without prefix)
                $base64 = base64_encode($qrCodeImage);
                
                Log::info('QR code generated with SimpleSoftwareIO', [
                    'base64_length' => strlen($base64)
                ]);
                
                return ['type' => 'base64', 'data' => $base64];
            }

            // Fallback: Use external API to generate QR code image
            $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=" . urlencode($qrString);
            
            Log::info('Using external QR code generation service', ['url' => $qrCodeUrl]);
            
            return ['type' => 'url', 'data' => $qrCodeUrl];

        } catch (\Exception $e) {
            Log::error('Error converting QR string to image', [
                'error' => $e->getMessage(),
                'qr_string_length' => strlen($qrString)
            ]);
            
            // Fallback to external service
            return ['type' => 'url', 'data' => "https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=" . urlencode($qrString)];
        }
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
                'auth_method' => 'nullable|string|in:qr'
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
                Log::info('Using existing session from WaSender', ['session_id' => $existingSession['id'], 'phone_number' => $phoneNumber]);
                $sessionData = $existingSession;
            } else {
                // Step 3: Create new session if not exists
                Log::info('Creating new session for phone number', ['phone_number' => $phoneNumber]);
                $createResponse = $this->createNewSession($phoneNumber, $instanceName);
                
                // If phone number already taken, fetch existing session and use it
                if (!$createResponse['success']) {
                    $errorMessage = $createResponse['message'] ?? '';
                    
                    // Check if it's a "phone number already taken" error
                    if (strpos(strtolower($errorMessage), 'phone number has already been taken') !== false ||
                        strpos(strtolower($errorMessage), 'already been taken') !== false) {
                        
                        Log::warning('Phone number already taken, fetching existing session', ['phone_number' => $phoneNumber]);
                        $existingSession = $this->checkExistingSession($phoneNumber);
                        
                        if ($existingSession) {
                            Log::info('Successfully retrieved existing session after duplicate error', [
                                'session_id' => $existingSession['id'],
                                'phone_number' => $phoneNumber
                            ]);
                            $sessionData = $existingSession;
                        } else {
                            return response()->json([
                                'success' => false,
                                'message' => 'Phone number already registered but existing session not found. Please contact support.'
                            ], 500);
                        }
                    } else {
                        // Other error occurred
                        return response()->json([
                            'success' => false,
                            'message' => $createResponse['message'] ?? 'Failed to create session'
                        ], 500);
                    }
                } else {
                    $sessionData = $createResponse['data'];
                }
            }

            // Extract session details
            $sessionId = $sessionData['id'];
            $apiKey = $sessionData['api_key'] ?? null;
            $webhookSecret = $sessionData['webhook_secret'] ?? null;
            $sessionStatus = $sessionData['status'] ?? 'pending';

            // Step 3b: Register/update the webhook on WaSender using the numeric session ID.
            // This ensures the webhook is always .../webhook/{numeric_id}, never .../webhook/{name}.
            $this->updateSessionWebhook((string) $sessionId);

            // Step 4: Save/update instance in database
            $existingInstance = WhatsappInstance::where('user_id', $user->id)
                ->where('phone_number', $phoneNumber)
                ->first();

            if ($existingInstance) {
                $instance = $existingInstance;

                // Detect reconnection: if the session ID changed (e.g. user deleted
                // the instance in WaSender and is re-onboarding), clear the Unified
                // Notification registration timestamp so it re-registers with the new ID.
                $sessionIdChanged = (string) $existingInstance->instance_id !== (string) $sessionId;

                $updateData = [
                    'instance_id'   => $sessionId,
                    'api_key'       => $apiKey,
                    'instance_name' => $instanceName,
                    'status'        => $sessionStatus,
                    'webhook_url'   => $sessionData['webhook_url'] ?? null,
                    'webhook_secret' => $webhookSecret,
                    'platform'      => 'wasender',
                    'metadata'      => json_encode([
                        'session_data' => $sessionData,
                        'updated_at'   => now()->toISOString()
                    ]),
                ];

                if ($sessionIdChanged) {
                    // Reset so registerWithUnifiedNotificationApi() runs again for the new session
                    $updateData['unified_api_registered_at'] = null;
                    Log::info('Reconnection detected — session ID changed, Unified API registration reset', [
                        'old_session_id' => $existingInstance->instance_id,
                        'new_session_id' => $sessionId,
                        'user_id'        => $user->id,
                    ]);
                }

                $instance->update($updateData);
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
                
                $isArray = is_array($qrCode);
                $isString = is_string($qrCode);
                
                Log::info('QR Code generation result', [
                    'session_id' => $sessionId,
                    'is_array' => $isArray,
                    'is_string' => $isString,
                    'type' => $isArray ? ($qrCode['type'] ?? 'unknown') : gettype($qrCode),
                    'preview' => $isArray ? ('Array with keys: ' . implode(', ', array_keys($qrCode))) : ($isString ? substr($qrCode, 0, 100) . '...' : $qrCode)
                ]);

                if ($qrCode === 'ALREADY_CONNECTED') {
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

                // Retry QR generation once if failed (for the same session)
                if ($qrCode === 'QR_GENERATION_FAILED') {
                    Log::warning('QR generation failed, retrying once for the same session', [
                        'session_id' => $sessionId,
                        'phone_number' => $phoneNumber
                    ]);
                    
                    // Wait a moment before retrying
                    sleep(2);
                    
                    // Retry QR generation for the same session
                    $qrCodeRetry = $this->generateQRCode($sessionId);
                    
                    $isArrayRetry = is_array($qrCodeRetry);
                    $isStringRetry = is_string($qrCodeRetry);
                    
                    Log::info('QR Code generation retry result', [
                        'session_id' => $sessionId,
                        'is_array' => $isArrayRetry,
                        'is_string' => $isStringRetry,
                        'type' => $isArrayRetry ? ($qrCodeRetry['type'] ?? 'unknown') : gettype($qrCodeRetry),
                        'preview' => $isArrayRetry ? ('Array with keys: ' . implode(', ', array_keys($qrCodeRetry))) : ($isStringRetry ? substr($qrCodeRetry, 0, 100) . '...' : $qrCodeRetry)
                    ]);
                    
                    if ($qrCodeRetry !== 'QR_GENERATION_FAILED' && $qrCodeRetry !== 'ALREADY_CONNECTED') {
                        $qrCode = $qrCodeRetry;
                        Log::info('QR code generation succeeded on retry');
                    } else {
                        // Still failed after retry
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to generate QR code. The session was created but QR code generation failed. Please try refreshing or contact support.',
                            'error' => 'QR_GENERATION_FAILED',
                            'session_id' => $sessionId,
                            'instance_id' => $instance->id
                        ], 500);
                    }
                }

                // Update instance with QR code (store as JSON if it's an array)
                $qrCodeToStore = is_array($qrCode) ? json_encode($qrCode) : $qrCode;
                
                $instance->update([
                    'qr_code' => $qrCodeToStore,
                    'qr_code_generated' => true,
                    'qr_code_generated_at' => now(),
                    'connect_status' => 'connecting'
                ]);

                // Create default AI Sales Agent for this user if not exists
                $this->createDefaultAiAgent($user);

                // QR code for frontend
                $qrCodeForFrontend = $qrCode;

                Log::info('WhatsApp session creation completed with QR', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id,
                    'has_qr_code' => !empty($qrCode),
                    'qr_code_stored' => !empty($instance->qr_code),
                    'qr_code_is_array' => is_array($qrCode)
                ]);

                // Handle QR code format - it's now an array with type and data
                $qrCodeData = is_array($qrCodeForFrontend) ? $qrCodeForFrontend : ['type' => 'base64', 'data' => $qrCodeForFrontend];
                
                $response = [
                    'success' => true,
                    'message' => 'QR code generated successfully. Scan with WhatsApp to connect.',
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id,
                    'qr_code' => $qrCodeData['data'],
                    'qr_code_type' => $qrCodeData['type'],
                    'auth_method' => 'qr',
                    'status' => 'NEED_SCAN',
                    'phone_number' => $phoneNumber,
                    // Debug information
                    'debug' => [
                        'api_key_configured' => !empty(config('services.wasender.access_token')),
                        'qr_type' => $qrCodeData['type'],
                        'data_length' => strlen($qrCodeData['data'])
                    ]
                ];

                Log::info('Sending QR response to frontend', ['response_keys' => array_keys($response)]);
                
                return response()->json($response);
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

                // Re-push webhook config now that the session is definitively connected.
                // The initial updateSessionWebhook() call (made at pending-state during
                // onboarding) may have been silently dropped/reset by WaSender before
                // the QR was scanned.  Calling it here guarantees the webhook is live.
                $webhookUpdated = $this->updateSessionWebhook($sessionId);
                if (!$webhookUpdated) {
                    Log::warning('Webhook re-registration may have failed at connection-confirmed stage', [
                        'session_id' => $sessionId,
                        'user_id'    => $instance->user_id,
                    ]);
                }

                // Register / sync with Unified Notification API now that connection is confirmed
                $unifiedResult = $this->registerWithUnifiedNotificationApi($instance, $connectionStatus['data'] ?? []);

                Log::info('WhatsApp session connected', [
                    'session_id'               => $sessionId,
                    'user_id'                  => $instance->user_id,
                    'unified_api_registered'   => $unifiedResult['success'] ?? false,
                    'unified_api_skip_reason'  => $unifiedResult['reason'] ?? null,
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
                    ],
                    'unified_api_registered' => $unifiedResult['success'] ?? false,
                ]);
            }

            // Return current status
            $currentStatus = $connectionStatus['status'] ?? $instance->status ?? 'pending';
            
            // Decode QR code if it's JSON
            $qrCodeStored = $instance->qr_code;
            $qrCodeData = null;
            $qrCodeType = 'base64';
            
            if ($qrCodeStored) {
                $decoded = json_decode($qrCodeStored, true);
                if (is_array($decoded) && isset($decoded['type']) && isset($decoded['data'])) {
                    $qrCodeData = $decoded['data'];
                    $qrCodeType = $decoded['type'];
                } else {
                    $qrCodeData = $qrCodeStored;
                }
            }
            
            return response()->json([
                'success' => true,
                'status' => $currentStatus,
                'message' => $this->getStatusMessage($currentStatus),
                'qr_code' => $qrCodeData,
                'qr_code_type' => $qrCodeType,
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
     * Register (or re-sync) a newly connected WhatsApp instance with the
     * Unified Notification API so it can route inbound/outbound messages.
     *
     * Called once from checkSessionStatus() when WaSender confirms connection.
     * Uses config('services.unified_notification.*') and the bearer token from
     * config('notifications.unified_api.bearer_token') (same source as
     * UnifiedNotificationService).
     *
     * Idempotent: skips silently if the instance was already registered.
     * Non-blocking: connection succeeds even if this call fails.
     */
    private function registerWithUnifiedNotificationApi(WhatsappInstance $instance, array $sessionData = []): array
    {
        try {
            $baseUrl    = config('services.unified_notification.base_url', 'https://notifications.shulesoft.africa/api');
            $token      = config('notifications.unified_api.bearer_token');
            $timeout    = (int) config('services.unified_notification.timeout', 30);

            if (!$token) {
                Log::warning('Unified Notification bearer token not configured — skipping registration', [
                    'instance_id' => $instance->instance_id,
                ]);
                return ['success' => false, 'reason' => 'not_configured'];
            }

            // Idempotent guard — only register once per instance
            if (!empty($instance->unified_api_registered_at)) {
                Log::info('WhatsApp instance already registered with Unified Notification API', [
                    'instance_id'   => $instance->instance_id,
                    'registered_at' => $instance->unified_api_registered_at,
                ]);
                return ['success' => true, 'reason' => 'already_registered'];
            }

            $user        = $instance->user;
            $schemaName  = $user->uuid ?? 'user_' . $user->id;
            $webhookUrl  = url('/api/wasender/webhook/' . $instance->instance_id);

            // Payload matches POST /api/wasender/sessions/create schema exactly.
            // Fields wasender_session_id, api_key, status, connected_at are NOT accepted
            // by that endpoint — they are returned in the response, not sent in the request.
            $payload = [
                'schema_name'            => $schemaName,
                'name'                   => $instance->instance_name ?? ('WhatsApp ' . $instance->phone_number),
                'phone_number'           => $instance->phone_number,
                'webhook_url'            => $webhookUrl,
                'webhook_enabled'        => true,
                'webhook_events'         => [
                    'messages.received',
                    'session.status',
                    'messages.update',
                ],
                'account_protection'     => true,
                'log_messages'           => true,
                'read_incoming_messages' => false,
            ];

            Log::info('Registering WhatsApp instance with Unified Notification API', [
                'instance_id'  => $instance->instance_id,
                'phone_number' => $instance->phone_number,
                'schema_name'  => $schemaName,
                'endpoint'     => $baseUrl . '/wasender/sessions/create',
            ]);

            $response = Http::timeout($timeout)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post($baseUrl . '/wasender/sessions/create', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                // Persist registration timestamp + response into metadata
                $existing = is_array($instance->metadata)
                    ? $instance->metadata
                    : (json_decode($instance->metadata ?? '{}', true) ?? []);

                $instance->update([
                    'unified_api_registered_at' => now(),
                    'metadata' => array_merge($existing, [
                        'unified_api_registration' => [
                            'registered_at' => now()->toISOString(),
                            'schema_name'   => $schemaName,
                            'response'      => $responseData,
                        ],
                    ]),
                ]);

                Log::info('Successfully registered with Unified Notification API', [
                    'instance_id'  => $instance->instance_id,
                    'phone_number' => $instance->phone_number,
                ]);

                return ['success' => true, 'data' => $responseData];
            }

            Log::warning('Unified Notification API registration failed', [
                'instance_id'   => $instance->instance_id,
                'http_status'   => $response->status(),
                'response_body' => $response->body(),
            ]);

            return [
                'success' => false,
                'reason'  => 'api_error',
                'status'  => $response->status(),
                'body'    => $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Exception while registering with Unified Notification API', [
                'instance_id' => $instance->instance_id,
                'error'       => $e->getMessage(),
            ]);
            return ['success' => false, 'reason' => 'exception', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user's active WhatsApp instances with live WaSender API status check
     */
    public function getUserInstances(Request $request)
    {
        try {
            $instances = WhatsappInstance::where('user_id', Auth::id())
                ->with('aiSalesAgent')
                ->orderBy('created_at', 'desc')
                ->get();

            // Check live status directly from WaSender API for each instance
            $apiKey = config('services.wasender.access_token');

            foreach ($instances as $instance) {
                try {
                    if (!$apiKey) {
                        break; // No API key configured, skip live check
                    }

                    // Call WaSender API directly for real-time status
                    $statusResult = $this->checkConnectionStatus($instance->instance_id);
                    $realTimeStatus = $statusResult['status'] ?? null;

                    if ($realTimeStatus) {
                        $mappedConnectStatus = $this->mapApiStatusToConnectStatus($realTimeStatus);
                        $mappedStatus = $this->mapApiStatusToStatus($realTimeStatus);

                        $instance->update([
                            'connect_status' => $mappedConnectStatus,
                            'status' => $mappedStatus,
                            'last_seen' => now()
                        ]);

                        // Clear warning banner cache if now connected
                        if ($mappedConnectStatus === 'ready') {
                            \Cache::forget('whatsapp_disconnected_' . Auth::id());
                        }

                        Log::info('WaSender live status synced', [
                            'instance_id' => $instance->instance_id,
                            'api_status' => $realTimeStatus,
                            'connect_status' => $mappedConnectStatus,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to check live WaSender status for instance', [
                        'instance_id' => $instance->instance_id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with next instance even if one fails
                }
            }

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
     * Map WaSender API status to connect_status enum
     * Enum values: ['disconnected', 'connecting', 'ready', 'error']
     */
    private function mapApiStatusToConnectStatus(string $apiStatus): string
    {
        $status = strtolower($apiStatus);
        
        return match($status) {
            'connected', 'ready', 'open' => 'ready',
            'connecting', 'initializing', 'starting' => 'connecting',
            'disconnected', 'closed', 'logged_out', 'offline' => 'disconnected',
            'failed', 'error', 'timeout' => 'error',
            default => 'disconnected'
        };
    }

    /**
     * Map WaSender API status to status enum
     * Enum values: ['connecting', 'connected', 'disconnected', 'error']
     */
    private function mapApiStatusToStatus(string $apiStatus): string
    {
        $status = strtolower($apiStatus);
        
        return match($status) {
            'connected', 'ready', 'open' => 'connected',
            'connecting', 'initializing', 'starting' => 'connecting',
            'disconnected', 'closed', 'logged_out', 'offline' => 'disconnected',
            'failed', 'error', 'timeout' => 'error',
            default => 'disconnected'
        };
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
     * Update the ignored-contacts list for a WhatsApp instance.
     *
     * PUT /api/wasender/instances/{instanceId}/ignored-contacts
     *
     * Request body:
     *   {
     *     "ignored_contacts": [
     *       {"phone": "255714825469", "label": "John (friend)"},
     *       {"phone": "255756123456"}
     *     ]
     *   }
     *
     * Phone numbers are normalised to digits-only before storage.
     */
    public function updateIgnoredContacts(Request $request, $instanceId): \Illuminate\Http\JsonResponse
    {
        $instance = WhatsappInstance::where('instance_id', $instanceId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$instance) {
            return response()->json(['success' => false, 'message' => 'Instance not found'], 404);
        }

        $validated = $request->validate([
            'ignored_contacts'           => ['required', 'array'],
            'ignored_contacts.*.phone'   => ['required', 'string', 'regex:/^[0-9+\- ]{7,20}$/'],
            'ignored_contacts.*.label'   => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        // Normalise phone numbers to digits-only before persisting
        $contacts = array_map(function (array $entry): array {
            $entry['phone'] = preg_replace('/[^0-9]/', '', $entry['phone']);
            if (isset($entry['label'])) {
                $entry['label'] = trim($entry['label']);
            }
            return $entry;
        }, $validated['ignored_contacts']);

        // Deduplicate by phone number
        $seen     = [];
        $contacts = array_values(array_filter($contacts, function (array $entry) use (&$seen): bool {
            if ($entry['phone'] === '' || isset($seen[$entry['phone']])) {
                return false;
            }
            $seen[$entry['phone']] = true;
            return true;
        }));

        $instance->update(['ignored_contacts' => $contacts]);

        Log::info('Ignored contacts updated', [
            'instance_id' => $instance->instance_id,
            'user_id'     => Auth::id(),
            'count'       => count($contacts),
        ]);

        return response()->json([
            'success'          => true,
            'ignored_contacts' => $contacts,
            'count'            => count($contacts),
        ]);
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
     * Handle incoming webhook from WaSender API
     */
    /**
     * Decide whether a webhook payload represents an INBOUND message, regardless of
     * the exact event name the provider used.
     *
     * Guards against silent inbound loss when a provider renames its message event
     * (e.g. 'messages.received' -> 'messages.upsert'): known non-message events are
     * excluded, known message events are matched, and anything else that actually
     * carries message content is treated as a message. Self/status/receipt payloads
     * are still filtered downstream (fromMe check + extractMessageData).
     */
    private function isIncomingMessageEvent($eventType, array $webhookData): bool
    {
        $eventType = strtolower(trim((string) $eventType));

        // Explicit non-message events must NOT be treated as inbound messages.
        $nonMessageEvents = [
            'status', 'status.update', 'message.ack', 'ack', 'messages.update',
            'messages.delete', 'message.revoke', 'qr', 'qr.update', 'ready',
            'connection.ready', 'connection.update', 'disconnected', 'connection.lost',
            'presence.update', 'chats.upsert', 'chats.update', 'contacts.update',
            'groups.update', 'call',
        ];
        if (in_array($eventType, $nonMessageEvents, true)) {
            return false;
        }

        // Known inbound-message event names across providers.
        $messageEvents = [
            'message', 'messages', 'messages.received', 'messages.upsert',
            'message.received', 'message.any', 'message.create', 'onmessage',
        ];
        if (in_array($eventType, $messageEvents, true)) {
            return true;
        }

        // Unknown event name — treat as a message only if the payload actually
        // carries message content.
        return isset($webhookData['data']['messages'])
            || isset($webhookData['messages'])
            || isset($webhookData['message'])
            || isset($webhookData['messageBody'])
            || isset($webhookData['data']['message']);
    }

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

            // Normalize provider event-name variants. Different WhatsApp providers
            // (Baileys-based ones especially) emit inbound messages under different
            // event names — 'messages.upsert', 'message', 'message.received', etc. —
            // or carry the message in the payload without a recognized event name.
            // Route any message-bearing payload to the incoming handler so a
            // provider-side rename can never silently drop inbound messages.
            if ($this->isIncomingMessageEvent($eventType, $webhookData)) {
                $eventType = 'message';
            }

            switch ($eventType) {
                case 'message':
                case 'messages.received':
                    return match($instance->instance_type) {
                        'customer_success' => $this->handleCsIncomingMessage($webhookData, $instance),
                        'both'             => $this->handleHybridIncomingMessage($webhookData, $instance),
                        default            => $this->handleIncomingMessage($webhookData, $instance),
                    };
                
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
     * Handle incoming webhook from WaSender API using instance UUID (NEW)
     */
    public function handleWebhookByUuid(Request $request, $instanceUuid)
    {
        try {
            $webhookData = $request->all();
            
            Log::info('Received WaSender webhook by UUID', [
                'instance_uuid' => $instanceUuid,
                'event_type' => $webhookData['event'] ?? 'unknown',
                'webhook_data' => $webhookData
            ]);

            // Find instance by UUID instead of instanceId
            $instance = WhatsappInstance::where('uuid', $instanceUuid)->first();
            
            if (!$instance) {
                Log::warning('Webhook received for unknown instance UUID', ['instance_uuid' => $instanceUuid]);
                return response()->json(['success' => false, 'message' => 'Instance not found'], 404);
            }

            // Update instance last seen
            $instance->update(['last_seen' => now()]);

            // Handle different webhook events (reuse existing logic)
            $eventType = $webhookData['event'] ?? $webhookData['type'] ?? 'message';

            // Normalize inbound-message event-name variants (see handleWebhook).
            if ($this->isIncomingMessageEvent($eventType, $webhookData)) {
                $eventType = 'message';
            }

            switch ($eventType) {
                case 'message':
                case 'messages.received':
                    return match($instance->instance_type) {
                        'customer_success' => $this->handleCsIncomingMessage($webhookData, $instance),
                        'both'             => $this->handleHybridIncomingMessage($webhookData, $instance),
                        default            => $this->handleIncomingMessageWithInstance($webhookData, $instance),
                    };
                
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
                        'instance_uuid' => $instanceUuid
                    ]);
                    return response()->json(['status' => 'received','success'=>true], 200);
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'instance_uuid' => $instanceUuid,
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
     * Handle incoming message from webhook (legacy method)
     */
    private function handleIncomingMessage($webhookData, $instance)
    {
        return $this->handleIncomingMessageWithInstance($webhookData, $instance);
    }

    /**
     * Handle incoming message from webhook with instance tracking
     */
    private function handleIncomingMessageWithInstance($webhookData, $instance)
    {
        try {
            Log::info('Processing incoming WhatsApp message with AI and instance tracking', [
                'instance_id' => $instance->instance_id,
                'instance_uuid' => $instance->uuid,
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

            // Silently drop messages from contacts the business owner chose to ignore
            if ($this->isContactIgnored($instance, $messageData['phone_number'] ?? '')) {
                Log::info('[Sales] Ignored contact silently skipped', [
                    'phone'       => $messageData['phone_number'] ?? '',
                    'instance_id' => $instance->id,
                ]);
                return response()->json(['success' => true, 'message' => 'Contact ignored']);
            }

            // Idempotency by provider message id: a duplicate webhook delivery — or a
            // second event type for the SAME WhatsApp message — carries the same
            // message_id. Without this, each delivery creates its own incoming_messages
            // row and generates its own AI reply, so the customer receives two (different)
            // answers to one message. firstOrCreate + wasRecentlyCreated skips the duplicate.
            $providerMessageId = $messageData['message_id'] ?? null;

            if (!empty($providerMessageId)) {
                $incomingMessage = IncomingMessage::firstOrCreate(
                    [
                        'message_id'           => $providerMessageId,
                        'whatsapp_instance_id' => $instance->id,
                    ],
                    $messageData
                );

                if (!$incomingMessage->wasRecentlyCreated) {
                    Log::info('Duplicate inbound webhook ignored (message_id already recorded)', [
                        'message_id'  => $providerMessageId,
                        'instance_id' => $instance->instance_id,
                        'existing_row'=> $incomingMessage->id,
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Duplicate message ignored (idempotency)',
                    ], 200);
                }
            } else {
                // No provider id to dedupe on — fall back to a plain insert.
                $incomingMessage = IncomingMessage::create($messageData);
            }

            Log::info('Created incoming message record', [
                'message_id' => $incomingMessage->id,
                'phone_number' => $incomingMessage->phone_number,
                'message_body' => substr($incomingMessage->message_body, 0, 100) . '...'
            ]);

            // Process message with AI sales agent (include instance for context)
            $aiResult = $this->aiWhatsAppService->processIncomingWhatsAppMessageWithAI($incomingMessage, $instance);
            
            if ($aiResult['success']) {
                // Send AI response back to customer using instance
                if (isset($aiResult['response']) && !empty($aiResult['response'])) {
                    $sent = $this->aiWhatsAppService->sendResponse($aiResult['response'], $incomingMessage, $instance);
                    
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
     * Check whether a phone number is in the instance's ignored-contacts list.
     *
     * Comparison is digits-only on both sides so callers don't need to
     * worry about whether the stored or incoming value contains a leading +.
     */
    private function isContactIgnored(WhatsappInstance $instance, string $rawPhone): bool
    {
        $ignored = $instance->ignored_contacts ?? [];

        if (empty($ignored)) {
            return false;
        }

        $normalized = preg_replace('/[^0-9]/', '', $rawPhone);

        if ($normalized === '') {
            return false;
        }

        foreach ($ignored as $entry) {
            $stored = preg_replace('/[^0-9]/', '', $entry['phone'] ?? '');
            if ($stored !== '' && $stored === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle incoming messages on a CS-only instance.
     * All inbound text is treated as the business owner replying to CS prompts.
     */
    private function handleCsIncomingMessage(array $webhookData, WhatsappInstance $instance): \Illuminate\Http\JsonResponse
    {
        try {
            // Skip self-sent messages
            if (! empty($webhookData['fromMe'])) {
                return response()->json(['success' => true, 'message' => 'Self message ignored']);
            }

            $user = $instance->user;
            if (! $user) {
                Log::warning('[CS] Instance has no associated user', ['instance_id' => $instance->id]);
                return response()->json(['success' => false, 'message' => 'No user for instance']);
            }

            $messageData = $this->extractMessageData($webhookData, $instance);

            // Silently drop messages from contacts the business owner chose to ignore
            if ($this->isContactIgnored($instance, $messageData['phone_number'] ?? '')) {
                Log::info('[CS] Ignored contact silently skipped', [
                    'phone'       => $messageData['phone_number'] ?? '',
                    'instance_id' => $instance->id,
                ]);
                return response()->json(['success' => true, 'message' => 'Contact ignored']);
            }

            $body        = trim($messageData['message_body'] ?? '');

            // Non-text / empty → treat as help request
            if ($body === '') {
                $body = 'help';
            }

            app(\App\Services\CustomerSuccess\CsConversationHandler::class)
                ->handleInbound($user, $body, $webhookData, $instance);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('[CS] handleCsIncomingMessage error', [
                'instance_id' => $instance->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'CS handler error'], 500);
        }
    }

    /**
     * Handle incoming messages on a hybrid instance (instance_type = 'both').
     *
     * Routing priority:
     *   1. Instance owner with an active CS session, or sending a CS keyword → CS handler
     *   2. Everything else → standard AI sales handler
     */
    private function handleHybridIncomingMessage(array $webhookData, WhatsappInstance $instance): \Illuminate\Http\JsonResponse
    {
        try {
            if (! empty($webhookData['fromMe'])) {
                return response()->json(['success' => true, 'message' => 'Self message ignored']);
            }

            $user = $instance->user;
            if (! $user) {
                return $this->handleIncomingMessageWithInstance($webhookData, $instance);
            }

            $messageData = $this->extractMessageData($webhookData, $instance);

            // Silently drop messages from contacts the business owner chose to ignore
            if ($this->isContactIgnored($instance, $messageData['phone_number'] ?? '')) {
                Log::info('[Hybrid] Ignored contact silently skipped', [
                    'phone'       => $messageData['phone_number'] ?? '',
                    'instance_id' => $instance->id,
                ]);
                return response()->json(['success' => true, 'message' => 'Contact ignored']);
            }

            $body        = trim($messageData['message_body'] ?? '');

            // Detect if the sender is the instance owner
            $senderPhone = preg_replace('/[^0-9]/', '', $messageData['phone_number'] ?? '');
            $ownerPhone  = preg_replace('/[^0-9]/', '', $user->phone ?? '');
            $isOwner     = $ownerPhone !== '' && str_ends_with($senderPhone, substr($ownerPhone, -9));

            if ($isOwner) {
                $hasSession = \App\Models\CsConversationSession::findActive($user->id) !== null;
                $csKeywords = ['upgrade', 'help', 'report', 'pause', 'buy credits', 'bei', 'package', 'lipa', 'pay', 'price', 'how much', 'nunua credits', 'gharama'];
                $lowerBody  = mb_strtolower($body);
                $isKeyword  = false;
                foreach ($csKeywords as $kw) {
                    if (str_contains($lowerBody, $kw)) {
                        $isKeyword = true;
                        break;
                    }
                }

                if ($hasSession || $isKeyword) {
                    app(\App\Services\CustomerSuccess\CsConversationHandler::class)
                        ->handleInbound($user, $body ?: 'help', $webhookData, $instance);
                    return response()->json(['success' => true]);
                }
            }

            // Fall through to AI sales handler
            return $this->handleIncomingMessageWithInstance($webhookData, $instance);
        } catch (\Throwable $e) {
            Log::error('[CS] handleHybridIncomingMessage error', [
                'instance_id' => $instance->id,
                'error'       => $e->getMessage(),
            ]);
            return $this->handleIncomingMessageWithInstance($webhookData, $instance);
        }
    }

    /**
     * Handle connection ready from webhook
     */
    private function handleConnectionReady($webhookData, $instance)
    {
        // Determine whether this is the very first successful connection
        // before we overwrite connected_at with the current timestamp.
        $isFirstConnection = $instance->connected_at === null;

        $instance->update([
            'status'       => 'connected',
            'connected_at' => now(),
        ]);

        // Re-queue any messages that failed because this instance was disconnected
        \Artisan::queue('messages:retry-failed', [
            '--reason' => 'instance_disconnected',
            '--user'   => (string) $instance->user_id,
            '--limit'  => '100',
        ]);

        // Create default AI sales agent if none exists
        if ($instance->user && !$instance->user->aiSalesAgents()->exists()) {
            $this->createDefaultAiAgent($instance->user);
        }

        // Fire CS onboarding event so the welcome message can be queued
        \App\Events\WhatsappInstanceConnected::dispatch($instance, $isFirstConnection);

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
                'whatsapp_instance_id' => $instance->id, // New field for multi-instance support
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
        return $this->createSessionLegacy($request);
        try {
            $user = Auth::user();
            
            $validated = $request->validate([
                'phone_number' => 'required|string',
                'instance_name' => 'nullable|string',
                'auth_method' => 'nullable|string|in:qr', // Only QR supported
                'webhook_events' => 'nullable|array',
                'webhook_events.*' => 'string|in:messages.received,session.status,messages.update',
                'webhook_url' => 'nullable|url'
            ]);

            $phoneNumber = $validated['phone_number'];
            $instanceName = $validated['instance_name'] ?? 'WhatsApp_' . time();
            $authMethod = $validated['auth_method'] ?? 'qr';
            
            // Validate webhook events with correct event names
            $webhookEvents = $validated['webhook_events'] ?? [
                'messages.received',
                'session.status', 
                'messages.update'
            ];

            $webhookUrl = $validated['webhook_url'] ?? url("/api/wasender/webhook/user_instance_id");

            $sessionId = 'session_' . uniqid() . '_' . time();
            
            Log::info('Starting WhatsApp session creation', [
                'user_id' => $user->id,
                'phone_number' => $phoneNumber,
                'session_id' => $sessionId,
                'auth_method' => $authMethod
            ]);
            
            // Create or update WhatsApp instance
            $instance = WhatsappInstance::updateOrCreate(
                ['user_id' => $user->id, 'phone_number' => $phoneNumber],
                [
                    'instance_name' => $instanceName,
                    'instance_id' => $sessionId,
                    'connect_status' => 'connecting',
                    'status' => 'pending',
                    'webhook_url' => $webhookUrl,
                    'webhook_events' => json_encode($webhookEvents),
                    'platform' => 'wasender',
                    'metadata' => json_encode([
                        'session_data' => compact('sessionId', 'phoneNumber', 'authMethod'),
                        'auth_method' => 'qr'
                    ])
                ]
            );

            // Use UnifiedNotificationService to create session via notifications.shulesoft.africa
            $unifiedService = app(\App\Services\UnifiedNotificationService::class);
            $sessionResponse = $unifiedService->createSession([
                'schema_name' => $user->uuid,
                'name' => $user->name,
                'phone_number' => $phoneNumber,
                'instance_name' => $instanceName,
                'account_protection' => true,
                'log_messages' => true,
                'read_incoming_messages' => false,
                'webhook_url' => $webhookUrl,
                'webhook_enabled' => true,
                'webhook_events' => $webhookEvents
            ]);

            if (isset($sessionResponse['success']) && $sessionResponse['success']) {
                // Check if session was created in mock mode due to authentication issues
                $isMockSession = isset($sessionResponse['data']['is_mock']) && $sessionResponse['data']['is_mock'];
                
                // Update instance with session response data
                if (isset($sessionResponse['data'])) {
                    $responseData = $sessionResponse['data'];
                    $instance->update([
                        'instance_id' => $responseData['wasender_session_id'] ?? $sessionId,
                        'connect_status' => $responseData['status'] ?? 'connecting',
                        'api_key' => $responseData['api_key'] ?? null,
                        'metadata' => json_encode([
                            'session_data' => $responseData,
                            'auth_method' => 'qr',
                            'is_mock' => $isMockSession
                        ])
                    ]);
                    $sessionId = $responseData['wasender_session_id'] ?? $sessionId;
                }
                
                // Generate QR code using the unified service
                $qrResponse = $unifiedService->getQRCode($sessionId);
                
                if (isset($qrResponse['success']) && $qrResponse['success']) {
                    $qrCodeForFrontend = $qrResponse['qr_code'];
                    
                    // Add data:image/png;base64, prefix if not present and not a URL
                    if (!str_starts_with($qrCodeForFrontend, 'data:image/') && !filter_var($qrCodeForFrontend, FILTER_VALIDATE_URL)) {
                        $qrCodeForFrontend = 'data:image/png;base64,' . $qrCodeForFrontend;
                    }
                    
                    if (isset($qrResponse['is_mock']) && $qrResponse['is_mock']) {
                        Log::info('Using mock QR code for session', ['session_id' => $sessionId]);
                    }
                } else {
                    // Fallback to mock QR generation
                    Log::warning('QR generation failed, using mock', ['session_id' => $sessionId]);
                    $qrCodeForFrontend = 'data:image/png;base64,' . base64_encode('mock_qr_code');
                }

                // Create default AI Sales Agent for this user if not exists
                $this->createDefaultAiAgent($user);

                $response = [
                    'success' => true,
                    'message' => 'QR code generated successfully. Scan with WhatsApp to connect.',
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id,
                    'qr_code' => $qrCodeForFrontend,
                    'auth_method' => 'qr',
                    'status' => 'NEED_SCAN',
                    'phone_number' => $phoneNumber,
                    'service_endpoint' => 'notifications.shulesoft.africa',
                    // Debug information
                    'debug' => [
                        'api_configured' => !empty(config('services.unified_notification.token')),
                        'qr_code_type' => filter_var($qrCodeForFrontend, FILTER_VALIDATE_URL) ? 'url' : 'base64',
                        'is_mock' => isset($qrResponse['is_mock']) ? $qrResponse['is_mock'] : $isMockSession,
                        'session_mock' => $isMockSession
                    ]
                ];
                
                Log::info('WhatsApp session created successfully', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'instance_id' => $instance->id,
                    'qr_generated' => isset($qrResponse['success']) ? $qrResponse['success'] : false
                ]);
                
                return response()->json($response);
            } else {
                throw new \Exception('Session creation failed: ' . ($sessionResponse['message'] ?? 'Unknown error'));
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Session creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'phone_number' => $request->get('phone_number')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create WhatsApp session: ' . $e->getMessage()
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
                    'created_at' => $instance->created_at ? $instance->created_at->toISOString() : now()->toISOString(),
                    'updated_at' => $instance->updated_at ? $instance->updated_at->toISOString() : now()->toISOString(),
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
                    'created_at' => $instance->created_at ? $instance->created_at->toISOString() : now()->toISOString(),
                    'updated_at' => $instance->updated_at ? $instance->updated_at->toISOString() : now()->toISOString(),
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
            
            Log::info('Requesting QR code via unified service', [
                'instance_id' => $id,
                'session_id' => $instance->instance_id
            ]);
            
            $result = $unifiedService->getQRCode($instance->instance_id);
            
            // Update instance with latest QR code if successful
            if ($result['success'] ?? false) {
                $instance->update([
                    'qr_code' => $result['qr_code'] ?? null,
                    'qr_code_generated_at' => now(),
                    'status' => $result['status'] ?? $instance->status
                ]);
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error getting QR code via unified service', [
                'instance_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve QR code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh or retrieve QR code for a session (legacy route compatibility)
     */
    public function sessionQr(Request $request, $sessionId)
    {
        try {
            $user = Auth::user();

            // Try to find by wasender session id first, scoped to the user
            $instance = WhatsappInstance::where('instance_id', $sessionId)
                ->where('user_id', $user->id)
                ->first();

            // If not found, try to find by local DB id
            if (!$instance) {
                $instance = WhatsappInstance::find($sessionId);
                if ($instance && $instance->user_id !== $user->id) {
                    return response()->json(['success' => false, 'message' => 'Instance not found'], 404);
                }
            }

            if (!$instance) {
                return response()->json(['success' => false, 'message' => 'Instance not found'], 404);
            }

            Log::info('Session QR requested', ['session_id' => $sessionId, 'instance_id' => $instance->id]);

            $qrCode = $this->generateQRCode($instance->instance_id);

            if ($qrCode === 'QR_GENERATION_FAILED') {
                return response()->json(['success' => false, 'message' => 'Failed to generate QR code'], 500);
            }

            // Update instance record (store as JSON if it's an array)
            $qrCodeToStore = is_array($qrCode) ? json_encode($qrCode) : $qrCode;
            
            $instance->update([
                'qr_code' => $qrCodeToStore,
                'qr_code_generated_at' => now(),
                'status' => $instance->status
            ]);

            // Prepare response with QR code data
            $qrCodeData = is_array($qrCode) ? $qrCode : ['type' => 'base64', 'data' => $qrCode];

            return response()->json([
                'success' => true,
                'session_id' => $instance->instance_id,
                'instance_id' => $instance->id,
                'qr_code' => $qrCodeData['data'],
                'qr_code_type' => $qrCodeData['type']
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating session QR', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error generating QR code'], 500);
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

            $freshInstance = $instance->fresh();
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session updated successfully',
                'data' => [
                    'id' => $instance->id,
                    'schema_name' => $instance->user->uuid ?? $instance->user_id,
                    'name' => $instance->instance_name,
                    'phone_number' => $instance->phone_number,
                    'webhook_enabled' => !empty($instance->webhook_url),
                    'updated_at' => ($freshInstance && $freshInstance->updated_at) ? $freshInstance->updated_at->toISOString() : now()->toISOString(),
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
     * Test QR code generation from unified notification service
     */
    public function testQRGeneration(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'session_id is required'
                ], 400);
            }

            Log::info('Testing QR code generation', ['session_id' => $sessionId]);
            
            // Test direct unified service call
            $unifiedService = app(\App\Services\UnifiedNotificationService::class);
            $result = $unifiedService->getQRCode($sessionId);
            
            return response()->json([
                'success' => true,
                'test_result' => $result,
                'timestamp' => now()->toISOString(),
                'service_endpoint' => 'notifications.shulesoft.africa'
            ]);
            
        } catch (\Exception $e) {
            Log::error('QR generation test failed', [
                'session_id' => $request->get('session_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'QR generation test failed',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString()
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
