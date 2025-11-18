<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\OfficialWhatsAppService;
use App\Jobs\SendOfficialWhatsAppMessage;
use App\Models\OfficialWhatsappCredential;

class OfficialWhatsAppApiController extends Controller
{
    /**
     * Send a text message
     */
    public function sendText(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'credential_id' => 'sometimes|exists:official_whatsapp_credentials,id',
                'to' => 'required|string',
                'text' => 'required|string|max:4096',
                'preview_url' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $credentialId = $request->input('credential_id');
            
            // Get user's active credential if not specified
            if (!$credentialId) {
                $credential = OfficialWhatsappCredential::forUser($user->id)->active()->first();
                if (!$credential) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active WhatsApp credential found'
                    ], 404);
                }
                $credentialId = $credential->id;
            }

            // Queue the message
            $job = SendOfficialWhatsAppMessage::sendText(
                $user->id,
                $credentialId,
                $request->input('to'),
                $request->input('text'),
                [
                    'preview_url' => $request->input('preview_url', false)
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Message queued for sending',
                'job_id' => $job->getJobId()
            ]);

        } catch (\Exception $e) {
            Log::error('API: Failed to queue text message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a template message
     */
    public function sendTemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'credential_id' => 'sometimes|exists:official_whatsapp_credentials,id',
                'to' => 'required|string',
                'template_name' => 'required|string',
                'language_code' => 'sometimes|string|size:2',
                'components' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $credentialId = $this->getCredentialId($request, $user);

            $job = SendOfficialWhatsAppMessage::sendTemplate(
                $user->id,
                $credentialId,
                $request->input('to'),
                $request->input('template_name'),
                $request->input('components', []),
                $request->input('language_code', 'en')
            );

            return response()->json([
                'success' => true,
                'message' => 'Template message queued for sending',
                'job_id' => $job->getJobId()
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to queue template message: ' . $e->getMessage());
        }
    }

    /**
     * Send an image message
     */
    public function sendImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'credential_id' => 'sometimes|exists:official_whatsapp_credentials,id',
                'to' => 'required|string',
                'image_url' => 'required|url',
                'caption' => 'sometimes|string|max:1024'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $credentialId = $this->getCredentialId($request, $user);

            $job = SendOfficialWhatsAppMessage::sendImage(
                $user->id,
                $credentialId,
                $request->input('to'),
                $request->input('image_url'),
                $request->input('caption')
            );

            return response()->json([
                'success' => true,
                'message' => 'Image message queued for sending',
                'job_id' => $job->getJobId()
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to queue image message: ' . $e->getMessage());
        }
    }

    /**
     * Send interactive buttons message
     */
    public function sendButtons(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'credential_id' => 'sometimes|exists:official_whatsapp_credentials,id',
                'to' => 'required|string',
                'body_text' => 'required|string|max:1024',
                'buttons' => 'required|array|min:1|max:3',
                'buttons.*.id' => 'required|string',
                'buttons.*.title' => 'required|string|max:20',
                'header_text' => 'sometimes|string|max:60',
                'footer_text' => 'sometimes|string|max:60'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $credentialId = $this->getCredentialId($request, $user);

            $job = SendOfficialWhatsAppMessage::sendButtons(
                $user->id,
                $credentialId,
                $request->input('to'),
                $request->input('body_text'),
                $request->input('buttons'),
                $request->input('header_text'),
                $request->input('footer_text')
            );

            return response()->json([
                'success' => true,
                'message' => 'Interactive button message queued for sending',
                'job_id' => $job->getJobId()
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to queue button message: ' . $e->getMessage());
        }
    }

    /**
     * Send interactive list message
     */
    public function sendList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'credential_id' => 'sometimes|exists:official_whatsapp_credentials,id',
                'to' => 'required|string',
                'body_text' => 'required|string|max:1024',
                'sections' => 'required|array|min:1|max:10',
                'sections.*.title' => 'required|string|max:24',
                'sections.*.rows' => 'required|array|min:1|max:10',
                'sections.*.rows.*.id' => 'required|string',
                'sections.*.rows.*.title' => 'required|string|max:24',
                'sections.*.rows.*.description' => 'sometimes|string|max:72',
                'button_text' => 'sometimes|string|max:20',
                'header_text' => 'sometimes|string|max:60',
                'footer_text' => 'sometimes|string|max:60'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $credentialId = $this->getCredentialId($request, $user);

            $job = SendOfficialWhatsAppMessage::sendList(
                $user->id,
                $credentialId,
                $request->input('to'),
                $request->input('body_text'),
                $request->input('sections'),
                $request->input('button_text', 'Choose Option'),
                $request->input('header_text'),
                $request->input('footer_text')
            );

            return response()->json([
                'success' => true,
                'message' => 'Interactive list message queued for sending',
                'job_id' => $job->getJobId()
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to queue list message: ' . $e->getMessage());
        }
    }

    /**
     * Send immediately (not queued) - for urgent messages
     */
    public function sendImmediate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'credential_id' => 'sometimes|exists:official_whatsapp_credentials,id',
                'to' => 'required|string',
                'type' => 'required|in:text,template',
                'data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $credentialId = $this->getCredentialId($request, $user);
            
            $credential = OfficialWhatsappCredential::forUser($user->id)
                ->findOrFail($credentialId);

            $service = new OfficialWhatsAppService($credential);
            
            $type = $request->input('type');
            $data = $request->input('data');
            $to = $request->input('to');

            $result = null;
            switch ($type) {
                case 'text':
                    $result = $service->sendTextMessage($to, $data['text'], $data['options'] ?? []);
                    break;
                case 'template':
                    $result = $service->sendTemplateMessage(
                        $to,
                        $data['template_name'],
                        $data['components'] ?? [],
                        $data['language_code'] ?? 'en'
                    );
                    break;
                default:
                    throw new \Exception('Unsupported message type for immediate sending');
            }

            return response()->json([
                'success' => true,
                'message' => 'Message sent immediately',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send immediate message: ' . $e->getMessage());
        }
    }

    /**
     * Get message sending statistics
     */
    public function getStats(Request $request)
    {
        try {
            $user = auth()->user();
            $credentialId = $request->input('credential_id');
            
            $query = \DB::table('outgoing_messages')
                ->where('user_id', $user->id);
                
            if ($credentialId) {
                // Filter by credential if specified
                $credential = OfficialWhatsappCredential::forUser($user->id)
                    ->findOrFail($credentialId);
                $query->where('from_number', $credential->display_phone_number);
            }

            $timeframe = $request->input('timeframe', '24h');
            $since = match ($timeframe) {
                '1h' => now()->subHour(),
                '24h' => now()->subDay(),
                '7d' => now()->subWeek(),
                '30d' => now()->subMonth(),
                default => now()->subDay()
            };

            $query->where('created_at', '>=', $since);

            $stats = [
                'total_messages' => $query->count(),
                'sent' => $query->clone()->where('status', 'sent')->count(),
                'delivered' => $query->clone()->where('status', 'delivered')->count(),
                'read' => $query->clone()->where('status', 'read')->count(),
                'failed' => $query->clone()->where('status', 'failed')->count(),
                'pending' => $query->clone()->where('status', 'pending')->count(),
            ];

            $stats['delivery_rate'] = $stats['total_messages'] > 0 
                ? round(($stats['delivered'] / $stats['total_messages']) * 100, 2) 
                : 0;

            $stats['read_rate'] = $stats['delivered'] > 0 
                ? round(($stats['read'] / $stats['delivered']) * 100, 2) 
                : 0;

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timeframe' => $timeframe
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get credential ID from request or use default
     */
    private function getCredentialId(Request $request, $user)
    {
        $credentialId = $request->input('credential_id');
        
        if (!$credentialId) {
            $credential = OfficialWhatsappCredential::forUser($user->id)->active()->first();
            if (!$credential) {
                throw new \Exception('No active WhatsApp credential found');
            }
            $credentialId = $credential->id;
        }
        
        return $credentialId;
    }

    /**
     * Return standardized error response
     */
    private function errorResponse($message, $code = 500)
    {
        Log::error('Official WhatsApp API error', [
            'message' => $message,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }
}
