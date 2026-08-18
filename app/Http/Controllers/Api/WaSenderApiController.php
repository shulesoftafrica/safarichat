<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WaSenderService;
use App\Jobs\SendWhatsAppMediaMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * WaSender API Controller
 * 
 * Provides API endpoints for sending WhatsApp messages using WaSender service
 */
class WaSenderApiController extends Controller
{
    protected $waSenderService;

    public function __construct(WaSenderService $waSenderService)
    {
        $this->waSenderService = $waSenderService;
        $this->middleware('auth:sanctum')->except(['webhook']);
    }

    /**
     * Send a text message via API
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTextMessageApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:4096',
            'instance_id' => 'nullable|string',
            'contact_name' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create conversation context for manual message
            $lead = $this->createOrUpdateLeadFromManualMessage($request);
            
            // Send the message
            $result = $this->waSenderService->sendTextMessage(
                $request->phone,
                $request->message,
                $request->instance_id,
                Auth::id()
            );

            // Create conversation record for the manual message
            $this->createManualMessageConversation($lead, $request->message);

            return response()->json([
                'success' => true,
                'message' => 'Text message sent successfully',
                'data' => $result,
                'lead_id' => $lead->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send text message via API', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send an image message
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'image_url' => 'required|url',
            'caption' => 'nullable|string|max:1024',
            'instance_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->waSenderService->sendImage(
                $request->phone,
                $request->image_url,
                $request->caption,
                $request->instance_id,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Image sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send image via API', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a document
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'document_url' => 'required|url',
            'filename' => 'nullable|string',
            'caption' => 'nullable|string|max:1024',
            'instance_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->waSenderService->sendDocument(
                $request->phone,
                $request->document_url,
                $request->filename,
                $request->caption,
                $request->instance_id,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send document via API', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send location
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'instance_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->waSenderService->sendLocation(
                $request->phone,
                $request->latitude,
                $request->longitude,
                $request->name,
                $request->address,
                $request->instance_id,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Location sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send location via API', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue a text message for later sending
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueTextMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:4096',
            'instance_id' => 'nullable|string',
            'delay_seconds' => 'nullable|integer|min:0|max:86400' // Max 24 hours
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $delay = $request->delay_seconds ?? 0;
            
            $dispatchResult = app(\App\Services\MultiChannel\OutboundOrchestratorService::class)
                ->dispatchDirect((int) Auth::id(), (string) $request->message, [
                    'to' => (string) $request->phone,
                    'channel' => 'whatsapp',
                    'source' => 'whatsapp',
                    'provider' => 'unified_api',
                    'priority' => 'normal',
                    'instance_id' => $request->instance_id,
                    'delay_seconds' => $delay,
                    'metadata' => [
                        'api_endpoint' => 'queueTextMessage',
                    ],
                ]);

            if (!($dispatchResult['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $dispatchResult['error'] ?? 'Failed to queue message'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message queued successfully',
                'data' => [
                    'delay_seconds' => $delay,
                    'scheduled_at' => now()->addSeconds($delay)->toISOString(),
                    'outgoing_message_id' => $dispatchResult['outgoing_message_id'] ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue text message via API', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue a media message for later sending
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueMediaMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'media_url' => 'required|url',
            'media_type' => 'required|in:image,document,audio,video',
            'caption' => 'nullable|string|max:1024',
            'filename' => 'nullable|string',
            'instance_id' => 'nullable|string',
            'delay_seconds' => 'nullable|integer|min:0|max:86400'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $delay = $request->delay_seconds ?? 0;
            $additionalData = [];
            
            if ($request->filename) {
                $additionalData['filename'] = $request->filename;
            }

            SendWhatsAppMediaMessage::dispatch(
                $request->phone,
                $request->media_url,
                $request->media_type,
                $request->caption,
                Auth::id(),
                $request->instance_id,
                $additionalData
            )->delay(now()->addSeconds($delay));

            return response()->json([
                'success' => true,
                'message' => 'Media message queued successfully',
                'data' => [
                    'media_type' => $request->media_type,
                    'delay_seconds' => $delay,
                    'scheduled_at' => now()->addSeconds($delay)->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue media message via API', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's WhatsApp instances
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInstances(Request $request)
    {
        try {
            $instances = \App\Models\WhatsappInstance::where('user_id', Auth::id())
                ->select(['instance_id', 'phone_number', 'status', 'connect_status', 'created_at'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'User instances retrieved successfully',
                'data' => $instances
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user instances via API', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check instance status
     * 
     * @param Request $request
     * @param string $instanceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkInstanceStatus(Request $request, string $instanceId)
    {
        try {
            $isReady = $this->waSenderService->isInstanceReady($instanceId);

            return response()->json([
                'success' => true,
                'message' => 'Instance status checked successfully',
                'data' => [
                    'instance_id' => $instanceId,
                    'is_ready' => $isReady,
                    'status' => $isReady ? 'ready' : 'not_ready'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check instance status via API', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or update lead from manual message
     */
    private function createOrUpdateLeadFromManualMessage(Request $request)
    {
        // Find or create lead for this contact
        $lead = \App\Models\Lead::firstOrCreate(
            ['phone_number' => $request->phone],
            [
                'name' => $request->contact_name ?? $this->extractNameFromPhone($request->phone),
                'user_id' => Auth::id(),
                'source' => 'manual_message',
                'status' => 'contacted',
                'last_contact_at' => now()
            ]
        );

        // Update lead if it exists but update the last contact time
        if (!$lead->wasRecentlyCreated) {
            $lead->update([
                'last_contact_at' => now(),
                'status' => 'contacted'
            ]);
        }

        return $lead;
    }

