<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\EventsGuest;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CrmSyncApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Sync contacts from external CRM
     * 
     * POST /api/crm/sync/contacts
     */
    public function syncContacts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contacts' => 'required|array|min:1|max:500',
                'contacts.*.external_id' => 'required|string',
                'contacts.*.name' => 'required|string|max:255',
                'contacts.*.phone' => 'required|string|max:20',
                'contacts.*.email' => 'nullable|email',
                'contacts.*.company' => 'nullable|string|max:255',
                'contacts.*.metadata' => 'nullable|array',
                'sync_mode' => 'nullable|in:create_only,update_only,upsert'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $syncMode = $request->get('sync_mode', 'upsert');
            $created = [];
            $updated = [];
            $errors = [];
            $userId = Auth::id();

            DB::beginTransaction();

            foreach ($request->contacts as $index => $contactData) {
                try {
                    $externalId = $contactData['external_id'];
                    
                    // Check if contact exists (by external_id in metadata or phone)
                    $existing = EventsGuest::where('user_id', $userId)
                        ->where(function($q) use ($externalId, $contactData) {
                            $q->where('guest_phone', $contactData['phone'])
                              ->orWhereRaw("JSON_EXTRACT(metadata, '$.external_id') = ?", [$externalId]);
                        })
                        ->first();

                    $metadata = $contactData['metadata'] ?? [];
                    $metadata['external_id'] = $externalId;
                    $metadata['last_sync_at'] = now()->toISOString();

                    if ($existing) {
                        if ($syncMode === 'create_only') {
                            $errors[] = "Contact at index {$index}: Already exists (external_id: {$externalId})";
                            continue;
                        }

                        $existing->update([
                            'guest_name' => $contactData['name'],
                            'guest_phone' => $contactData['phone'],
                            'guest_email' => $contactData['email'] ?? $existing->guest_email,
                            'metadata' => $metadata
                        ]);

                        $updated[] = [
                            'internal_id' => $existing->id,
                            'external_id' => $externalId,
                            'name' => $contactData['name']
                        ];

                    } else {
                        if ($syncMode === 'update_only') {
                            $errors[] = "Contact at index {$index}: Not found (external_id: {$externalId})";
                            continue;
                        }

                        $contact = EventsGuest::create([
                            'user_id' => $userId,
                            'guest_name' => $contactData['name'],
                            'guest_phone' => $contactData['phone'],
                            'guest_email' => $contactData['email'] ?? null,
                            'metadata' => $metadata,
                            'contacted_for_sales' => false
                        ]);

                        $created[] = [
                            'internal_id' => $contact->id,
                            'external_id' => $externalId,
                            'name' => $contactData['name']
                        ];
                    }

                } catch (\Exception $e) {
                    $errors[] = "Contact at index {$index}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => $created,
                    'updated' => $updated,
                    'created_count' => count($created),
                    'updated_count' => count($updated),
                    'error_count' => count($errors),
                    'errors' => $errors
                ],
                'message' => 'Contact sync completed'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing contacts from CRM', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error syncing contacts'
            ], 500);
        }
    }

    /**
     * Sync leads to external CRM
     * 
     * POST /api/crm/sync/leads
     */
    public function syncLeads(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'since' => 'nullable|date',
                'status' => 'nullable|array',
                'status.*' => 'string',
                'include_conversations' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            $query = Lead::where('user_id', $userId)
                        ->with(['contact', 'leadProducts.product']);

            // Filter by date
            if ($request->has('since')) {
                $query->where('updated_at', '>=', Carbon::parse($request->since));
            }

            // Filter by status
            if ($request->has('status')) {
                $query->whereIn('status', $request->status);
            }

            // Include conversations if requested
            if ($request->boolean('include_conversations')) {
                $query->with('conversations');
            }

            $leads = $query->get();

            $syncData = $leads->map(function($lead) use ($request) {
                $data = [
                    'internal_id' => $lead->id,
                    'external_id' => $lead->metadata['external_id'] ?? null,
                    'contact' => [
                        'internal_id' => $lead->contact->id,
                        'external_id' => $lead->contact->metadata['external_id'] ?? null,
                        'name' => $lead->contact->guest_name,
                        'phone' => $lead->contact->guest_phone,
                        'email' => $lead->contact->guest_email
                    ],
                    'company_name' => $lead->company_name,
                    'industry' => $lead->industry,
                    'status' => $lead->status,
                    'source' => $lead->source,
                    'lead_score' => $lead->lead_score,
                    'is_churned' => $lead->is_churned,
                    'churn_date' => $lead->churn_date,
                    'churn_reason' => $lead->churn_reason,
                    'last_interaction_at' => $lead->last_interaction_at,
                    'products' => $lead->leadProducts->map(function($lp) {
                        return [
                            'product_id' => $lp->product->id,
                            'product_name' => $lp->product->name,
                            'status' => $lp->status,
                            'is_primary' => $lp->is_primary_product,
                            'quoted_price' => $lp->quoted_price,
                            'discount_applied' => $lp->discount_applied
                        ];
                    }),
                    'created_at' => $lead->created_at,
                    'updated_at' => $lead->updated_at
                ];

                if ($request->boolean('include_conversations')) {
                    $data['conversations'] = $lead->conversations->map(function($conv) {
                        return [
                            'id' => $conv->id,
                            'message_type' => $conv->message_type,
                            'message_content' => $conv->message_content,
                            'conversation_state' => $conv->conversation_state,
                            'sentiment' => $conv->sentiment,
                            'confidence_score' => $conv->confidence_score,
                            'created_at' => $conv->created_at
                        ];
                    });
                }

                return $data;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'leads' => $syncData,
                    'total_count' => $syncData->count(),
                    'sync_timestamp' => now()->toISOString()
                ],
                'message' => 'Leads data prepared for sync'
            ]);

        } catch (\Exception $e) {
            Log::error('Error preparing leads for sync', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error preparing leads for sync'
            ], 500);
        }
    }

    /**
     * Get sync status
     * 
     * GET /api/crm/sync/status
     */
    public function getSyncStatus(): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Get last sync info from metadata
            $lastContactSync = EventsGuest::where('user_id', $userId)
                ->whereNotNull('metadata->last_sync_at')
                ->orderBy(DB::raw("JSON_EXTRACT(metadata, '$.last_sync_at')"), 'desc')
                ->first();

            $lastLeadSync = Lead::where('user_id', $userId)
                ->whereNotNull('metadata->last_sync_at')
                ->orderBy(DB::raw("JSON_EXTRACT(metadata, '$.last_sync_at')"), 'desc')
                ->first();

            // Count syncable items
            $syncableContacts = EventsGuest::where('user_id', $userId)
                ->whereNotNull('metadata->external_id')
                ->count();

            $syncableLeads = Lead::where('user_id', $userId)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'last_contact_sync' => $lastContactSync ? 
                        $lastContactSync->metadata['last_sync_at'] : null,
                    'last_lead_sync' => $lastLeadSync ? 
                        $lastLeadSync->metadata['last_sync_at'] : null,
                    'syncable_contacts' => $syncableContacts,
                    'total_leads' => $syncableLeads,
                    'sync_enabled' => true
                ],
                'message' => 'Sync status retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving sync status', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sync status'
            ], 500);
        }
    }

    /**
     * Receive CRM updates via webhook
     * 
     * POST /api/crm/webhooks/updates
     */
    public function receiveUpdates(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'event_type' => 'required|in:contact.updated,contact.created,lead.updated,lead.created,lead.deleted',
                'external_id' => 'required|string',
                'data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $eventType = $request->event_type;
            $externalId = $request->external_id;
            $data = $request->data;
            $userId = Auth::id();

            switch ($eventType) {
                case 'contact.updated':
                case 'contact.created':
                    $contact = EventsGuest::where('user_id', $userId)
                        ->whereRaw("JSON_EXTRACT(metadata, '$.external_id') = ?", [$externalId])
                        ->first();

                    if ($contact) {
                        $metadata = $contact->metadata ?? [];
                        $metadata['external_id'] = $externalId;
                        $metadata['last_sync_at'] = now()->toISOString();

                        $contact->update([
                            'guest_name' => $data['name'] ?? $contact->guest_name,
                            'guest_phone' => $data['phone'] ?? $contact->guest_phone,
                            'guest_email' => $data['email'] ?? $contact->guest_email,
                            'metadata' => $metadata
                        ]);

                        $message = 'Contact updated successfully';
                    } else {
                        $contact = EventsGuest::create([
                            'user_id' => $userId,
                            'guest_name' => $data['name'],
                            'guest_phone' => $data['phone'],
                            'guest_email' => $data['email'] ?? null,
                            'metadata' => [
                                'external_id' => $externalId,
                                'last_sync_at' => now()->toISOString()
                            ]
                        ]);

                        $message = 'Contact created successfully';
                    }

                    $responseData = ['contact_id' => $contact->id];
                    break;

                case 'lead.updated':
                    $lead = Lead::where('user_id', $userId)
                        ->whereRaw("JSON_EXTRACT(metadata, '$.external_id') = ?", [$externalId])
                        ->first();

                    if ($lead) {
                        $metadata = $lead->metadata ?? [];
                        $metadata['external_id'] = $externalId;
                        $metadata['last_sync_at'] = now()->toISOString();

                        $lead->update([
                            'status' => $data['status'] ?? $lead->status,
                            'company_name' => $data['company_name'] ?? $lead->company_name,
                            'industry' => $data['industry'] ?? $lead->industry,
                            'lead_score' => $data['lead_score'] ?? $lead->lead_score,
                            'metadata' => $metadata
                        ]);

                        $message = 'Lead updated successfully';
                        $responseData = ['lead_id' => $lead->id];
                    } else {
                        $message = 'Lead not found';
                        $responseData = [];
                    }
                    break;

                default:
                    $message = 'Event type not fully implemented';
                    $responseData = [];
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing CRM webhook', [
                'error' => $e->getMessage(),
                'event_type' => $request->event_type,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook'
            ], 500);
        }
    }
}