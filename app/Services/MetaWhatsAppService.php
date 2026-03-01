<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Meta WhatsApp Business API Service
 * 
 * Provides clean interface for sending messages via Meta's Official WhatsApp Business API.
 * Implements automatic fallback to WaSender on failures.
 * 
 * @link https://developers.facebook.com/docs/whatsapp/cloud-api
 */
class MetaWhatsAppService
{
    protected string $baseUrl;
    protected string $phoneNumberId;
    protected string $accessToken;
    protected string $apiVersion;
    protected WaSenderService $waSenderService;
    protected bool $enableFallback;
    protected bool $logRequests;

    /**
     * Initialize Meta WhatsApp service
     */
    public function __construct(WaSenderService $waSenderService)
    {
        $this->baseUrl = rtrim(config('meta_whatsapp.base_url', 'https://graph.facebook.com'), '/');
        $this->phoneNumberId = config('meta_whatsapp.phone_number_id');
        $this->accessToken = config('meta_whatsapp.access_token');
        $this->apiVersion = config('meta_whatsapp.api_version', 'v24.0');
        $this->waSenderService = $waSenderService;
        $this->enableFallback = config('meta_whatsapp.settings.enable_fallback', true);
        $this->logRequests = config('meta_whatsapp.settings.log_requests', true);
        
        if (empty($this->accessToken)) {
            Log::warning('Meta WhatsApp access token not configured');
        }
    }