    /**
     * Create conversation record for manual message
     */
    private function createManualMessageConversation($lead, $message)
    {
        // Get user's AI sales agent for context
        $aiAgent = \App\Models\AiSalesAgent::where('user_id', Auth::id())
                                         ->where('status', 'active')
                                         ->first();

        \App\Models\Conversation::create([
            'lead_id' => $lead->id,
            'ai_sales_agent_id' => $aiAgent ? $aiAgent->id : null,
            'message' => $message,
            'message_type' => 'outbound',
            'sender_type' => 'user_manual',
            'created_at' => now()
        ]);

        Log::info('Manual message conversation created', [
            'lead_id' => $lead->id,
            'ai_agent_id' => $aiAgent ? $aiAgent->id : null,
            'phone' => $lead->phone_number
        ]);
    }

    /**
     * Extract a name from phone number as fallback
     */
    private function extractNameFromPhone($phone)
    {
        // Clean the phone number and create a simple identifier
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        return 'Contact ' . substr($cleanPhone, -4); // Use last 4 digits
    }

    /**
     * Admin — summary + recent list of failed outgoing messages.
     *
     * GET /api/admin/failed-messages
     * Optional query params: ?reason=instance_disconnected&user_id=123&limit=50
     */
    public function failedMessages(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $reason = $request->query('reason');
        $userId = $request->query('user_id');
        $limit  = min((int) ($request->query('limit', 50)), 200);

        // Grouped breakdown — how many failed per reason / retryable flag
        $breakdown = \App\Models\OutgoingMessage::where('status', 'failed')
            ->select('failure_reason', 'retryable',
                     DB::raw('COUNT(*) as total'),
                     DB::raw('MAX(created_at) as latest'),
                     DB::raw('SUM(retry_count) as total_retries'))
            ->groupBy('failure_reason', 'retryable')
            ->orderByDesc('total')
            ->get();

        // Recent individual records
        $query = \App\Models\OutgoingMessage::where('status', 'failed')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($reason) {
            $query->where('failure_reason', $reason);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $records = $query->get([
            'id', 'user_id', 'phone_number', 'message_type',
            'failure_reason', 'retryable', 'retry_count', 'max_retries',
            'last_retry_at', 'created_at',
        ]);

        return response()->json([
            'success'    => true,
            'breakdown'  => $breakdown,
            'records'    => $records,
            'filters'    => compact('reason', 'userId', 'limit'),
        ]);
    }
}