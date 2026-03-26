<?php

namespace App\Services;

use App\Models\WhatsappInstance;
use App\Models\OutgoingMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Auth;
/**
 * Unified Notification API Service
 * 
 * This service handles all WhatsApp message sending operations using the unified notification API.
 * Replaces the legacy WaSender API with the new notification service.
 * 
 * @link https://notifcations.shulesoft.africa/api Unified Notification API
 */
class WaSenderService
{
    protected $baseUrl;
    protected $bearerToken;

    /**
     * Initialize the unified notification service
     */
    public function __construct()
    {
        $this->baseUrl = rtrim(config('notifications.unified_api.base_url', 'https://notifications.shulesoft.africa/api'), '/');
        $this->bearerToken = config('notifications.unified_api.bearer_token', 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn');
    }

    /**
     * Send a WhatsApp message via unified notification API
     * Supports text, images, documents, audio, video with attachments
     * 
     * @param string $phoneNumber Phone number in international format
     * @param string $message The message content
     * @param array $options Additional options (attachment, type, priority, etc.)
     * @param WhatsappInstance|string|null $instance WhatsApp instance object or legacy instanceId
     * @param int|null $userId User ID for tracking (fallback)
     * @return array Response from the API
     * @throws Exception
     */
    public function sendMessage(string $phoneNumber, string $message, array $options = [], $instance = null, ?int $userId = null): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        $schemaName = $this->resolveSchemaName($userId, $instance);

        Log::info('Sending WhatsApp message via Unified API', [
            'phone' => $cleanPhone,
            'schema_name' => $schemaName,
            'user_id' => $userId,
            'has_attachment' => !empty($options['attachment_path']),
            'type' => $options['type'] ?? 'text'
        ]);

        try {
            // Base payload for unified API
            $payload = [
                'schema_name' => $schemaName,
                'channel' => 'whatsapp',
                'to' => $cleanPhone, // Already formatted as WhatsApp JID (number@s.whatsapp.net)
                'message' => $message,
                'priority' => $options['priority'] ?? 'normal',
                'type' => 'wasender'
            ];

            // Add optional fields from documentation
            // Note: 'type' field is not supported by the API, removed
            
            if (!empty($options['scheduled_at'])) {
                $payload['scheduled_at'] = $options['scheduled_at'];
            }

            if (!empty($options['template_id'])) {
                $payload['template_id'] = $options['template_id'];
            }

            if (!empty($options['template_data'])) {
                $payload['template_data'] = $options['template_data'];
            }

            if (!empty($options['metadata'])) {
                $payload['metadata'] = $options['metadata'];
            }

            if (!empty($options['webhook_url'])) {
                $payload['webhook_url'] = $options['webhook_url'];
            }

            if (!empty($options['tags'])) {
                $payload['tags'] = $options['tags'];
            }

            // Handle attachment if provided
            if (!empty($options['attachment_path'])) {
                $attachmentData = $this->prepareAttachment(
                    $options['attachment_path'], 
                    $options['attachment_type'] ?? 'document',
                    $options['attachment_name'] ?? null
                );
                
                if ($attachmentData) {
                    $payload = array_merge($payload, $attachmentData);
                }
            }

            // Send to unified API
            // DEBUG: Log payload being sent
            Log::info('Sending to unified API', [
                'url' => "{$this->baseUrl}/notifications/send",
                'payload' => $payload,
                'has_bearer_token' => !empty($this->bearerToken)
            ]);

          
            
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post("{$this->baseUrl}/notifications/send", $payload);

            $result = $response->json() ?? [];

            // Prepare payload for logging (remove large attachment data)
            $logPayload = $payload;
            if (isset($logPayload['attachment']) && strlen($logPayload['attachment']) > 1000) {
                $logPayload['attachment'] = '[BASE64_DATA_' . strlen($logPayload['attachment']) . '_BYTES]';
            }

            // Log detailed API response for debugging
            Log::debug('Unified API Response Details', [
                'phone' => $cleanPhone,
                'http_status' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $result,
                'successful' => $response->successful(),
                'payload_sent' => $logPayload
            ]);

            // Log the message
            $messageType = !empty($options['attachment_path']) ? ($options['attachment_type'] ?? 'media') : 'text';
            $instanceId = is_object($instance) ? $instance->id : $instance;
            $this->logOutgoingMessage($cleanPhone, $message, $messageType, $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                Log::info('WhatsApp message sent successfully via Unified API', [
                    'phone' => $cleanPhone,
                    'message_id' => $result['message_id'] ?? null,
                    'external_id' => $result['external_id'] ?? null
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['message_id'] ?? null,
                    'external_id' => $result['external_id'] ?? null,
                    'data' => $result['data'] ?? [],
                    'status' => $result['status'] ?? 'sent'
                ];
            }

            // Provide more detailed error information
            $errorMessage = 'Failed to send message';
            if (!$response->successful()) {
                $errorMessage .= " (HTTP {$response->status()})";
            }
            if (isset($result['message'])) {
                $errorMessage .= ": {$result['message']}";
            } elseif (isset($result['error'])) {
                $errorMessage .= ": {$result['error']}";
            } elseif (!empty($result)) {
                $errorMessage .= ": " . json_encode($result);
            }

            throw new Exception($errorMessage);

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp message via Unified API', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload ?? null,
                'base_url' => $this->baseUrl,
                'has_bearer_token' => !empty($this->bearerToken)
            ]);

            $messageType = !empty($options['attachment_path']) ? ($options['attachment_type'] ?? 'media') : 'text';
            $this->logOutgoingMessage($cleanPhone, $message, $messageType, [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instance, 'failed');

            throw $e;
        }
    }

