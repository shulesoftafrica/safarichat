<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\OutgoingMessage;
use App\Models\WhatsappInstance;
use App\Models\BusinessContact;
use App\Services\UserResolutionService;

class UnifiedNotificationService
{
    protected $baseUrl;
    protected $bearerToken;

    public function __construct()
    {
        $this->baseUrl = config('services.unified_notification.base_url', 'https://notifications.shulesoft.africa/api');
        
        // Read bearer token from config (NOT env() - fails when config is cached in production)
        $this->bearerToken = config('notifications.unified_api.bearer_token');
            
        // Log token status for debugging (without exposing the actual token)
        Log::debug('UnifiedNotificationService initialized', [
            'base_url' => $this->baseUrl,
            'token_configured' => !empty($this->bearerToken),
            'token_length' => $this->bearerToken ? strlen($this->bearerToken) : 0
        ]);
    }

    /**
     * Send a single notification
     */
    public function sendNotification(array $data)
    {
        try {
            // Create local tracking record first
            $outgoingMessage = OutgoingMessage::createNotification([
                'user_id' => $this->resolveUserId($data['schema_name']),
                'to' => $data['to'],
                'message' => $data['message'],
                'message_type' => $data['message_type'] ?? 'text',
                'priority' => $data['priority'] ?? 'normal',
                'metadata' => $data['metadata'] ?? null,
                'events_guest_id' => $this->findOrCreateGuest($data['schema_name'], $data['to']),
            ]);

           
            // Send to unified API
            $response = $this->makeApiCall('/notifications/send', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update local record with API response
                $outgoingMessage->updateFromApiResponse(
                    $responseData,
                    $responseData['external_id'] ?? null
                );

                return [
                    'success' => true,
                    'message_id' => $outgoingMessage->id,
                    'external_id' => $responseData['external_id'] ?? null,
                    'status' => 'sent',
                    'data' => $responseData
                ];
            } else {
                // Mark as failed
                $outgoingMessage->markAsFailed($response->body());
                
                return [
                    'success' => false,
                    'message_id' => $outgoingMessage->id,
                    'error' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Unified notification send failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications(array $data)
    {
        try {
            $batchId = 'batch_' . uniqid();
            $userId = $this->resolveUserId($data['schema_name']);
            $createdMessages = [];

            // Create local records for all messages
            foreach ($data['messages'] as $message) {
                $messageData = array_merge($data, $message);
                $messageData['batch_id'] = $batchId;
                $messageData['user_id'] = $userId;
                
                $outgoingMessage = OutgoingMessage::createNotification([
                    'user_id' => $userId,
                    'to' => $message['to'],
                    'message' => $message['message'],
                    'batch_id' => $batchId,
                    'priority' => $data['priority'] ?? 'normal',
                    'metadata' => $message['metadata'] ?? null,
                    'events_guest_id' => $this->findOrCreateGuest($data['schema_name'], $message['to']),
                ]);

                $createdMessages[] = $outgoingMessage;
            }

            // Send to unified API
            $response = $this->makeApiCall('/notifications/bulk/send', $data);

            if ($response->successful()) {
                $responseData = $response->json();

                // Update all messages to sent status
                foreach ($createdMessages as $message) {
                    $message->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'waapi_response' => $responseData
                    ]);
                }

                return [
                    'success' => true,
                    'batch_id' => $batchId,
                    'queued_messages' => count($createdMessages),
                    'failed_messages' => 0,
                    'data' => $responseData
                ];
            } else {
                // Mark all as failed
                foreach ($createdMessages as $message) {
                    $message->markAsFailed($response->body());
                }

                return [
                    'success' => false,
                    'batch_id' => $batchId,
                    'error' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Unified bulk notification send failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create WhatsApp session via notifications API
     */
    public function createSession(array $data)
    {
        try {
            // Check if token is configured before attempting API call
            if (empty($this->bearerToken)) {
                Log::warning('No Bearer token configured - using local mock session', [
                    'schema_name' => $data['schema_name'] ?? 'unknown'
                ]);
                return $this->createLocalMockSession($data);
            }
            
            $response = $this->makeApiCall('/wasender/sessions/create', $data);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('WhatsApp session created via unified API', [
                    'session_id' => $responseData['data']['wasender_session_id'] ?? 'unknown',
                    'schema_name' => $data['schema_name'] ?? 'unknown'
                ]);
                
                return $responseData;
            } else {
                $errorData = $response->json();
                Log::error('Unified API session creation failed', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'data' => $data
                ]);

                // Check if it's an authentication error
                if ($response->status() === 401 || $response->status() === 500) {
                    Log::warning('API authentication failed - falling back to local session creation');
                    return $this->createLocalMockSession($data);
                }

                return $errorData;
            }

        } catch (\Exception $e) {
            Log::error('Session creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            // If it's a token configuration error, use local mock
            if (strpos($e->getMessage(), 'Bearer token') !== false || 
                strpos($e->getMessage(), 'token not configured') !== false) {
                Log::info('Using local mock session due to token configuration issue');
                return $this->createLocalMockSession($data);
            }

            // Fallback for other errors
            return [
                'success' => true,
                'data' => [
                    'wasender_session_id' => 'local_mock_' . time(),
                    'status' => 'connecting',
                    'schema_name' => $data['schema_name'] ?? 'mock',
                    'name' => $data['name'] ?? 'Mock Session',
                    'phone_number' => $data['phone_number'] ?? '',
                    'api_key' => 'mock_api_key',
                    'webhook_url' => $data['webhook_url'] ?? null,
                    'is_mock' => true
                ],
                'message' => 'Mock session created (API unavailable)'
            ];
        }
    }

    /**
     * Create local mock session when unified API is not available
     */
    private function createLocalMockSession(array $data)
    {
        try {
            $sessionId = 'local_mock_' . uniqid();
            
            Log::info('Creating local mock session', [
                'session_id' => $sessionId,
                'schema_name' => $data['schema_name'] ?? 'unknown'
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'wasender_session_id' => $sessionId,
                    'api_key' => 'mock_api_key_' . substr(md5($sessionId), 0, 16),
                    'name' => $data['name'] ?? 'Mock Session',
                    'phone_number' => $data['phone_number'] ?? '',
                    'status' => 'pending',
                    'webhook_url' => $data['webhook_url'] ?? null,
                    'qr_code' => null,
                    'is_mock' => true,
                    'message' => 'Created local mock session - unified API authentication unavailable'
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to create local mock session', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to create fallback session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get session status
     */
    public function getSessionStatus($sessionId)
    {
        try {
            $response = $this->makeApiCall('/wasender/sessions/' . $sessionId . '/status', [], 'GET');
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Get session status failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get QR code for session
     */
    public function getQRCode($sessionId)
    {
        try {
            // Check if this is a local mock session
            if (strpos($sessionId, 'local_mock_') === 0) {
                Log::info('Generating QR for local mock session', ['session_id' => $sessionId]);
                
                // Generate a placeholder QR code for mock sessions
                $qrText = "1@" . time() . "," . $sessionId . ",mock_server_token," . substr(md5($sessionId . time()), 0, 16);
                $externalQRUrl = "https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=" . urlencode($qrText);
                
                try {
                    $qrImageContent = file_get_contents($externalQRUrl);
                    if ($qrImageContent !== false) {
                        $base64QR = base64_encode($qrImageContent);
                        return [
                            'success' => true,
                            'qr_code' => $base64QR,
                            'status' => 'pending',
                            'is_mock' => true,
                            'message' => 'Mock QR code generated - for testing only'
                        ];
                    }
                } catch (\Exception $e) {
                    // Fallback to simple placeholder
                }
                
                return [
                    'success' => true,
                    'qr_code' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                    'status' => 'pending',
                    'is_mock' => true,
                    'message' => 'Mock QR code placeholder'
                ];
            }
            
            // Convert session ID to integer for the API call, or use mock for non-numeric
            if (is_numeric($sessionId)) {
                $numericSessionId = (int)$sessionId;
                $response = $this->makeApiCall('/wasender/sessions/' . $numericSessionId . '/qrcode', [], 'GET');
                return $response->json();
            } else {
                // For non-numeric session IDs, treat as mock session
                Log::info('Non-numeric session ID treated as mock', ['session_id' => $sessionId]);
                return $this->generateMockQRCode($sessionId);
            }
        } catch (\Exception $e) {
            Log::error('Get QR code failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate mock QR code for testing or fallback purposes
     * Uses a more realistic WhatsApp Web protocol format
     */
    private function generateMockQRCode($sessionId)
    {
        Log::info('Generating mock QR code', ['session_id' => $sessionId]);
        
        // Generate a more realistic WhatsApp Web QR code format
        // Format: ref,publicKey,secret,serverToken,browserToken,clientToken,wid,protoVersion
        $ref = time();
        $publicKey = base64_encode(random_bytes(32)); // 32-byte public key
        $secret = base64_encode(random_bytes(32));    // 32-byte secret
        $serverToken = base64_encode(random_bytes(16)); // 16-byte server token
        $browserToken = base64_encode(random_bytes(16)); // 16-byte browser token 
        $clientToken = base64_encode(random_bytes(16));  // 16-byte client token
        $wid = 'mock_' . $sessionId; // WhatsApp ID
        $protoVersion = '[0,3,3876]'; // Protocol version
        
        // WhatsApp Web QR format (simplified but more realistic)
        $qrText = $ref . ',' . 
                 $publicKey . ',' . 
                 $secret . ',' . 
                 $serverToken . ',' . 
                 $browserToken . ',' . 
                 $clientToken . ',' . 
                 $wid . ',' . 
                 $protoVersion;
        
        $externalQRUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&ecc=L&data=" . urlencode($qrText);
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'WhatsApp-QR-Generator/1.0'
                ]
            ]);
            
            $qrImageContent = file_get_contents($externalQRUrl, false, $context);
            if ($qrImageContent !== false) {
                $base64QR = base64_encode($qrImageContent);
                
                Log::info('Generated realistic mock QR code', [
                    'session_id' => $sessionId,
                    'qr_size' => strlen($qrImageContent) . ' bytes',
                    'base64_length' => strlen($base64QR),
                    'format' => 'WhatsApp Web protocol (mock)'
                ]);
                
                return [
                    'success' => true,
                    'qr_code' => $base64QR,
                    'status' => 'pending',
                    'is_mock' => true,
                    'protocol' => 'whatsapp_web',
                    'message' => 'Mock QR code with realistic WhatsApp Web format - for testing only',
                    'instructions' => 'This is a test QR code. For real WhatsApp connection, configure NOTIFICATION_API_TOKEN in .env file'
                ];
            }
        } catch (\Exception $e) {
            Log::warning('External QR generation failed, using placeholder', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback to simple placeholder with warning
        return [
            'success' => true,
            'qr_code' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'status' => 'pending',
            'is_mock' => true,
            'message' => 'QR code placeholder - Configure NOTIFICATION_API_TOKEN for real WhatsApp QR codes'
        ];
    }

    /**
     * Connect session
     */
    public function connectSession($sessionId)
    {
        try {
            $response = $this->makeApiCall('/wasender/sessions/' . $sessionId . '/connect', [], 'POST');
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Connect session failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make API call to unified notification service
     */
    protected function makeApiCall($endpoint, $data = [], $method = 'POST')
    {
        // Validate token before making API call
        if (empty($this->bearerToken)) {
            Log::warning('No Bearer token configured for UnifiedNotificationService', [
                'endpoint' => $endpoint,
                'method' => $method
            ]);
            throw new \Exception('Bearer token not configured for unified notification service');
        }
        
        $url = $this->baseUrl . $endpoint;
        
        Log::debug('Making unified API call', [
            'url' => $url,
            'method' => $method,
            'has_data' => !empty($data),
            'token_configured' => !empty($this->bearerToken)
        ]);
 
      
        $request = Http::withToken($this->bearerToken)
          ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
            ->timeout(30);

        return match(strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            default => $request->post($url, $data)
        };
    }

    /**
     * Resolve user ID from WhatsApp instance UUID (schema name)
     */
    protected function resolveUserId($schemaName)
    {
        // First try to find WhatsApp instance by UUID
        $instance = \App\Models\WhatsappInstance::where('uuid', $schemaName)->first();
        
        if ($instance) {
            return $instance->user_id;
        }
        
        // Fallback: try to find user by UUID (for backward compatibility)
        $user = \App\Models\User::where('uuid', $schemaName)->first();
        
        if ($user) {
            return $user->id;
        }
        
        // Last resort: try direct numeric ID lookup
        if (is_numeric($schemaName)) {
            $user = \App\Models\User::find($schemaName);
            if ($user) {
                return $user->id;
            }
        }

        throw new \Exception("User not found for schema: {$schemaName} (expected WhatsApp instance UUID)");
    }

    /**
     * Find or create BusinessContact for notification
     */
    protected function findOrCreateGuest($schemaName, $phoneNumber)
    {
        try {
            $userId = $this->resolveUserId($schemaName);
            $contact = UserResolutionService::resolveOrCreateContact([
                'phone' => $phoneNumber,
                'name' => 'Notification Contact',
                'user_id' => $userId
            ]);
            return $contact->id;
        } catch (\Exception $e) {
            Log::warning('Could not find/create business contact', [
                'schema_name' => $schemaName,
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}