    /**
     * Send OTP template message
     * 
     * @param string $phoneNumber Phone number in international format
     * @param string $otpCode OTP code (4-8 digits)
     * @param string|null $otpCode2 Optional second OTP parameter for button
     * @return array Response with success status and data
     */
    public function sendOtpTemplate(string $phoneNumber, string $otpCode, ?string $otpCode2 = null): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        $otpCode2 = $otpCode2 ?? $otpCode;
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $cleanPhone,
            'type' => 'template',
            'template' => [
                'name' => 'otp',
                'language' => [
                    'code' => 'en'
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otpCode
                            ]
                        ]
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otpCode2
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->sendWithFallback($cleanPhone, $payload, 'otp_verification', "Your OTP: $otpCode");
    }

    /**
     * Send text message
     * 
     * @param string $phoneNumber Phone number
     * @param string $message Message content
     * @param bool $previewUrl Enable URL preview
     * @return array Response
     */
    public function sendTextMessage(string $phoneNumber, string $message, bool $previewUrl = false): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $message
            ]
        ];

        return $this->sendWithFallback($cleanPhone, $payload, 'text', $message);
    }

    /**
     * Send image with optional caption
     * 
     * @param string $phoneNumber Phone number
     * @param string $imageUrl Image URL (must be publicly accessible)
     * @param string|null $caption Optional caption
     * @return array Response
     */
    public function sendImage(string $phoneNumber, string $imageUrl, ?string $caption = null): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $cleanPhone,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl
            ]
        ];

        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        return $this->sendWithFallback($cleanPhone, $payload, 'image', $caption ?? 'Image');
    }

    /**
     * Send document (PDF, DOC, etc.)
     * 
     * @param string $phoneNumber Phone number
     * @param string $documentUrl Document URL
     * @param string $filename Filename to display
     * @param string|null $caption Optional caption
     * @return array Response
     */
    public function sendDocument(string $phoneNumber, string $documentUrl, string $filename, ?string $caption = null): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $cleanPhone,
            'type' => 'document',
            'document' => [
                'link' => $documentUrl,
                'filename' => $filename
            ]
        ];

        if ($caption) {
            $payload['document']['caption'] = $caption;
        }

        return $this->sendWithFallback($cleanPhone, $payload, 'document', $caption ?? "Document: $filename");
    }

    /**
     * Send location
     * 
     * @param string $phoneNumber Phone number
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $name Location name
     * @param string $address Location address
     * @return array Response
     */
    public function sendLocation(string $phoneNumber, float $latitude, float $longitude, string $name, string $address): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanPhone,
            'type' => 'location',
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name' => $name,
                'address' => $address
            ]
        ];

        return $this->sendWithFallback($cleanPhone, $payload, 'location', "Location: $name");
    }

    /**
     * Send template message
     * 
     * @param string $phoneNumber Phone number
     * @param string $templateName Template name (must be pre-approved)
     * @param string $languageCode Language code (en, sw, etc.)
     * @param array $components Template components (parameters, buttons)
     * @return array Response
     */
    public function sendTemplate(string $phoneNumber, string $templateName, string $languageCode = 'en', array $components = []): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $cleanPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->sendWithFallback($cleanPhone, $payload, 'template', "Template: $templateName");
    }

    /**
     * Mark message as read
     * 
     * @param string $messageId WhatsApp message ID
     * @return array Response
     */
    public function markAsRead(string $messageId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId
        ];

        try {
            $response = $this->makeApiRequest('messages', $payload);
            
            $this->logOperation('mark_as_read', [
                'message_id' => $messageId,
                'success' => $response['success'] ?? false
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logOperation('mark_as_read_error', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send typing indicator (experimental - not officially documented)
     * 
     * @param string $phoneNumber Phone number
     * @param string $status typing or stopped
     * @return array Response
     */
    public function sendTypingIndicator(string $phoneNumber, string $status = 'typing'): array
    {
        $cleanPhone = $this->formatPhoneNumber($phoneNumber);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $cleanPhone,
            'status' => $status
        ];

        try {
            return $this->makeApiRequest('messages', $payload);
        } catch (Exception $e) {
            // Typing indicator is not critical, silently fail
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send message with automatic fallback to WaSender
     * 
     * @param string $phoneNumber Formatted phone number
     * @param array $payload Meta API payload
     * @param string $messageType Message type for logging
     * @param string $fallbackMessage Fallback message for WaSender
     * @return array Response
     */
    protected function sendWithFallback(string $phoneNumber, array $payload, string $messageType, string $fallbackMessage): array
    {
        try {
            $response = $this->makeApiRequest('messages', $payload);
            
            if ($response['success'] ?? false) {
                $this->logOperation('send_' . $messageType, [
                    'phone' => $phoneNumber,
                    'success' => true,
                    'message_id' => $response['data']['messages'][0]['id'] ?? null
                ]);

                return $response;
            }

            // Meta API returned error, try fallback
            if ($this->enableFallback) {
                return $this->fallbackToWaSender($phoneNumber, $fallbackMessage, $messageType, $response);
            }

            return $response;

        } catch (Exception $e) {
            $this->logOperation('send_' . $messageType . '_error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            // Exception occurred, try fallback
            if ($this->enableFallback) {
                return $this->fallbackToWaSender($phoneNumber, $fallbackMessage, $messageType, [
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fallback to WaSender service
     * 
     * @param string $phoneNumber Phone number
     * @param string $message Message content
     * @param string $messageType Message type
     * @param array $metaResponse Original Meta response
     * @return array Response
     */
    protected function fallbackToWaSender(string $phoneNumber, string $message, string $messageType, array $metaResponse): array
    {
        $this->logOperation('fallback_to_wasender', [
            'phone' => $phoneNumber,
            'message_type' => $messageType,
            'meta_error' => $metaResponse['error'] ?? 'Unknown error'
        ]);

        try {
            $wasenderResponse = $this->waSenderService->sendMessage($phoneNumber, $message);
            
            return [
                'success' => true,
                'via' => 'wasender',
                'fallback' => true,
                'meta_error' => $metaResponse['error'] ?? null,
                'wasender_response' => $wasenderResponse
            ];
        } catch (Exception $e) {
            $this->logOperation('fallback_failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Both Meta and WaSender failed',
                'meta_error' => $metaResponse['error'] ?? null,
                'wasender_error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make API request to Meta WhatsApp
     * 
     * @param string $endpoint API endpoint (e.g., 'messages')
     * @param array $payload Request payload
     * @return array Response
     * @throws Exception
     */
    protected function makeApiRequest(string $endpoint, array $payload): array
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/{$endpoint}";
        
        if ($this->logRequests) {
            Log::channel('single')->info('Meta WhatsApp API Request', [
                'url' => $url,
                'payload' => $payload
            ]);
        }

        $response = Http::withToken($this->accessToken)
            ->timeout(config('meta_whatsapp.settings.timeout', 30))
            ->post($url, $payload);

        $result = $response->json() ?? [];

        if ($this->logRequests) {
            Log::channel('single')->info('Meta WhatsApp API Response', [
                'status' => $response->status(),
                'response' => $result
            ]);
        }

        // Check for errors
        if (!$response->successful() || isset($result['error'])) {
            $errorCode = $result['error']['code'] ?? $response->status();
            $errorMessage = $result['error']['message'] ?? 'Unknown error';
            
            // Check if error code should trigger fallback
            $fallbackCodes = config('meta_whatsapp.fallback_error_codes', []);
            $shouldFallback = in_array($errorCode, $fallbackCodes);

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'should_fallback' => $shouldFallback,
                'raw_response' => $result
            ];
        }

        return [
            'success' => true,
            'data' => $result,
            'via' => 'meta'
        ];
    }

    /**
     * Format phone number to international format
     * 
     * @param string $phoneNumber Phone number
     * @return string Formatted phone number
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Ensure it starts with +
        if (!str_starts_with($cleaned, '+')) {
            // If starts with 0, remove it and add country code
            if (str_starts_with($cleaned, '0')) {
                $cleaned = '+255' . substr($cleaned, 1);
            } else if (!str_starts_with($cleaned, '255')) {
                // If doesn't start with country code, add it
                $cleaned = '+255' . $cleaned;
            } else {
                // Already has country code, just add +
                $cleaned = '+' . $cleaned;
            }
        }
        
        return $cleaned;
    }

    /**
     * Log operation to file
     * 
     * @param string $operation Operation name
     * @param array $data Operation data
     * @return void
     */
    protected function logOperation(string $operation, array $data): void
    {
        if (!$this->logRequests) {
            return;
        }

        Log::channel('single')->info("Meta WhatsApp: {$operation}", $data);
    }

    /**
     * Check if Meta WhatsApp is properly configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && 
               !empty($this->phoneNumberId) && 
               !empty($this->baseUrl);
    }

    /**
     * Get service health status
     * 
     * @return array Health status
     */
    public function getHealthStatus(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'access_token' => !empty($this->accessToken),
            'phone_number_id' => !empty($this->phoneNumberId),
            'fallback_enabled' => $this->enableFallback,
            'api_version' => $this->apiVersion
        ];
    }
}
