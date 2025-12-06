<?php

namespace App\Services;

use App\Models\WhatsappInstance;
use App\Models\OutgoingMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
     * @param string|null $instanceId Optional instance ID (mapped to schema_name)
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendMessage(string $phoneNumber, string $message, array $options = [], ?string $instanceId = null, ?int $userId = null): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        $schemaName = $this->resolveSchemaName($userId, $instanceId);

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
            ], $userId, $instanceId, 'failed');

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
        return $this->sendMessage($phoneNumber, $caption ?? 'Image attachment', [
            'type' => 'media',
            'attachment_path' => $imageUrl,
            'attachment_type' => 'image/jpeg'
        ], $instanceId, $userId);
    }

    /**
     * Send a document (backward compatibility)
     */
    public function sendDocument(string $phoneNumber, string $documentUrl, ?string $filename = null, ?string $caption = null, ?string $instanceId = null, ?int $userId = null): array
    {
        return $this->sendMessage($phoneNumber, $caption ?? 'Document attachment', [
            'type' => 'media',
            'attachment_path' => $documentUrl,
            'attachment_type' => 'application/pdf',
            'attachment_name' => $filename
        ], $instanceId, $userId);
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
     * Resolve schema name from user ID or instance ID
     * 
     * @param int|null $userId User ID
     * @param string|null $instanceId Instance ID
     * @return string Schema name for the API
     */
    protected function resolveSchemaName(?int $userId, ?string $instanceId = null): string
    {
        // Try to get user UUID from user ID
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->uuid) {
                return $user->uuid;
            }
            // Fallback to user ID as string
            return (string) $userId;
        }
        
        // Try to extract from instance ID (if it's a user-based instance)
        if ($instanceId) {
            $instance = WhatsappInstance::where('instance_id', $instanceId)->first();
            if ($instance && $instance->user_id) {
                $user = User::find($instance->user_id);
                if ($user && $user->uuid) {
                    return $user->uuid;
                }
                return (string) $instance->user_id;
            }
        }
        
        // Fallback to default schema (you may want to configure this)
        return config('notifications.defaults.schema_name', 'shulesoft');
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
        
     
        // If phone number starts with +, return as is
        if (str_starts_with($phoneNumber, '+')) {
            $whatsappJid = ltrim($cleaned, '+');
            
            Log::debug('Phone number formatted to WhatsApp JID', [
            'original' => $originalPhone,
            'formatted' => $whatsappJid
            ]);
            
            return $whatsappJid;
        }
        
        // If starts with 0, remove it and add country code
        if (str_starts_with($cleaned, '0')) {
            $cleaned = $countryCode . substr($cleaned, 1);
        }
        // If doesn't start with country code, add it
        elseif (!str_starts_with($cleaned, $countryCode)) {
            $cleaned = $countryCode . $cleaned;
        }
        
        // Return as WhatsApp JID format
        $whatsappJid = $cleaned;
        
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
    protected function logOutgoingMessage(string $phoneNumber, string $message, string $messageType, array $apiResponse, ?int $userId, ?string $instanceId, string $status = 'sent'): void
    {
        try {
            OutgoingMessage::create([
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'message_body' => $message,
                'message_type' => $messageType,
                'status' => $status,
                'instance_id' => $instanceId,
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
}