    /**
     * Send a text message (backward compatibility)
     */
    public function sendTextMessage(string $phoneNumber, string $message, ?string $instanceId = null, ?int $userId = null): array
    {
        return $this->sendMessage($phoneNumber, $message, [], $instanceId, $userId);
    }

    /**
     * Send an image (backward compatibility)
     */
    public function sendImage(string $phoneNumber, string $imageUrl, ?string $caption = null, ?string $instanceId = null, ?int $userId = null): array
    {
        try {
            $cleanPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Get user's WhatsApp instance and API key
            $apiKey = $this->getUserApiKey($userId, $instanceId);
            if (!$apiKey) {
                throw new Exception('No active WhatsApp instance found for user');
            }
            
            Log::info('Sending image via WaSender API', [
                'phone' => $cleanPhone,
                'image_path' => $imageUrl,
                'user_id' => $userId
            ]);
            
            // Step 1: Upload the image to WaSender
            $uploadedUrl = $this->uploadMediaToWaSender($imageUrl, $apiKey);
            
            if (!$uploadedUrl) {
                throw new Exception('Failed to upload image to WaSender');
            }
            
            Log::info('Image uploaded to WaSender', [
                'phone' => $cleanPhone,
                'uploaded_url' => $uploadedUrl
            ]);
            
            // Step 2: Send the image message using WaSender API directly
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://www.wasenderapi.com/api/send-message', [
                'to' => $cleanPhone,
                'text' => $caption ?? 'Image attachment',
                'imageUrl' => $uploadedUrl
            ]);
            
            $result = $response->json() ?? [];
            
            if ($response->successful() && isset($result['success']) && $result['success']) {
                Log::info('Image message sent successfully via WaSender', [
                    'phone' => $cleanPhone,
                    'message_id' => $result['data']['msgId'] ?? null
                ]);
                
                // Log the message
                $this->logOutgoingMessage($cleanPhone, $caption ?? 'Image attachment', 'image', $result, $userId, $instanceId);
                
                return [
                    'success' => true,
                    'message_id' => $result['data']['msgId'] ?? null,
                    'jid' => $result['data']['jid'] ?? null,
                    'status' => $result['data']['status'] ?? 'sent',
                    'data' => $result['data'] ?? []
                ];
            }
            
