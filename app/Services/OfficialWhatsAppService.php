<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\OfficialWhatsappCredential;
use App\Models\OutgoingMessage;
use Carbon\Carbon;

class OfficialWhatsAppService
{
    protected $credential;
    protected $config;

    public function __construct(OfficialWhatsappCredential $credential)
    {
        $this->credential = $credential;
        $this->config = config('whatsapp.official');
    }

    /**
     * Send a text message
     */
    public function sendTextMessage($to, $text, $options = [])
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'text',
                'text' => [
                    'preview_url' => $options['preview_url'] ?? false,
                    'body' => $text
                ]
            ];

            return $this->sendMessage($payload, 'text', $text);

        } catch (\Exception $e) {
            Log::error('Failed to send text message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Send a template message
     */
    public function sendTemplateMessage($to, $templateName, $components = [], $languageCode = 'en')
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
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

            return $this->sendMessage($payload, 'template', "Template: {$templateName}");

        } catch (\Exception $e) {
            Log::error('Failed to send template message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'template' => $templateName,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Send an image message
     */
    public function sendImageMessage($to, $imageUrl, $caption = null)
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl
                ]
            ];

            if ($caption) {
                $payload['image']['caption'] = $caption;
            }

            return $this->sendMessage($payload, 'image', $caption ?: 'Image');

        } catch (\Exception $e) {
            Log::error('Failed to send image message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'image_url' => $imageUrl,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Send a document message
     */
    public function sendDocumentMessage($to, $documentUrl, $filename = null, $caption = null)
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'document',
                'document' => [
                    'link' => $documentUrl
                ]
            ];

            if ($filename) {
                $payload['document']['filename'] = $filename;
            }

            if ($caption) {
                $payload['document']['caption'] = $caption;
            }

            return $this->sendMessage($payload, 'document', $caption ?: "Document: {$filename}");

        } catch (\Exception $e) {
            Log::error('Failed to send document message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'document_url' => $documentUrl,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Send an interactive button message
     */
    public function sendButtonMessage($to, $bodyText, $buttons, $headerText = null, $footerText = null)
    {
        try {
            $interactive = [
                'type' => 'button',
                'body' => [
                    'text' => $bodyText
                ],
                'action' => [
                    'buttons' => []
                ]
            ];

            if ($headerText) {
                $interactive['header'] = [
                    'type' => 'text',
                    'text' => $headerText
                ];
            }

            if ($footerText) {
                $interactive['footer'] = [
                    'text' => $footerText
                ];
            }

            // Format buttons (max 3 buttons)
            foreach (array_slice($buttons, 0, 3) as $index => $button) {
                $interactive['action']['buttons'][] = [
                    'type' => 'reply',
                    'reply' => [
                        'id' => $button['id'] ?? "button_{$index}",
                        'title' => substr($button['title'], 0, 20) // Max 20 chars
                    ]
                ];
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'interactive',
                'interactive' => $interactive
            ];

            return $this->sendMessage($payload, 'interactive', "Interactive: {$bodyText}");

        } catch (\Exception $e) {
            Log::error('Failed to send button message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Send an interactive list message
     */
    public function sendListMessage($to, $bodyText, $sections, $buttonText = 'Choose Option', $headerText = null, $footerText = null)
    {
        try {
            $interactive = [
                'type' => 'list',
                'body' => [
                    'text' => $bodyText
                ],
                'action' => [
                    'button' => $buttonText,
                    'sections' => []
                ]
            ];

            if ($headerText) {
                $interactive['header'] = [
                    'type' => 'text',
                    'text' => $headerText
                ];
            }

            if ($footerText) {
                $interactive['footer'] = [
                    'text' => $footerText
                ];
            }

            // Format sections
            foreach ($sections as $section) {
                $formattedSection = [
                    'title' => $section['title'],
                    'rows' => []
                ];

                foreach ($section['rows'] as $row) {
                    $formattedSection['rows'][] = [
                        'id' => $row['id'],
                        'title' => substr($row['title'], 0, 24), // Max 24 chars
                        'description' => isset($row['description']) ? substr($row['description'], 0, 72) : null // Max 72 chars
                    ];
                }

                $interactive['action']['sections'][] = $formattedSection;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'interactive',
                'interactive' => $interactive
            ];

            return $this->sendMessage($payload, 'interactive', "List: {$bodyText}");

        } catch (\Exception $e) {
            Log::error('Failed to send list message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Send location message
     */
    public function sendLocationMessage($to, $latitude, $longitude, $name = null, $address = null)
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'location',
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ];

            if ($name) {
                $payload['location']['name'] = $name;
            }

            if ($address) {
                $payload['location']['address'] = $address;
            }

            return $this->sendMessage($payload, 'location', "Location: {$latitude}, {$longitude}");

        } catch (\Exception $e) {
            Log::error('Failed to send location message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Mark message as read
     */
    public function markMessageAsRead($messageId)
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->credential->access_token,
                'Content-Type' => 'application/json'
            ])->post($this->getApiUrl() . '/messages', $payload);

            if (!$response->successful()) {
                throw new \Exception('API request failed: ' . $response->body());
            }

            Log::info('Message marked as read', [
                'message_id' => $messageId,
                'credential_id' => $this->credential->id
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Failed to mark message as read', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Get media URL from media ID
     */
    public function getMediaUrl($mediaId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->credential->access_token
            ])->get("https://graph.facebook.com/v18.0/{$mediaId}");

            if (!$response->successful()) {
                throw new \Exception('Failed to get media info: ' . $response->body());
            }

            $mediaData = $response->json();
            
            // Get the actual media file
            $mediaResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->credential->access_token
            ])->get($mediaData['url']);

            return $mediaData;

        } catch (\Exception $e) {
            Log::error('Failed to get media URL', [
                'error' => $e->getMessage(),
                'media_id' => $mediaId,
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Get phone number info
     */
    public function getPhoneNumberInfo()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->credential->access_token
            ])->get("https://graph.facebook.com/v18.0/{$this->credential->phone_number_id}", [
                'fields' => 'verified_name,display_phone_number,quality_rating,status'
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get phone number info: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Failed to get phone number info', [
                'error' => $e->getMessage(),
                'credential_id' => $this->credential->id
            ]);
            throw $e;
        }
    }

    /**
     * Core method to send any message
     */
    protected function sendMessage($payload, $messageType, $content)
    {
        try {
            // Check if credential is active
            if (!$this->credential->isActive()) {
                throw new \Exception('WhatsApp credential is not active');
            }

            // Check token expiration
            if ($this->credential->isTokenExpired()) {
                throw new \Exception('Access token has expired');
            }

            // Create outgoing message record
            $outgoingMessage = OutgoingMessage::create([
                'user_id' => $this->credential->user_id,
                'to_number' => $payload['to'],
                'from_number' => $this->credential->display_phone_number,
                'message_type' => $messageType,
                'content' => $content,
                'payload' => json_encode($payload),
                'status' => 'pending',
                'created_at' => now()
            ]);

            // Send to WhatsApp API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->credential->access_token,
                'Content-Type' => 'application/json'
            ])->post($this->getApiUrl() . '/messages', $payload);

            if (!$response->successful()) {
                $error = $response->json()['error'] ?? ['message' => $response->body()];
                throw new \Exception("API request failed: {$error['message']}");
            }

            $responseData = $response->json();
            $messageId = $responseData['messages'][0]['id'] ?? null;

            // Update outgoing message with response
            $outgoingMessage->update([
                'message_id' => $messageId,
                'status' => 'sent',
                'api_response' => json_encode($responseData),
                'sent_at' => now()
            ]);

            Log::info('Message sent successfully', [
                'message_id' => $messageId,
                'to' => $payload['to'],
                'type' => $messageType,
                'credential_id' => $this->credential->id,
                'outgoing_message_id' => $outgoingMessage->id
            ]);

            return [
                'success' => true,
                'message_id' => $messageId,
                'outgoing_message_id' => $outgoingMessage->id,
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            // Update outgoing message with error
            if (isset($outgoingMessage)) {
                $outgoingMessage->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'failed_at' => now()
                ]);
            }

            Log::error('Failed to send message', [
                'error' => $e->getMessage(),
                'payload' => $payload,
                'credential_id' => $this->credential->id
            ]);

            throw $e;
        }
    }

    /**
     * Get the API URL for sending messages
     */
    protected function getApiUrl()
    {
        return "https://graph.facebook.com/v18.0/{$this->credential->phone_number_id}";
    }

    /**
     * Format phone number for WhatsApp API
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Remove leading + if present
        if (substr($cleaned, 0, 1) === '+') {
            $cleaned = substr($cleaned, 1);
        }
        
        // Add country code if not present (assuming international format)
        if (strlen($cleaned) < 10) {
            throw new \Exception('Invalid phone number format');
        }
        
        return $cleaned;
    }

    /**
     * Create a service instance for a user's credential
     */
    public static function forUser($userId, $credentialId = null)
    {
        $query = OfficialWhatsappCredential::forUser($userId)->active();
        
        if ($credentialId) {
            $credential = $query->findOrFail($credentialId);
        } else {
            $credential = $query->first();
        }
        
        if (!$credential) {
            throw new \Exception('No active WhatsApp credential found for user');
        }
        
        return new self($credential);
    }
}
