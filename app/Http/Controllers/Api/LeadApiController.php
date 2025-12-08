<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\EventsGuest;
use App\Models\Product;
use App\Models\AiSalesAgent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeadApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Create a new lead from existing contact
     * 
     * POST /api/leads
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'events_guest_id' => 'required|exists:events_guests,id',
                'ai_sales_agent_id' => 'nullable|exists:ai_sales_agents,id',
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'exists:products,id',
                'primary_product_id' => 'nullable|exists:products,id',
                'company_name' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:100',
                'source' => 'nullable|in:manual,import,webform,api,referral',
                'notes' => 'nullable|string|max:1000',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify contact belongs to authenticated user
            $contact = EventsGuest::where('id', $request->events_guest_id)
                                 ->where('user_id', Auth::id())
                                 ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact not found or access denied'
                ], 404);
            }

            // Check if lead already exists for this contact with any of these products
            $existingLead = Lead::where('events_guest_id', $request->events_guest_id)
                              ->whereHas('leadProducts', function($query) use ($request) {
                                  $query->whereIn('product_id', $request->product_ids);
                              })
                              ->whereNotIn('status', [Lead::STATUS_CLOSED, Lead::STATUS_LOST, Lead::STATUS_DO_NOT_CONTACT])
                              ->first();

            if ($existingLead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active lead already exists for this contact with one or more of the specified products'
                ], 409);
            }

            DB::beginTransaction();

            // Create the lead
            $lead = Lead::create([
                'events_guest_id' => $request->events_guest_id,
                'ai_sales_agent_id' => $request->ai_sales_agent_id ?? $this->getDefaultAiAgent(),
                'user_id' => Auth::id(),
                'business_id' => $contact->business_id,
                'name' => $contact->guest_name,
                'phone_number' => $contact->guest_phone,
                'email' => $contact->guest_email,
                'company_name' => $request->company_name,
                'industry' => $request->industry,
                'source' => $request->source ?? 'api',
                'status' => Lead::STATUS_NEW,
                'notes' => $request->notes,
                'lead_score' => 50, // Default score
                'metadata' => $request->metadata ?? []
            ]);

            // Add products to the lead
            $primaryProductId = $request->primary_product_id ?? $request->product_ids[0];
            
            foreach ($request->product_ids as $productId) {
                $lead->leadProducts()->create([
                    'product_id' => $productId,
                    'status' => 'INTERESTED',
                    'is_primary_product' => $productId == $primaryProductId,
                    'is_active' => true
                ]);
            }

            DB::commit();

            // Load relationships for response
            $lead->load(['contact', 'leadProducts.product', 'aiSalesAgent']);

            return response()->json([
                'success' => true,
                'data' => $this->formatLeadResponse($lead),
                'message' => 'Lead created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating lead', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating lead'
            ], 500);
        }
    }

    /**
     * Get all leads for authenticated user
     * 
     * GET /api/leads
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lead::where('user_id', Auth::id())
                        ->with(['contact', 'leadProducts.product', 'aiSalesAgent']);

            // Filter by status
            if ($request->has('status')) {
                $statuses = is_array($request->status) ? $request->status : [$request->status];
                $query->whereIn('status', $statuses);
            }

            // Filter by source
            if ($request->has('source')) {
                $query->where('source', $request->source);
            }

            // Filter by lead score range
            if ($request->has('min_score')) {
                $query->where('lead_score', '>=', $request->min_score);
            }
            if ($request->has('max_score')) {
                $query->where('lead_score', '<=', $request->max_score);
            }

            // Filter by product
            if ($request->has('product_id')) {
                $query->whereHas('leadProducts', function($q) use ($request) {
                    $q->where('product_id', $request->product_id);
                });
            }

            // Filter by churned status
            if ($request->has('is_churned')) {
                $query->where('is_churned', $request->boolean('is_churned'));
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            $allowedSortFields = ['created_at', 'lead_score', 'last_interaction_at', 'name', 'status'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50); // Max 50 per page
            $leads = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $leads->items()->map(function($lead) {
                    return $this->formatLeadResponse($lead);
                }),
                'pagination' => [
                    'current_page' => $leads->currentPage(),
                    'last_page' => $leads->lastPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total(),
                    'from' => $leads->firstItem(),
                    'to' => $leads->lastItem()
                ],
                'message' => 'Leads retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving leads', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving leads'
            ], 500);
        }
    }

    /**
     * Get a specific lead
     * 
     * GET /api/leads/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $lead = Lead::where('user_id', Auth::id())
                       ->where('id', $id)
                       ->with(['contact', 'leadProducts.product', 'aiSalesAgent', 'conversations'])
                       ->first();

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatDetailedLeadResponse($lead),
                'message' => 'Lead retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving lead', [
                'error' => $e->getMessage(),
                'lead_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving lead'
            ], 500);
        }
    }

    /**
     * Update lead status and basic information
     * 
     * PUT /api/leads/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:' . implode(',', [
                    Lead::STATUS_NEW, Lead::STATUS_OUTREACHED, Lead::STATUS_REPLIED,
                    Lead::STATUS_QUALIFIED, Lead::STATUS_PITCHED, Lead::STATUS_DEMO_SCHEDULED,
                    Lead::STATUS_PROPOSAL_SENT, Lead::STATUS_NEGOTIATING, Lead::STATUS_CLOSED,
                    Lead::STATUS_LOST, Lead::STATUS_HANDED_OFF, Lead::STATUS_DO_NOT_CONTACT
                ]),
                'notes' => 'nullable|string|max:1000',
                'assigned_agent_id' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lead = Lead::where('user_id', Auth::id())->findOrFail($id);

            $updateData = [
                'status' => $request->status,
                'last_interaction_at' => now()
            ];

            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            if ($request->has('assigned_agent_id')) {
                $updateData['assigned_agent_id'] = $request->assigned_agent_id;
            }

            $lead->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $this->formatLeadResponse($lead->fresh(['contact', 'leadProducts.product', 'aiSalesAgent'])),
                'message' => 'Lead status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }
    }

    /**
     * Get lead activity timeline
     * 
     * GET /api/leads/{id}/timeline
     */
    public function timeline(int $id): JsonResponse
    {
        try {
            $lead = Lead::where('user_id', Auth::id())->findOrFail($id);

            $conversations = $lead->conversations()
                                 ->orderBy('created_at', 'desc')
                                 ->get()
                                 ->map(function($conv) {
                                     return [
                                         'id' => $conv->id,
                                         'type' => 'conversation',
                                         'message_type' => $conv->message_type,
                                         'content' => $conv->message_content,
                                         'conversation_state' => $conv->conversation_state,
                                         'timestamp' => $conv->created_at,
                                         'confidence_score' => $conv->confidence_score
                                     ];
                                 });

            // Add lead status changes (this would require a separate audit log table in production)
            $timeline = $conversations->toArray();

            // Sort by timestamp
            usort($timeline, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $lead->id,
                    'timeline' => $timeline
                ],
                'message' => 'Lead timeline retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }
    }

    /**
     * Assign lead to an agent
     * 
     * POST /api/leads/{id}/assign
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'assigned_agent_id' => 'required|exists:users,id',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lead = Lead::where('user_id', Auth::id())->findOrFail($id);

            $lead->update([
                'assigned_agent_id' => $request->assigned_agent_id,
                'status' => Lead::STATUS_HANDED_OFF,
                'notes' => $request->has('notes') ? 
                    ($lead->notes ? $lead->notes . "\n\n" . $request->notes : $request->notes) : 
                    $lead->notes,
                'last_interaction_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatLeadResponse($lead->fresh(['contact', 'leadProducts.product', 'aiSalesAgent', 'assignedAgent'])),
                'message' => 'Lead assigned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }
    }

    /**
     * Bulk create leads from contacts
     * 
     * POST /api/leads/bulk-create
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'leads' => 'required|array|min:1|max:50',
                'leads.*.events_guest_id' => 'required|exists:events_guests,id',
                'leads.*.product_ids' => 'required|array|min:1',
                'leads.*.product_ids.*' => 'exists:products,id',
                'leads.*.company_name' => 'nullable|string|max:255',
                'leads.*.industry' => 'nullable|string|max:100',
                'leads.*.source' => 'nullable|in:manual,import,webform,api,referral',
                'leads.*.notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $created = [];
            $errors = [];
            $userId = Auth::id();

            DB::beginTransaction();

            foreach ($request->leads as $index => $leadData) {
                try {
                    // Verify contact belongs to user
                    $contact = EventsGuest::where('id', $leadData['events_guest_id'])
                                         ->where('user_id', $userId)
                                         ->first();

                    if (!$contact) {
                        $errors[] = "Lead at index {$index}: Contact not found or access denied";
                        continue;
                    }

                    // Check for existing active leads
                    $existingLead = Lead::where('events_guest_id', $leadData['events_guest_id'])
                                      ->whereHas('leadProducts', function($query) use ($leadData) {
                                          $query->whereIn('product_id', $leadData['product_ids']);
                                      })
                                      ->whereNotIn('status', [Lead::STATUS_CLOSED, Lead::STATUS_LOST, Lead::STATUS_DO_NOT_CONTACT])
                                      ->first();

                    if ($existingLead) {
                        $errors[] = "Lead at index {$index}: Active lead already exists for this contact with specified products";
                        continue;
                    }

                    // Create lead
                    $lead = Lead::create([
                        'events_guest_id' => $leadData['events_guest_id'],
                        'ai_sales_agent_id' => $this->getDefaultAiAgent(),
                        'user_id' => $userId,
                        'business_id' => $contact->business_id,
                        'name' => $contact->guest_name,
                        'phone_number' => $contact->guest_phone,
                        'email' => $contact->guest_email,
                        'company_name' => $leadData['company_name'] ?? null,
                        'industry' => $leadData['industry'] ?? null,
                        'source' => $leadData['source'] ?? 'api',
                        'status' => Lead::STATUS_NEW,
                        'notes' => $leadData['notes'] ?? null,
                        'lead_score' => 50,
                        'metadata' => []
                    ]);

                    // Add products
                    $primaryProductId = $leadData['product_ids'][0];
                    foreach ($leadData['product_ids'] as $productId) {
                        $lead->leadProducts()->create([
                            'product_id' => $productId,
                            'status' => 'INTERESTED',
                            'is_primary_product' => $productId == $primaryProductId,
                            'is_active' => true
                        ]);
                    }

                    $lead->load(['contact', 'leadProducts.product']);
                    $created[] = $this->formatLeadResponse($lead);

                } catch (\Exception $e) {
                    $errors[] = "Lead at index {$index}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => $created,
                    'created_count' => count($created),
                    'error_count' => count($errors),
                    'errors' => $errors
                ],
                'message' => 'Bulk lead creation completed'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk lead creation', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error in bulk lead creation'
            ], 500);
        }
    }

    /**
     * Bulk update lead statuses
     * 
     * PUT /api/leads/bulk-update
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'updates' => 'required|array|min:1|max:100',
                'updates.*.lead_id' => 'required|exists:leads,id',
                'updates.*.status' => 'required|in:' . implode(',', [
                    Lead::STATUS_NEW, Lead::STATUS_OUTREACHED, Lead::STATUS_REPLIED,
                    Lead::STATUS_QUALIFIED, Lead::STATUS_PITCHED, Lead::STATUS_DEMO_SCHEDULED,
                    Lead::STATUS_PROPOSAL_SENT, Lead::STATUS_NEGOTIATING, Lead::STATUS_CLOSED,
                    Lead::STATUS_LOST, Lead::STATUS_HANDED_OFF, Lead::STATUS_DO_NOT_CONTACT
                ]),
                'updates.*.notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updated = [];
            $errors = [];
            $userId = Auth::id();

            DB::beginTransaction();

            foreach ($request->updates as $index => $updateData) {
                try {
                    $lead = Lead::where('user_id', $userId)
                              ->where('id', $updateData['lead_id'])
                              ->first();

                    if (!$lead) {
                        $errors[] = "Update at index {$index}: Lead not found or access denied";
                        continue;
                    }

                    $lead->update([
                        'status' => $updateData['status'],
                        'notes' => $updateData['notes'] ?? $lead->notes,
                        'last_interaction_at' => now()
                    ]);

                    $updated[] = $updateData['lead_id'];

                } catch (\Exception $e) {
                    $errors[] = "Update at index {$index}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_leads' => $updated,
                    'updated_count' => count($updated),
                    'error_count' => count($errors),
                    'errors' => $errors
                ],
                'message' => 'Bulk lead update completed'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk lead update', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error in bulk lead update'
            ], 500);
        }
    }

    /**
     * Get sales pipeline summary
     * 
     * GET /api/leads/pipeline
     */
    public function pipeline(): JsonResponse
    {
        try {
            $userId = Auth::id();

            $pipeline = Lead::where('user_id', $userId)
                          ->selectRaw('status, COUNT(*) as count, AVG(lead_score) as avg_score')
                          ->groupBy('status')
                          ->get()
                          ->keyBy('status');

            $statusCounts = [
                'NEW' => ['count' => 0, 'avg_score' => 0],
                'OUTREACHED' => ['count' => 0, 'avg_score' => 0],
                'REPLIED' => ['count' => 0, 'avg_score' => 0],
                'QUALIFIED' => ['count' => 0, 'avg_score' => 0],
                'PITCHED' => ['count' => 0, 'avg_score' => 0],
                'DEMO_SCHEDULED' => ['count' => 0, 'avg_score' => 0],
                'PROPOSAL_SENT' => ['count' => 0, 'avg_score' => 0],
                'NEGOTIATING' => ['count' => 0, 'avg_score' => 0],
                'CLOSED' => ['count' => 0, 'avg_score' => 0],
                'LOST' => ['count' => 0, 'avg_score' => 0],
                'HANDED_OFF' => ['count' => 0, 'avg_score' => 0],
                'DO_NOT_CONTACT' => ['count' => 0, 'avg_score' => 0]
            ];

            foreach ($pipeline as $status => $data) {
                $statusCounts[$status] = [
                    'count' => $data->count,
                    'avg_score' => round($data->avg_score, 1)
                ];
            }

            // Get recent activity
            $recentActivity = Lead::where('user_id', $userId)
                                ->whereNotNull('last_interaction_at')
                                ->orderBy('last_interaction_at', 'desc')
                                ->limit(10)
                                ->with(['contact'])
                                ->get()
                                ->map(function($lead) {
                                    return [
                                        'lead_id' => $lead->id,
                                        'contact_name' => $lead->contact->guest_name,
                                        'status' => $lead->status,
                                        'last_interaction_at' => $lead->last_interaction_at
                                    ];
                                });

            return response()->json([
                'success' => true,
                'data' => [
                    'pipeline' => $statusCounts,
                    'total_leads' => array_sum(array_column($statusCounts, 'count')),
                    'recent_activity' => $recentActivity
                ],
                'message' => 'Pipeline data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving pipeline data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving pipeline data'
            ], 500);
        }
    }

    /**
     * Mark lead as churned
     * 
     * POST /api/leads/{id}/churn
     */
    public function markAsChurned(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'churn_reason' => 'required|string|max:255',
                'churn_date' => 'nullable|date',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lead = Lead::where('user_id', Auth::id())->findOrFail($id);

            $lead->update([
                'is_churned' => true,
                'churn_date' => $request->churn_date ? Carbon::parse($request->churn_date) : now(),
                'churn_reason' => $request->churn_reason,
                'status' => Lead::STATUS_LOST,
                'churn_notes' => $request->notes,
                'last_interaction_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatLeadResponse($lead->fresh(['contact', 'leadProducts.product', 'aiSalesAgent'])),
                'message' => 'Lead marked as churned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }
    }

    /**
     * Reactivate churned lead
     * 
     * POST /api/leads/{id}/reactivate
     */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lead = Lead::where('user_id', Auth::id())
                       ->where('is_churned', true)
                       ->findOrFail($id);

            $lead->update([
                'is_churned' => false,
                'churn_date' => null,
                'churn_reason' => null,
                'churn_notes' => null,
                'status' => Lead::STATUS_NEW,
                'notes' => $request->has('notes') ? 
                    ($lead->notes ? $lead->notes . "\n\nReactivated: " . $request->notes : "Reactivated: " . $request->notes) : 
                    $lead->notes,
                'last_interaction_at' => now(),
                'win_back_attempts' => ($lead->win_back_attempts ?? 0) + 1,
                'last_win_back_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatLeadResponse($lead->fresh(['contact', 'leadProducts.product', 'aiSalesAgent'])),
                'message' => 'Lead reactivated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Churned lead not found'
            ], 404);
        }
    }

    /**
     * Get leads by contact ID
     * 
     * GET /api/contacts/{contactId}/leads
     */
    public function getLeadsByContact(int $contactId): JsonResponse
    {
        try {
            // Verify contact belongs to user
            $contact = EventsGuest::where('id', $contactId)
                                 ->where('user_id', Auth::id())
                                 ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact not found'
                ], 404);
            }

            $leads = Lead::where('events_guest_id', $contactId)
                        ->with(['leadProducts.product', 'aiSalesAgent'])
                        ->orderBy('created_at', 'desc')
                        ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'contact' => [
                        'id' => $contact->id,
                        'name' => $contact->guest_name,
                        'phone' => $contact->guest_phone,
                        'email' => $contact->guest_email
                    ],
                    'leads' => $leads->map(function($lead) {
                        return $this->formatLeadResponse($lead);
                    })
                ],
                'message' => 'Leads retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving leads for contact', [
                'error' => $e->getMessage(),
                'contact_id' => $contactId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving leads'
            ], 500);
        }
    }

    /**
     * Get default AI sales agent for user
     */
    private function getDefaultAiAgent()
    {
        $agent = AiSalesAgent::where('user_id', Auth::id())
                            ->where('is_active', true)
                            ->first();
        
        return $agent ? $agent->id : null;
    }

    /**
     * Format lead response for API
     */
    private function formatLeadResponse(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'contact' => [
                'id' => $lead->contact->id,
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
            'last_interaction_at' => $lead->last_interaction_at,
            'notes' => $lead->notes,
            'products' => $lead->leadProducts->map(function($lp) {
                return [
                    'id' => $lp->product->id,
                    'name' => $lp->product->name,
                    'status' => $lp->status,
                    'is_primary' => $lp->is_primary_product,
                    'quoted_price' => $lp->quoted_price
                ];
            }),
            'ai_sales_agent' => $lead->aiSalesAgent ? [
                'id' => $lead->aiSalesAgent->id,
                'name' => $lead->aiSalesAgent->name
            ] : null,
            'assigned_agent' => $lead->assignedAgent ? [
                'id' => $lead->assignedAgent->id,
                'name' => $lead->assignedAgent->name
            ] : null,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at
        ];
    }

    /**
     * Format detailed lead response with conversations
     */
    private function formatDetailedLeadResponse(Lead $lead): array
    {
        $baseResponse = $this->formatLeadResponse($lead);
        
        $baseResponse['conversations'] = $lead->conversations->map(function($conv) {
            return [
                'id' => $conv->id,
                'message_type' => $conv->message_type,
                'content' => $conv->message_content,
                'conversation_state' => $conv->conversation_state,
                'confidence_score' => $conv->confidence_score,
                'tokens_used' => $conv->tokens_used,
                'created_at' => $conv->created_at
            ];
        });

        return $baseResponse;
    }
}