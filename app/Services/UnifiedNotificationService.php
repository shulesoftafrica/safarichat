<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\OutgoingMessage;
use App\Models\WhatsappInstance;
use App\Models\EventsGuest;

class UnifiedNotificationService
{
    protected $baseUrl;
    protected $bearerToken;

    public function __construct()
    {
        $this->baseUrl = 'https://notifications.shulesoft.africa/api';
        $this->bearerToken = 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn';
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
     * Create WaSender session
     */
    public function createSession(array $data)
    {
        try {
            $response = $this->makeApiCall('/wasender/sessions/create', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Create local WhatsappInstance record
                $instance = WhatsappInstance::createForNotificationApi([
                    'user_id' => $this->resolveUserId($data['schema_name']),
                    'wasender_session_id' => $responseData['data']['wasender_session_id'],
                    'name' => $data['name'],
                    'phone_number' => $data['phone_number'],
                    'api_key' => $responseData['data']['api_key'] ?? null,
                    'webhook_url' => $data['webhook_url'] ?? null,
                    'account_protection' => $data['account_protection'] ?? true,
                    'log_messages' => $data['log_messages'] ?? true,
                    'webhook_events' => $data['webhook_events'] ?? [],
                ]);

                return $responseData;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Session creation failed', [
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
            $response = $this->makeApiCall('/wasender/sessions/' . $sessionId . '/qrcode', [], 'GET');
            return $response->json();
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
        $url = $this->baseUrl . $endpoint;
 
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
     * Resolve user ID from schema name
     */
    protected function resolveUserId($schemaName)
    {
        // Try UUID first
        $user = \App\Models\User::where('uuid', $schemaName)->first();
        
        if (!$user && is_numeric($schemaName)) {
            // Try direct ID
            $user = \App\Models\User::find($schemaName);
        }

        if (!$user) {
            throw new \Exception("User not found for schema: {$schemaName}");
        }

        return $user->id;
    }

    /**
     * Find or create EventsGuest for notification
     */
    protected function findOrCreateGuest($schemaName, $phoneNumber)
    {
        try {
            $userId = $this->resolveUserId($schemaName);
            $guest = EventsGuest::findOrCreateForNotification($userId, $phoneNumber);
            return $guest->id;
        } catch (\Exception $e) {
            Log::warning('Could not find/create guest', [
                'schema_name' => $schemaName,
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}