            throw new Exception($result['message'] ?? 'Failed to send image message');
            
        } catch (Exception $e) {
            Log::error('Failed to send image via WaSender', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            
            $this->logOutgoingMessage($phoneNumber, $caption ?? 'Image attachment', 'image', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');
            
            throw $e;
        }
    }

    /**
     * Send a document (backward compatibility)
     */
    public function sendDocument(string $phoneNumber, string $documentUrl, ?string $filename = null, ?string $caption = null, ?string $instanceId = null, ?int $userId = null): array
    {
        try {
            $cleanPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Get user's WhatsApp instance and API key
            $apiKey = $this->getUserApiKey($userId, $instanceId);
            if (!$apiKey) {
                throw new Exception('No active WhatsApp instance found for user');
            }
            
            Log::info('Sending document via WaSender API', [
                'phone' => $cleanPhone,
                'document_path' => $documentUrl,
                'filename' => $filename,
                'user_id' => $userId
            ]);
            
            // Step 1: Upload the document to WaSender
            $uploadedUrl = $this->uploadMediaToWaSender($documentUrl, $apiKey);
            
            if (!$uploadedUrl) {
                throw new Exception('Failed to upload document to WaSender');
            }
            
            Log::info('Document uploaded to WaSender', [
                'phone' => $cleanPhone,
                'uploaded_url' => $uploadedUrl
            ]);
            
            // Step 2: Send the document message using WaSender API directly
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://www.wasenderapi.com/api/send-message', [
                'to' => $cleanPhone,
                'text' => $caption ?? 'Document attachment',
                'documentUrl' => $uploadedUrl
            ]);
            
            $result = $response->json() ?? [];
            
            if ($response->successful() && isset($result['success']) && $result['success']) {
                Log::info('Document message sent successfully via WaSender', [
                    'phone' => $cleanPhone,
                    'message_id' => $result['data']['msgId'] ?? null
                ]);
                
                // Log the message
                $this->logOutgoingMessage($cleanPhone, $caption ?? 'Document attachment', 'document', $result, $userId, $instanceId);
                
                return [
                    'success' => true,
                    'message_id' => $result['data']['msgId'] ?? null,
                    'jid' => $result['data']['jid'] ?? null,
                    'status' => $result['data']['status'] ?? 'sent',
                    'data' => $result['data'] ?? []
                ];
            }
            
            throw new Exception($result['message'] ?? 'Failed to send document message');
            
        } catch (Exception $e) {
            Log::error('Failed to send document via WaSender', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            
            $this->logOutgoingMessage($phoneNumber, $caption ?? 'Document attachment', 'document', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');
            
            throw $e;
        }
    }

    /**
     * Send audio (backward compatibility)
     */
    public function sendAudio(string $phoneNumber, string $audioUrl, ?string $instanceId = null, ?int $userId = null): array
    {
        return $this->sendMessage($phoneNumber, 'Audio message', [
            'type' => 'media',
            'attachment_path' => $audioUrl,
            'attachment_type' => 'audio/mpeg'
        ], $instanceId, $userId);
    }

    /**
     * Send video (backward compatibility)
     */
    public function sendVideo(string $phoneNumber, string $videoUrl, ?string $caption = null, ?string $instanceId = null, ?int $userId = null): array
    {
        return $this->sendMessage($phoneNumber, $caption ?? 'Video attachment', [
            'type' => 'media',
            'attachment_path' => $videoUrl,
            'attachment_type' => 'video/mp4'
        ], $instanceId, $userId);
    }

    /**
     * Resolve schema name for API calls using instance UUID (not user UUID)
     * 
     * @param int|null $userId User ID (fallback)
     * @param WhatsappInstance|string|null $instance WhatsApp instance or legacy instance ID
     * @return string Schema name for the API
     */
    protected function resolveSchemaName(?int $userId, $instance = null): string
    {
        // CANONICAL SOURCE OF TRUTH:
        // The Notification API schema_name is ALWAYS registered as users.uuid (from the users table).
        // whatsapp_instances.uuid is a completely separate UUID and is NOT the schema_name.
        // Registration code (WaSenderController::registerWithUnifiedNotificationApi) confirms:
        //   $schemaName = $user->uuid ?? 'user_' . $user->id;
        // ALL paths below must resolve to users.uuid.

        // Priority 1: WhatsappInstance object — load the user relation and return users.uuid
        if ($instance instanceof \App\Models\WhatsappInstance) {
            $user = $instance->relationLoaded('user') ? $instance->user : $instance->user()->first();
            if ($user && $user->uuid) {
                Log::debug('resolveSchemaName: resolved via instance object → users.uuid', [
                    'instance_id'   => $instance->instance_id,
                    'user_id'       => $user->id,
                    'resolved_uuid' => $user->uuid,
                ]);
                return $user->uuid;
            }
            // Fallback within Priority 1: use user_id
            if ($instance->user_id) {
                $userId = $instance->user_id;
            }
        }

        // Priority 2: String instance identifier — look up the instance, then its user → users.uuid
        if (is_string($instance) && !empty($instance)) {
            $whatsappInstance = WhatsappInstance::where('instance_id', $instance)
                ->orWhere('api_key', $instance)
                ->first();
            if ($whatsappInstance) {
                $user = $whatsappInstance->user()->first();
                if ($user && $user->uuid) {
                    Log::debug('resolveSchemaName: resolved via instance string lookup → users.uuid', [
                        'instance_value' => $instance,
                        'user_id'        => $user->id,
                        'resolved_uuid'  => $user->uuid,
                    ]);
                    return $user->uuid;
                }
                // Fallback: capture user_id for Priority 3
                if ($whatsappInstance->user_id && !$userId) {
                    $userId = $whatsappInstance->user_id;
                }
            }
        }

        // Priority 3: User ID — look up the user directly and return users.uuid
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user && $user->uuid) {
                Log::debug('resolveSchemaName: resolved via user_id → users.uuid', [
                    'user_id'       => $userId,
                    'resolved_uuid' => $user->uuid,
                ]);
                return $user->uuid;
            }
            // User found but has no uuid — use deterministic fallback matching registration logic
            if ($user) {
                return 'user_' . $user->id;
            }
        }

        // Final fallback — should never reach here in normal operation
        Log::warning('resolveSchemaName: could not resolve schema_name from any source', [
            'user_id'  => $userId,
            'instance' => is_string($instance) ? $instance : ($instance instanceof \App\Models\WhatsappInstance ? $instance->instance_id : null),
        ]);
        return config('notifications.defaults.schema_name', 'shulesoft');
    }

    /**
     * Get WhatsApp instance by schema name (UUID)
     * 
     * @param string $schemaName Instance UUID
     * @return WhatsappInstance|null
     */
    public function getInstanceBySchemaName(string $schemaName): ?WhatsappInstance
    {
        // schema_name is users.uuid — join through users table to find the instance
        return WhatsappInstance::whereHas('user', function ($q) use ($schemaName) {
            $q->where('uuid', $schemaName);
        })->latest('connected_at')->first();
    }

    /**
     * Prepare attachment data for unified API
     * 
     * @param string $filePath File path or URL
     * @param string $type Attachment type (image, document, audio, video)
     * @param string|null $filename Optional filename
     * @return array|null Attachment data or null if failed
     */
    protected function prepareAttachment(string $filePath, string $type, ?string $filename = null): ?array
    {
        try {
            // If it's already a URL, we can't convert to base64, so just return URL info
            if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
                return [
                    'attachment_type' => $this->getMimeType($filePath, $type),
                    'attachment_name' => $filename ?? basename($filePath)
                ];
            }

            // Check if file exists locally
            $fullPath = $this->getFullFilePath($filePath);
            if (file_exists($fullPath)) {
                // Read file content and encode to base64
                $content = file_get_contents($fullPath);
                if ($content !== false) {
                    return [
                        'attachment' => base64_encode($content),
                        'attachment_type' => $this->getMimeType($fullPath, $type),
                        'attachment_name' => $filename ?? basename($fullPath)
                    ];
                }
            }

            Log::warning('Could not prepare attachment', [
                'file_path' => $filePath,
                'type' => $type
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Failed to prepare attachment', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get full file path from relative path
     * 
     * @param string $filePath File path
     * @return string Full file path
     */
    protected function getFullFilePath(string $filePath): string
    {
        // If already absolute path, return as is
        if (str_starts_with($filePath, '/') || (strlen($filePath) > 1 && $filePath[1] === ':')) {
            return $filePath;
        }

        // Check storage paths
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->path($filePath);
        }

        if (Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->path($filePath);
        }

        // Check public path
        $publicPath = public_path($filePath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        // Return original path as fallback
        return $filePath;
    }

    /**
     * Get MIME type for file
     * 
     * @param string $filePath File path
     * @param string $type General type (image, document, audio, video)
     * @return string MIME type
     */
    protected function getMimeType(string $filePath, string $type): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            
            // Documents
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            
            // Audio
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            
            // Video
            'mp4' => 'video/mp4',
            'avi' => 'video/avi',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv'
        ];

        return $mimeTypes[$extension] ?? match($type) {
            'image' => 'image/jpeg',
            'document' => 'application/pdf',
            'audio' => 'audio/mpeg',
            'video' => 'video/mp4',
            default => 'application/octet-stream'
        };
    }

    /**
     * Get user's WhatsApp instance
     * 
     * @param int $userId User ID
     * @return WhatsappInstance|null
     */
    public function getUserInstance(int $userId): ?WhatsappInstance
    {
        return WhatsappInstance::where('user_id', $userId)
            ->where('status', 'connected')
            ->where('connect_status', 'ready')
            ->first();
    }

    /**
     * Get user's WaSender API key from WhatsApp instance
     * 
     * @param int|null $userId User ID
     * @param string|null $instanceId Optional instance ID
     * @return string|null API key or null if not found
     */
    protected function getUserApiKey(?int $userId, ?string $instanceId = null): ?string
    {
        // If userId is provided, get their instance
        if ($userId) {
            $instance = $this->getUserInstance($userId);
            if ($instance && $instance->api_key) {
                return $instance->api_key;
            }
        }
        
        // If instanceId is provided, try to find by instance_id
        if ($instanceId) {
            $instance = WhatsappInstance::where('instance_id', $instanceId)
                ->where('status', 'connected')
                ->where('connect_status', 'ready')
                ->first();
            if ($instance && $instance->api_key) {
                return $instance->api_key;
            }
        }
        
        // If authenticated user exists but no userId provided, use auth user
        if (!$userId && Auth::check()) {
            $instance = $this->getUserInstance(Auth::id());
            if ($instance && $instance->api_key) {
                return $instance->api_key;
            }
        }
        
        Log::warning('No WaSender API key found', [
            'user_id' => $userId,
            'instance_id' => $instanceId,
            'auth_user_id' => Auth::check() ? Auth::id() : null
        ]);
        
        return null;
    }

    /**
     * Send bulk WhatsApp messages via unified notification API
     * 
     * @param array $messages Array of messages [['to' => '', 'message' => ''], ...]
     * @param int|null $userId User ID for tracking
     * @param string|null $instanceId Optional instance ID
     * @param array $options Additional options (rate_limit, batch_size, etc.)
     * @return array Response from the API
     * @throws Exception
     */
    public function sendBulkMessages(array $messages, ?int $userId = null, ?string $instanceId = null, array $options = []): array
    {
        $schemaName = $this->resolveSchemaName($userId, $instanceId);

        Log::info('Sending bulk WhatsApp messages via Unified API', [
            'message_count' => count($messages),
            'schema_name' => $schemaName,
            'user_id' => $userId
        ]);

        try {
            $payload = [
                'schema_name' => $schemaName,
                'channel' => 'whatsapp',
                'priority' => $options['priority'] ?? 'normal',
                'provider' => 'unified_api',
                'rate_limit' => $options['rate_limit'] ?? 60,
                'batch_size' => $options['batch_size'] ?? 50,
                'messages' => array_map(function($msg) {
                    return [
                        'to' => $this->formatPhoneNumber($msg['to']), // Already formatted as JID
                        'message' => $msg['message'],
                        'metadata' => $msg['metadata'] ?? []
                    ];
                }, $messages)
            ];

            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post("{$this->baseUrl}/notifications/bulk/send", $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['success']) && $result['success']) {
                Log::info('Bulk WhatsApp messages queued successfully', [
                    'batch_id' => $result['batch_id'] ?? null,
                    'queued_messages' => $result['queued_messages'] ?? 0,
                    'failed_messages' => $result['failed_messages'] ?? 0
                ]);

                return [
                    'success' => true,
                    'batch_id' => $result['batch_id'] ?? null,
                    'queued_messages' => $result['queued_messages'] ?? 0,
                    'failed_messages' => $result['failed_messages'] ?? 0,
                    'data' => $result
                ];
            }

            throw new Exception($result['message'] ?? $result['error'] ?? 'Failed to send bulk messages');

        } catch (Exception $e) {
            Log::error('Failed to send bulk WhatsApp messages', [
                'message_count' => count($messages),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get message status from unified notification API
     * 
     * @param int|string $messageId Message ID
     * @return array Response from the API
     * @throws Exception
     */
    public function getMessageStatus($messageId): array
    {
        try {
            $response = Http::withToken($this->bearerToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->baseUrl}/notifications/{$messageId}");

            $result = $response->json();

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            }

            throw new Exception($result['message'] ?? $result['error'] ?? 'Failed to get message status');

        } catch (Exception $e) {
            Log::error('Failed to get message status', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * List messages from unified notification API
     * 
     * @param array $filters Filters (channel, status, from, to, recipient, per_page)
     * @return array Response from the API
     * @throws Exception
     */
    public function listMessages(array $filters = []): array
    {
        try {
            $queryParams = array_merge([
                'channel' => 'whatsapp'
            ], $filters);

            $response = Http::withToken($this->bearerToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->baseUrl}/notifications", $queryParams);

            $result = $response->json();

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'meta' => $result['meta'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? $result['error'] ?? 'Failed to list messages');

        } catch (Exception $e) {
            Log::error('Failed to list messages', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Upload media file to WaSender API
     * 
     * @param string $filePath File path (local or URL)
     * @param string $apiKey WaSender API key
     * @return string|null Uploaded file URL
     */
    protected function uploadMediaToWaSender(string $filePath, string $apiKey): ?string
    {
        try {
            $fullPath = $this->getFullFilePath($filePath);
            
            if (!file_exists($fullPath)) {
                Log::error('File not found for WaSender upload', ['path' => $filePath]);
                return null;
            }
            
            $fileSize = filesize($fullPath);
            $mimeType = mime_content_type($fullPath);
            
            Log::info('Uploading file to WaSender', [
                'file' => basename($fullPath),
                'size' => $fileSize,
                'mime' => $mimeType
            ]);
            
            // For large files (> 10MB), use binary upload method
            if ($fileSize > 10 * 1024 * 1024) {
                return $this->uploadMediaBinary($fullPath, $mimeType, $apiKey);
            }
            
            // For smaller files, use base64 method
            return $this->uploadMediaBase64($fullPath, $mimeType, $apiKey);
            
        } catch (Exception $e) {
            Log::error('Failed to upload media to WaSender', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Upload media using base64 method
     * 
     * @param string $filePath Full file path
     * @param string $mimeType MIME type
     * @param string $apiKey WaSender API key
     * @return string|null Uploaded file URL
     */
    protected function uploadMediaBase64(string $filePath, string $mimeType, string $apiKey): ?string
    {
        try {
            $fileContent = file_get_contents($filePath);
            $base64Content = base64_encode($fileContent);
            $dataUrl = "data:$mimeType;base64,$base64Content";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json'
            ])->post('https://www.wasenderapi.com/api/upload', [
                'base64' => $dataUrl
            ]);
            
            $result = $response->json() ?? [];
            
            if ($result['success'] ?? false) {
                Log::info('Media uploaded via base64', ['url' => $result['publicUrl']]);
                return $result['publicUrl'];
            }
            
            Log::error('Base64 upload failed', ['result' => $result]);
            return null;
            
        } catch (Exception $e) {
            Log::error('Base64 upload exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Upload media using binary method (for large files)
     * 
     * @param string $filePath Full file path
     * @param string $mimeType MIME type
     * @param string $apiKey WaSender API key
     * @return string|null Uploaded file URL
     */
    protected function uploadMediaBinary(string $filePath, string $mimeType, string $apiKey): ?string
    {
        try {
            $client = new Client();
            
            $response = $client->post('https://www.wasenderapi.com/api/upload', [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => $mimeType
                ],
                'body' => fopen($filePath, 'r')
            ]);
            
            $result = json_decode($response->getBody(), true);
            
            if ($result['success'] ?? false) {
                Log::info('Media uploaded via binary', ['url' => $result['publicUrl']]);
                return $result['publicUrl'];
            }
            
            Log::error('Binary upload failed', ['result' => $result]);
            return null;
            
        } catch (RequestException $e) {
            Log::error('Binary upload exception', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            return null;
        }
    }

    /**
     * Format phone number to WhatsApp JID format (number@s.whatsapp.net)
     * 
     * @param string $phoneNumber Phone number
     * @return string Formatted WhatsApp JID
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        $originalPhone = $phoneNumber;
        
        // Remove all non-numeric characters except + at the start
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Remove @c.us or @s.whatsapp.net suffix if present
        $cleaned = str_replace(['@c.us', '@s.whatsapp.net'], '', $cleaned);
        
        // Get the authenticated user's country code
        $countryCode = Auth::check() ? Auth::user()->country_code : '+255'; // Default to Tanzania if not authenticated
        $countryCodeDigits = ltrim($countryCode, '+'); // Get country code without +
        
     
        // If phone number starts with +, remove it for processing
        if (str_starts_with($cleaned, '+')) {
            $cleaned = ltrim($cleaned, '+');
        }
        
        // If starts with 0, remove it and add country code digits
        if (str_starts_with($cleaned, '0')) {
            $whatsappJid = $countryCodeDigits . substr($cleaned, 1);
        }
        // If already starts with country code digits, use as is
        elseif (str_starts_with($cleaned, $countryCodeDigits)) {
            $whatsappJid = $cleaned;
        }
        // Otherwise, add country code digits
        else {
            $whatsappJid = $countryCodeDigits . $cleaned;
        }
        
        // Always add the + sign at the beginning
        $whatsappJid = '+' . $whatsappJid;
        
        Log::debug('Phone number formatted to WhatsApp JID', [
            'original' => $originalPhone,
            'formatted' => $whatsappJid,
            'country_code' => $countryCode
        ]);
        
        return $whatsappJid;
    }

    /**
     * Log outgoing message to database
     * 
     * @param string $phoneNumber Phone number
     * @param string $message Message content
     * @param string $messageType Message type
     * @param array $apiResponse API response
     * @param int|null $userId User ID
     * @param string|null $instanceId Instance ID
     * @param string $status Message status
     * @return void
     */
    protected function logOutgoingMessage(string $phoneNumber, string $message, string $messageType, array $apiResponse, ?int $userId, $instanceId, string $status = 'sent'): void
    {
        try {
            OutgoingMessage::create([
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'message_body' => $message,
                'message_type' => $messageType,
                'status' => $status,
                'instance_id' => is_numeric($instanceId) ? (int)$instanceId : null,
                'waapi_message_id' => $apiResponse['message_id'] ?? null,
                'external_id' => $apiResponse['external_id'] ?? null,
                'waapi_response' => json_encode($apiResponse),
                'provider' => 'unified_api',
                'priority' => 'normal',
                'retry_count' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log outgoing message', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if instance/session is active using unified API
     * 
     * @param string $instanceId Instance ID (session ID for unified API)
     * @return bool
     */
    public function isInstanceReady(string $instanceId): bool
    {
        try {
            $response = Http::withToken($this->bearerToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->baseUrl}/wasender/sessions/{$instanceId}/status");

            if ($response->successful()) {
                $result = $response->json();
                return isset($result['success']) && $result['success'] && 
                       isset($result['data']['status']) && $result['data']['status'] === 'connected';
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to check instance status via Unified API', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create WhatsApp session using unified API
     * 
     * @param array $sessionData Session data
     * @param int|null $userId User ID
     * @return array Response from the API
     * @throws Exception
     */
    public function createSession(array $sessionData, ?int $userId = null): array
    {
        $schemaName = $this->resolveSchemaName($userId);

        try {
            $payload = array_merge([
                'schema_name' => $schemaName,
                'account_protection' => true,
                'log_messages' => true,
                'read_incoming_messages' => false,
                'webhook_enabled' => true,
                'webhook_events' => ['messages.received', 'session.status', 'messages.update']
            ], $sessionData);

            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post("{$this->baseUrl}/wasender/sessions/create", $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            }

            throw new Exception($result['message'] ?? $result['error'] ?? 'Failed to create session');

        } catch (Exception $e) {
            Log::error('Failed to create WhatsApp session', [
                'schema_name' => $schemaName,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get QR code for session using unified API
     * 
     * @param string $sessionId Session ID
     * @return array Response from the API
     * @throws Exception
     */
    public function getQRCode(string $sessionId): array
    {
        try {
            $response = Http::withToken($this->bearerToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->baseUrl}/wasender/sessions/{$sessionId}/qrcode");

            $result = $response->json();

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'qr_code' => $result['data']['qr_code'] ?? null
                ];
            }

            throw new Exception($result['message'] ?? $result['error'] ?? 'Failed to get QR code');

        } catch (Exception $e) {
            Log::error('Failed to get QR code', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get all contacts from WASender API
     * 
     * @param int|null $userId User ID
     * @param string|null $instanceId Optional instance ID
     * @return array Array of contacts from WASender API
     * @throws Exception
     */
    public function getContacts(?int $userId = null, ?string $instanceId = null): array
    {
        try {
            // Get user's WhatsApp instance and API key
            $apiKey = $this->getUserApiKey($userId, $instanceId);
            if (!$apiKey) {
                throw new Exception('No active WhatsApp instance found for user');
            }

            // Get the instance to retrieve instance_id (which is actually the WaSender session ID)
            $instance = null;
            if ($userId) {
                $instance = $this->getUserInstance($userId);
            } elseif ($instanceId) {
                $instance = WhatsappInstance::where('instance_id', $instanceId)->first();
            } elseif (Auth::check()) {
                $instance = $this->getUserInstance(Auth::id());
            }

            if (!$instance || !$instance->instance_id) {
                throw new Exception('No WhatsApp instance found');
            }

            Log::info('Fetching contacts from WASender API', [
                'user_id' => $userId,
                'session_id' => $instance->instance_id
            ]);

            // Call WASender API to get contacts
            // Endpoint: https://www.wasenderapi.com/api/whatsapp-sessions/{sessionId}/contacts
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ])->get("https://www.wasenderapi.com/api/contacts");

            $result = $response->json() ?? [];

            Log::debug('WASender contacts API response', [
                'http_status' => $response->status(),
                'response_body' => $result,
                'successful' => $response->successful()
            ]);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                $contacts = $result['data'] ?? [];
                
                Log::info('Contacts fetched successfully from WASender', [
                    'count' => count($contacts),
                    'session_id' => $instance->instance_id
                ]);

                return [
                    'success' => true,
                    'data' => $contacts,
                    'count' => count($contacts)
                ];
            }

            $errorMessage = $result['message'] ?? 'Failed to fetch contacts from WASender';
            throw new Exception($errorMessage);

        } catch (Exception $e) {
            Log::error('Failed to fetch contacts from WASender', [
                'user_id' => $userId,
                'instance_id' => $instanceId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
