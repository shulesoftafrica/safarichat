<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\OfficialWhatsappCredential;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;
use App\Jobs\ProcessIncomingMessage;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    protected $config;

    public function __construct()
    {
        $this->config = config('whatsapp.official');
    }

    /**
     * Handle webhook verification (GET request)
     */
    public function verify(Request $request)
    {
        try {
            $verifyToken = $this->config['webhook']['verify_token'];
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            Log::info('Webhook verification attempt', [
                'mode' => $mode,
                'token' => $token,
                'challenge' => $challenge
            ]);

            if ($mode === 'subscribe' && $token === $verifyToken) {
                Log::info('Webhook verification successful');
                return response($challenge, 200);
            }

            Log::warning('Webhook verification failed', [
                'expected_token' => $verifyToken,
                'received_token' => $token
            ]);

            return response('Verification failed', 403);

        } catch (\Exception $e) {
            Log::error('Webhook verification error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Handle webhook notifications (POST request)
     */
    public function handle(Request $request)
    {
        try {
            Log::info('Webhook notification received', [
                'headers' => $request->headers->all(),
                'body' => $request->getContent()
            ]);

            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('Webhook signature verification failed');
                return response('Unauthorized', 401);
            }

            $payload = $request->json()->all();

            // Process each entry in the webhook payload
            if (isset($payload['entry'])) {
                foreach ($payload['entry'] as $entry) {
                    $this->processWebhookEntry($entry);
                }
            }

            // Always return 200 to acknowledge receipt
            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Webhook handling error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_body' => $request->getContent()
            ]);

            // Still return 200 to prevent webhook retries
            return response('Error processed', 200);
        }
    }

    /**
     * Process a single webhook entry
     */
    private function processWebhookEntry($entry)
    {
        try {
            Log::info('Processing webhook entry', ['entry' => $entry]);

            // Handle WhatsApp Business Account changes
            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    $this->processChange($change);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error processing webhook entry', [
                'error' => $e->getMessage(),
                'entry' => $entry
            ]);
        }
    }

    /**
     * Process a single change notification
     */
    private function processChange($change)
    {
        try {
            $field = $change['field'] ?? null;
            $value = $change['value'] ?? [];

            Log::info('Processing webhook change', [
                'field' => $field,
                'value' => $value
            ]);

            switch ($field) {
                case 'messages':
                    $this->processMessages($value);
                    break;

                case 'message_template_status_update':
                    $this->processTemplateStatusUpdate($value);
                    break;

                case 'phone_number_name_update':
                    $this->processPhoneNumberUpdate($value);
                    break;

                case 'phone_number_quality_update':
                    $this->processQualityUpdate($value);
                    break;

                case 'account_alerts':
                    $this->processAccountAlerts($value);
                    break;

                default:
                    Log::info('Unhandled webhook field', ['field' => $field]);
            }

        } catch (\Exception $e) {
            Log::error('Error processing webhook change', [
                'error' => $e->getMessage(),
                'change' => $change
            ]);
        }
    }

    /**
     * Process incoming messages
     */
    private function processMessages($value)
    {
        try {
            $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
            $displayPhoneNumber = $value['metadata']['display_phone_number'] ?? null;

            if (!$phoneNumberId) {
                Log::warning('No phone_number_id in message webhook');
                return;
            }

            // Find the credential associated with this phone number
            $credential = OfficialWhatsappCredential::where('phone_number_id', $phoneNumberId)
                ->orWhere('display_phone_number', $displayPhoneNumber)
                ->first();

            if (!$credential) {
                Log::warning('No credential found for phone number', [
                    'phone_number_id' => $phoneNumberId,
                    'display_phone_number' => $displayPhoneNumber
                ]);
                return;
            }

            // Process incoming messages
            if (isset($value['messages'])) {
                foreach ($value['messages'] as $message) {
                    $this->processIncomingMessage($credential, $message);
                }
            }

            // Process message statuses (delivery receipts)
            if (isset($value['statuses'])) {
                foreach ($value['statuses'] as $status) {
                    $this->processMessageStatus($credential, $status);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error processing messages webhook', [
                'error' => $e->getMessage(),
                'value' => $value
            ]);
        }
    }

    /**
     * Process a single incoming message
     */
    private function processIncomingMessage($credential, $messageData)
    {
        try {
            $messageId = $messageData['id'];
            $from = $messageData['from'];
            $timestamp = $messageData['timestamp'];
            $type = $messageData['type'];

            // Check if message already exists
            $existingMessage = IncomingMessage::where('message_id', $messageId)->first();
            if ($existingMessage) {
                Log::info('Message already processed', ['message_id' => $messageId]);
                return;
            }

            // Extract message content based on type
            $content = $this->extractMessageContent($messageData, $type);

            // Create incoming message record
            $incomingMessage = IncomingMessage::create([
                'user_id' => $credential->user_id,
                'message_id' => $messageId,
                'from_number' => $from,
                'to_number' => $credential->display_phone_number,
                'message_type' => $type,
                'content' => $content['text'] ?? '',
                'media_url' => $content['media_url'] ?? null,
                'media_type' => $content['media_type'] ?? null,
                'raw_data' => json_encode($messageData),
                'webhook_received_at' => Carbon::createFromTimestamp($timestamp),
                'processed_at' => now()
            ]);

            Log::info('Incoming message processed', [
                'message_id' => $messageId,
                'from' => $from,
                'type' => $type,
                'credential_id' => $credential->id
            ]);

            // Queue message for further processing (AI responses, etc.)
            ProcessIncomingMessage::dispatch($incomingMessage->id)
                ->onQueue('messages');

        } catch (\Exception $e) {
            Log::error('Error processing incoming message', [
                'error' => $e->getMessage(),
                'message_data' => $messageData,
                'credential_id' => $credential->id
            ]);
        }
    }

    /**
     * Extract content from message based on type
     */
    private function extractMessageContent($messageData, $type)
    {
        $content = [
            'text' => '',
            'media_url' => null,
            'media_type' => null
        ];

        switch ($type) {
            case 'text':
                $content['text'] = $messageData['text']['body'] ?? '';
                break;

            case 'image':
                $content['text'] = $messageData['image']['caption'] ?? '';
                $content['media_url'] = $messageData['image']['id'] ?? null;
                $content['media_type'] = 'image';
                break;

            case 'video':
                $content['text'] = $messageData['video']['caption'] ?? '';
                $content['media_url'] = $messageData['video']['id'] ?? null;
                $content['media_type'] = 'video';
                break;

            case 'audio':
                $content['media_url'] = $messageData['audio']['id'] ?? null;
                $content['media_type'] = 'audio';
                break;

            case 'document':
                $content['text'] = $messageData['document']['caption'] ?? '';
                $content['media_url'] = $messageData['document']['id'] ?? null;
                $content['media_type'] = 'document';
                break;

            case 'location':
                $location = $messageData['location'] ?? [];
                $content['text'] = sprintf(
                    'Location: %s, %s (%s)',
                    $location['latitude'] ?? 'Unknown',
                    $location['longitude'] ?? 'Unknown',
                    $location['name'] ?? 'No name'
                );
                break;

            case 'contacts':
                $contacts = $messageData['contacts'] ?? [];
                $content['text'] = 'Contact: ' . ($contacts[0]['name']['formatted_name'] ?? 'Unknown');
                break;

            case 'interactive':
                $interactive = $messageData['interactive'] ?? [];
                if (isset($interactive['button_reply'])) {
                    $content['text'] = 'Button: ' . $interactive['button_reply']['title'];
                } elseif (isset($interactive['list_reply'])) {
                    $content['text'] = 'List: ' . $interactive['list_reply']['title'];
                }
                break;

            default:
                $content['text'] = 'Unsupported message type: ' . $type;
                Log::warning('Unsupported message type', ['type' => $type, 'data' => $messageData]);
        }

        return $content;
    }

    /**
     * Process message status updates (delivery receipts)
     */
    private function processMessageStatus($credential, $statusData)
    {
        try {
            $messageId = $statusData['id'];
            $status = $statusData['status']; // sent, delivered, read, failed
            $timestamp = $statusData['timestamp'];

            // Update outgoing message status
            $outgoingMessage = OutgoingMessage::where('message_id', $messageId)->first();
            
            if ($outgoingMessage) {
                $outgoingMessage->update([
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? Carbon::createFromTimestamp($timestamp) : null,
                    'read_at' => $status === 'read' ? Carbon::createFromTimestamp($timestamp) : null,
                    'failed_at' => $status === 'failed' ? Carbon::createFromTimestamp($timestamp) : null,
                    'updated_at' => now()
                ]);

                Log::info('Message status updated', [
                    'message_id' => $messageId,
                    'status' => $status,
                    'outgoing_message_id' => $outgoingMessage->id
                ]);
            } else {
                Log::warning('Outgoing message not found for status update', [
                    'message_id' => $messageId,
                    'status' => $status
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error processing message status', [
                'error' => $e->getMessage(),
                'status_data' => $statusData
            ]);
        }
    }

    /**
     * Process template status updates
     */
    private function processTemplateStatusUpdate($value)
    {
        Log::info('Template status update received', ['value' => $value]);
        // TODO: Implement template status tracking
    }

    /**
     * Process phone number updates
     */
    private function processPhoneNumberUpdate($value)
    {
        try {
            $phoneNumberId = $value['phone_number_id'] ?? null;
            $verifiedName = $value['verified_name'] ?? null;

            if ($phoneNumberId) {
                $credential = OfficialWhatsappCredential::where('phone_number_id', $phoneNumberId)->first();
                if ($credential) {
                    $credential->update(['verified_name' => $verifiedName]);
                    Log::info('Phone number name updated', [
                        'phone_number_id' => $phoneNumberId,
                        'verified_name' => $verifiedName
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error processing phone number update', [
                'error' => $e->getMessage(),
                'value' => $value
            ]);
        }
    }

    /**
     * Process quality rating updates
     */
    private function processQualityUpdate($value)
    {
        try {
            $phoneNumberId = $value['phone_number_id'] ?? null;
            $qualityRating = $value['quality_score'] ?? null;

            if ($phoneNumberId) {
                $credential = OfficialWhatsappCredential::where('phone_number_id', $phoneNumberId)->first();
                if ($credential) {
                    $credential->update(['quality_rating' => $qualityRating]);
                    Log::info('Quality rating updated', [
                        'phone_number_id' => $phoneNumberId,
                        'quality_rating' => $qualityRating
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error processing quality update', [
                'error' => $e->getMessage(),
                'value' => $value
            ]);
        }
    }

    /**
     * Process account alerts
     */
    private function processAccountAlerts($value)
    {
        Log::warning('Account alert received', ['value' => $value]);
        // TODO: Implement account alert handling
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(Request $request)
    {
        try {
            $signature = $request->header('X-Hub-Signature-256');
            $payload = $request->getContent();
            $appSecret = $this->config['meta']['app_secret'];

            if (!$signature || !$appSecret) {
                return false;
            }

            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
            
            return hash_equals($expectedSignature, $signature);

        } catch (\Exception $e) {
            Log::error('Signature verification error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
