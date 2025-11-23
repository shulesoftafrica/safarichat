<?php

namespace App\Services;

use App\Models\WhatsappInstance;
use App\Models\OutgoingMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * WaSender WhatsApp Messaging Service
 * 
 * This service handles all WhatsApp message sending operations using the WaSender API.
 * Supports text messages, media files, locations, contacts, and more.
 * 
 * @link https://wasender.co.tz/docs WaSender API Documentation
 */
class WaSenderService
{
    protected $baseUrl;
    protected $apiKey;
    protected $defaultInstanceId;

    /**
     * Initialize the WaSender service
     */
    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wasender.base_url', 'https://wasender.co.tz/api'), '/');
        $this->apiKey = config('services.wasender.api_key');
        $this->defaultInstanceId = config('services.wasender.default_instance_id');
    }

    /**
     * Send a text message via WhatsApp
     * 
     * @param string $phoneNumber Phone number in international format (e.g., 255123456789)
     * @param string $message The message content
     * @param string|null $instanceId Optional instance ID, uses default if not provided
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendTextMessage(string $phoneNumber, string $message, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp text message', [
            'phone' => $cleanPhone,
            'instance_id' => $instanceId,
            'user_id' => $userId
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("https://wasenderapi.com/api/send-message", [
                'to' => '+' . $cleanPhone,
                'text' => $message
            ]);

            $result = $response->json();

            // Log the outgoing message
            $this->logOutgoingMessage($cleanPhone, $message, 'text', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                Log::info('WhatsApp text message sent successfully', [
                    'phone' => $cleanPhone,
                    'message_id' => $result['data']['messageId'] ?? null
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? [],
                    'status' => 'sent'
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send message');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp text message', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, $message, 'text', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send an image via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param string $imageUrl URL or path to the image
     * @param string|null $caption Optional caption for the image
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendImage(string $phoneNumber, string $imageUrl, ?string $caption = null, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp image', [
            'phone' => $cleanPhone,
            'image_url' => $imageUrl,
            'instance_id' => $instanceId
        ]);

        try {
            $payload = [
                'phone' => $cleanPhone,
                'media_url' => $this->getFullMediaUrl($imageUrl),
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/image", $payload);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, $caption ?? 'Image', 'image', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send image');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp image', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, $caption ?? 'Image', 'image', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send a document/file via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param string $documentUrl URL or path to the document
     * @param string|null $filename Filename for the document
     * @param string|null $caption Optional caption
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendDocument(string $phoneNumber, string $documentUrl, ?string $filename = null, ?string $caption = null, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp document', [
            'phone' => $cleanPhone,
            'document_url' => $documentUrl,
            'instance_id' => $instanceId
        ]);

        try {
            $payload = [
                'phone' => $cleanPhone,
                'media_url' => $this->getFullMediaUrl($documentUrl),
            ];

            if ($filename) {
                $payload['filename'] = $filename;
            }

            if ($caption) {
                $payload['caption'] = $caption;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/document", $payload);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, $caption ?? 'Document', 'document', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send document');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp document', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, $caption ?? 'Document', 'document', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send an audio file via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param string $audioUrl URL or path to the audio file
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendAudio(string $phoneNumber, string $audioUrl, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp audio', [
            'phone' => $cleanPhone,
            'audio_url' => $audioUrl,
            'instance_id' => $instanceId
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/audio", [
                'phone' => $cleanPhone,
                'media_url' => $this->getFullMediaUrl($audioUrl),
            ]);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, 'Audio', 'audio', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send audio');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp audio', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, 'Audio', 'audio', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send a video via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param string $videoUrl URL or path to the video
     * @param string|null $caption Optional caption
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendVideo(string $phoneNumber, string $videoUrl, ?string $caption = null, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp video', [
            'phone' => $cleanPhone,
            'video_url' => $videoUrl,
            'instance_id' => $instanceId
        ]);

        try {
            $payload = [
                'phone' => $cleanPhone,
                'media_url' => $this->getFullMediaUrl($videoUrl),
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/video", $payload);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, $caption ?? 'Video', 'video', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send video');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp video', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, $caption ?? 'Video', 'video', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send a location via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @param string|null $name Location name
     * @param string|null $address Location address
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendLocation(string $phoneNumber, float $latitude, float $longitude, ?string $name = null, ?string $address = null, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp location', [
            'phone' => $cleanPhone,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'instance_id' => $instanceId
        ]);

        try {
            $payload = [
                'phone' => $cleanPhone,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            if ($name) {
                $payload['name'] = $name;
            }

            if ($address) {
                $payload['address'] = $address;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/location", $payload);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, 'Location: ' . ($name ?? "{$latitude},{$longitude}"), 'location', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send location');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp location', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, 'Location', 'location', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send a contact card via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param array $contactData Contact information ['name' => '', 'phone' => '', 'email' => '']
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendContact(string $phoneNumber, array $contactData, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp contact', [
            'phone' => $cleanPhone,
            'contact_name' => $contactData['name'] ?? 'Unknown',
            'instance_id' => $instanceId
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/contact", [
                'phone' => $cleanPhone,
                'contact' => $contactData
            ]);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, 'Contact: ' . ($contactData['name'] ?? 'Unknown'), 'contact', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send contact');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp contact', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, 'Contact', 'contact', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send a button message via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param string $message Message text
     * @param array $buttons Array of buttons [['id' => '1', 'text' => 'Button 1'], ...]
     * @param string|null $footer Optional footer text
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendButtonMessage(string $phoneNumber, string $message, array $buttons, ?string $footer = null, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp button message', [
            'phone' => $cleanPhone,
            'button_count' => count($buttons),
            'instance_id' => $instanceId
        ]);

        try {
            $payload = [
                'phone' => $cleanPhone,
                'message' => $message,
                'buttons' => $buttons
            ];

            if ($footer) {
                $payload['footer'] = $footer;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/button", $payload);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, $message, 'button', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send button message');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp button message', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, $message, 'button', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
    }

    /**
     * Send a list message via WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param string $message Message text
     * @param array $sections List sections
     * @param string $buttonText Button text to display
     * @param string|null $footer Optional footer text
     * @param string|null $instanceId Optional instance ID
     * @param int|null $userId User ID for tracking
     * @return array Response from the API
     * @throws Exception
     */
    public function sendListMessage(string $phoneNumber, string $message, array $sections, string $buttonText, ?string $footer = null, ?string $instanceId = null, ?int $userId = null): array
    {
        $instanceId = $instanceId ?? $this->defaultInstanceId;
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending WhatsApp list message', [
            'phone' => $cleanPhone,
            'section_count' => count($sections),
            'instance_id' => $instanceId
        ]);

        try {
            $payload = [
                'phone' => $cleanPhone,
                'message' => $message,
                'button_text' => $buttonText,
                'sections' => $sections
            ];

            if ($footer) {
                $payload['footer'] = $footer;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/instances/{$instanceId}/messages/list", $payload);

            $result = $response->json();

            $this->logOutgoingMessage($cleanPhone, $message, 'list', $result, $userId, $instanceId);

            if ($response->successful() && isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'message_id' => $result['data']['messageId'] ?? null,
                    'data' => $result['data'] ?? []
                ];
            }

            throw new Exception($result['message'] ?? 'Failed to send list message');

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp list message', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage()
            ]);

            $this->logOutgoingMessage($cleanPhone, $message, 'list', [
                'success' => false,
                'error' => $e->getMessage()
            ], $userId, $instanceId, 'failed');

            throw $e;
        }
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
     * Format phone number to international format without special characters
     * 
     * @param string $phoneNumber Phone number
     * @return string Formatted phone number
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        $originalPhone = $phoneNumber;
        
        // Remove all non-numeric characters except + at the start
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Remove @c.us suffix if present
        $cleaned = str_replace('@c.us', '', $cleaned);
        
        // Remove leading + if present
        $cleaned = ltrim($cleaned, '+');
        
        // Handle Tanzanian phone numbers
        if (str_starts_with($cleaned, '0')) {
            // Remove leading 0 and add Tanzania country code
            $cleaned = '255' . substr($cleaned, 1);
        } elseif (!str_starts_with($cleaned, '255') && strlen($cleaned) === 9) {
            // Add Tanzania country code for 9-digit numbers
            $cleaned = '255' . $cleaned;
        }
        
        // Validate that we have a proper international format
        if (!preg_match('/^255\d{9}$/', $cleaned)) {
            Log::error('Invalid phone number format', [
                'original' => $originalPhone,
                'cleaned' => $cleaned,
                'length' => strlen($cleaned)
            ]);
            throw new Exception("The to must be a valid WhatsApp JID (User, Group, or Channel format).");
        }
        
        Log::debug('Phone number formatted successfully', [
            'original' => $originalPhone,
            'formatted' => $cleaned
        ]);
        
        return $cleaned;
    }

    /**
     * Get full URL for media files
     * 
     * @param string $mediaPath Media path or URL
     * @return string Full URL
     */
    protected function getFullMediaUrl(string $mediaPath): string
    {
        // If it's already a full URL, return as is
        if (str_starts_with($mediaPath, 'http://') || str_starts_with($mediaPath, 'https://')) {
            return $mediaPath;
        }

        // If it's a storage path, generate URL
        if (Storage::exists($mediaPath)) {
            return Storage::url($mediaPath);
        }

        // If it's a public path, generate URL
        if (file_exists(public_path($mediaPath))) {
            return url($mediaPath);
        }

        // Return as is and let the API handle it
        return $mediaPath;
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
                'message' => $message,
                'message_type' => $messageType,
                'status' => $status,
                'instance_id' => $instanceId,
                'message_id' => $apiResponse['data']['messageId'] ?? null,
                'api_response' => json_encode($apiResponse),
                'sent_at' => $status === 'sent' ? now() : null,
                'delivery_status' => $status,
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
     * Check if instance is active and ready
     * 
     * @param string $instanceId Instance ID
     * @return bool
     */
    public function isInstanceReady(string $instanceId): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json'
            ])->get("{$this->baseUrl}/instances/{$instanceId}/status");

            if ($response->successful()) {
                $result = $response->json();
                return isset($result['data']['status']) && $result['data']['status'] === 'ready';
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to check instance status', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
