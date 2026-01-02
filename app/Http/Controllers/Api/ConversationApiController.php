<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;
use App\Services\BillingService;
use App\Services\LocalBillingValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ConversationApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get conversation history for a lead
     * 
     * GET /api/conversations/{leadId}
     */
    public function getConversationHistory(Request $request, int $leadId): JsonResponse
    {
        try {
            // Verify lead belongs to authenticated user
            $lead = Lead::where('user_id', Auth::id())->findOrFail($leadId);

            $query = Conversation::where('lead_id', $leadId)
                                ->with(['lead.contact', 'aiSalesAgent']);

            // Filter by message type
            if ($request->has('message_type')) {
                $validTypes = [Conversation::TYPE_CUSTOMER, Conversation::TYPE_AI_AGENT, Conversation::TYPE_HUMAN_AGENT];
                if (in_array($request->message_type, $validTypes)) {
                    $query->where('message_type', $request->message_type);
                }
            }

            // Filter by conversation state
            if ($request->has('conversation_state')) {
                $query->where('conversation_state', $request->conversation_state);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date);
            }

            // Filter by confidence score range
            if ($request->has('min_confidence')) {
                $query->where('confidence_score', '>=', $request->min_confidence);
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            $allowedSortFields = ['created_at', 'confidence_score', 'tokens_used'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Pagination
            $perPage = min($request->get('per_page', 50), 100);
            $conversations = $query->paginate($perPage);

            $formattedConversations = $conversations->items()->map(function($conversation) {
                return $this->formatConversationResponse($conversation);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'lead' => [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'contact_name' => $lead->contact->guest_name,
                        'status' => $lead->status
                    ],
                    'conversations' => $formattedConversations,
                    'summary' => [
                        'total_messages' => $conversations->total(),
                        'customer_messages' => $lead->conversations()->where('message_type', Conversation::TYPE_CUSTOMER)->count(),
                        'ai_messages' => $lead->conversations()->where('message_type', Conversation::TYPE_AI_AGENT)->count(),
                        'human_messages' => $lead->conversations()->where('message_type', Conversation::TYPE_HUMAN_AGENT)->count(),
                        'avg_confidence' => round($lead->conversations()->avg('confidence_score'), 2),
                        'total_tokens_used' => $lead->conversations()->sum('tokens_used'),
                        'conversation_duration_days' => $lead->conversations()->count() > 0 ? 
                            Carbon::parse($lead->conversations()->min('created_at'))->diffInDays($lead->conversations()->max('created_at')) : 0
                    ]
                ],
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total()
                ],
                'message' => 'Conversation history retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving conversation history', [
                'error' => $e->getMessage(),
                'lead_id' => $leadId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lead not found or error retrieving conversations'
            ], 404);
        }
    }

    /**
     * Create a new conversation entry
     * 
     * POST /api/conversations
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'lead_id' => 'required|exists:leads,id',
                'message_type' => 'required|in:' . implode(',', [
                    Conversation::TYPE_CUSTOMER, Conversation::TYPE_AI_AGENT, Conversation::TYPE_HUMAN_AGENT
                ]),
                'message_content' => 'required|string|max:4096',
                'conversation_state' => 'nullable|in:' . implode(',', [
                    Conversation::STATE_INTRO, Conversation::STATE_PITCH, Conversation::STATE_DEMO,
                    Conversation::STATE_NEGOTIATION, Conversation::STATE_CLOSING, Conversation::STATE_CLOSED,
                    Conversation::STATE_OBJECTION_HANDLING, Conversation::STATE_FOLLOW_UP
                ]),
                'product_id' => 'nullable|exists:products,id',
                'ai_sales_agent_id' => 'nullable|exists:ai_sales_agents,id',
                'confidence_score' => 'nullable|numeric|between:0,1',
                'tokens_used' => 'nullable|integer|min:0',
                'sentiment' => 'nullable|string|max:50',
                'rag_sources' => 'nullable|string|max:2000',
                'ai_actions' => 'nullable|array',
                'conversation_context' => 'nullable|array',
                'incoming_message_id' => 'nullable|exists:incoming_messages,id',
                'followup_attempt_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify lead belongs to authenticated user
            $lead = Lead::where('user_id', Auth::id())
                       ->where('id', $request->lead_id)
                       ->firstOrFail();

            // FEATURE GATE: Check if trying to schedule a follow-up
            if (!empty($request->followup_attempt_at)) {
                $customerId = Auth::user()->customer_id ?? Auth::id();
                $billingStatus = BillingService::getCachedStatus($customerId);
                
                if (!$billingStatus['permissions']['customer_followups']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer follow-up scheduling is not available in your current plan',
                        'upgrade_required' => true,
                        'feature' => 'customer_followups',
                        'current_plan' => $billingStatus['subscription']['plan'],
                        'required_plan' => 'pro'
                    ], 403);
                }
            }

            // Create conversation entry
            $conversation = Conversation::create([
                'lead_id' => $request->lead_id,
                'product_id' => $request->product_id,
                'message_type' => $request->message_type,
                'message_content' => $request->message_content,
                'conversation_state' => $request->conversation_state ?? Conversation::STATE_INTRO,
                'ai_sales_agent_id' => $request->ai_sales_agent_id ?? $lead->ai_sales_agent_id,
                'confidence_score' => $request->confidence_score,
                'tokens_used' => $request->tokens_used ?? 0,
                'sentiment' => $request->sentiment,
                'rag_sources' => $request->rag_sources,
                'rag_enhanced' => $request->rag_sources ? 1 : 0,
                'ai_actions' => $request->ai_actions,
                'conversation_context' => $request->conversation_context,
                'incoming_message_id' => $request->incoming_message_id,
                'followup_attempt_at' => $request->followup_attempt_at,
                'is_active' => true
            ]);

            // Update lead's last interaction time
            $lead->update(['last_interaction_at' => now()]);

            // Load relationships for response
            $conversation->load(['lead.contact', 'aiSalesAgent']);

            return response()->json([
                'success' => true,
                'data' => $this->formatConversationResponse($conversation),
                'message' => 'Conversation entry created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating conversation', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating conversation entry'
            ], 500);
        }
    }

    /**
     * Update conversation state and metadata
     * 
     * PUT /api/conversations/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversation_state' => 'nullable|in:' . implode(',', [
                    Conversation::STATE_INTRO, Conversation::STATE_PITCH, Conversation::STATE_DEMO,
                    Conversation::STATE_NEGOTIATION, Conversation::STATE_CLOSING, Conversation::STATE_CLOSED,
                    Conversation::STATE_OBJECTION_HANDLING, Conversation::STATE_FOLLOW_UP
                ]),
                'confidence_score' => 'nullable|numeric|between:0,1',
                'sentiment' => 'nullable|string|max:50',
                'ai_actions' => 'nullable|array',
                'conversation_context' => 'nullable|array',
                'followup_attempt_at' => 'nullable|date',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find conversation and verify access through lead ownership
            $conversation = Conversation::whereHas('lead', function($query) {
                $query->where('user_id', Auth::id());
            })->findOrFail($id);

            $updateData = array_filter([
                'conversation_state' => $request->conversation_state,
                'confidence_score' => $request->confidence_score,
                'sentiment' => $request->sentiment,
                'ai_actions' => $request->ai_actions,
                'conversation_context' => $request->conversation_context,
                'followup_attempt_at' => $request->followup_attempt_at,
                'is_active' => $request->is_active
            ], function($value) {
                return $value !== null;
            });

            $conversation->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $this->formatConversationResponse($conversation->fresh(['lead.contact', 'aiSalesAgent'])),
                'message' => 'Conversation updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }
    }

    /**
     * Get conversation analytics
     * 
     * GET /api/conversations/analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Date range filter
            $fromDate = $request->get('from_date', now()->subDays(30)->toDateString());
            $toDate = $request->get('to_date', now()->toDateString());

            $baseQuery = Conversation::whereHas('lead', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })->whereBetween('created_at', [$fromDate, $toDate]);

            // Overall metrics
            $totalConversations = (clone $baseQuery)->count();
            $avgConfidence = (clone $baseQuery)->avg('confidence_score');
            $totalTokens = (clone $baseQuery)->sum('tokens_used');

            // Message type distribution
            $messageTypes = (clone $baseQuery)
                ->selectRaw('message_type, COUNT(*) as count')
                ->groupBy('message_type')
                ->pluck('count', 'message_type');

            // Conversation state distribution
            $conversationStates = (clone $baseQuery)
                ->selectRaw('conversation_state, COUNT(*) as count')
                ->groupBy('conversation_state')
                ->pluck('count', 'conversation_state');

            // Daily conversation volume
            $dailyVolume = (clone $baseQuery)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');

            // Confidence score distribution
            $confidenceRanges = [
                'high' => (clone $baseQuery)->where('confidence_score', '>=', 0.8)->count(),
                'medium' => (clone $baseQuery)->whereBetween('confidence_score', [0.5, 0.79])->count(),
                'low' => (clone $baseQuery)->where('confidence_score', '<', 0.5)->count()
            ];

            // Sentiment analysis
            $sentiments = (clone $baseQuery)
                ->whereNotNull('sentiment')
                ->selectRaw('sentiment, COUNT(*) as count')
                ->groupBy('sentiment')
                ->pluck('count', 'sentiment');

            // Top performing AI agents (if applicable)
            $topAgents = (clone $baseQuery)
                ->whereNotNull('ai_sales_agent_id')
                ->selectRaw('ai_sales_agent_id, COUNT(*) as conversation_count, AVG(confidence_score) as avg_confidence')
                ->groupBy('ai_sales_agent_id')
                ->orderByDesc('conversation_count')
                ->limit(5)
                ->get()
                ->map(function($agent) {
                    return [
                        'agent_id' => $agent->ai_sales_agent_id,
                        'conversation_count' => $agent->conversation_count,
                        'avg_confidence' => round($agent->avg_confidence, 3)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'from' => $fromDate,
                        'to' => $toDate
                    ],
                    'overview' => [
                        'total_conversations' => $totalConversations,
                        'avg_confidence_score' => round($avgConfidence, 3),
                        'total_tokens_used' => $totalTokens,
                        'avg_tokens_per_conversation' => $totalConversations > 0 ? round($totalTokens / $totalConversations) : 0
                    ],
                    'message_types' => $messageTypes,
                    'conversation_states' => $conversationStates,
                    'daily_volume' => $dailyVolume,
                    'confidence_distribution' => $confidenceRanges,
                    'sentiment_analysis' => $sentiments,
                    'top_ai_agents' => $topAgents
                ],
                'message' => 'Conversation analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving conversation analytics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving analytics'
            ], 500);
        }
    }

    /**
     * Search conversations across all leads
     * 
     * GET /api/conversations/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:3|max:255',
                'message_type' => 'nullable|in:' . implode(',', [
                    Conversation::TYPE_CUSTOMER, Conversation::TYPE_AI_AGENT, Conversation::TYPE_HUMAN_AGENT
                ]),
                'conversation_state' => 'nullable|string',
                'lead_id' => 'nullable|exists:leads,id',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date',
                'min_confidence' => 'nullable|numeric|between:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Conversation::whereHas('lead', function($q) {
                $q->where('user_id', Auth::id());
            })->with(['lead.contact']);

            // Text search in message content
            $searchTerm = $request->query;
            $query->where('message_content', 'like', "%{$searchTerm}%");

            // Apply filters
            if ($request->has('message_type')) {
                $query->where('message_type', $request->message_type);
            }

            if ($request->has('conversation_state')) {
                $query->where('conversation_state', $request->conversation_state);
            }

            if ($request->has('lead_id')) {
                // Verify lead belongs to user
                $leadExists = Lead::where('user_id', Auth::id())
                                 ->where('id', $request->lead_id)
                                 ->exists();
                
                if ($leadExists) {
                    $query->where('lead_id', $request->lead_id);
                }
            }

            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date);
            }

            if ($request->has('min_confidence')) {
                $query->where('confidence_score', '>=', $request->min_confidence);
            }

            // Sort by relevance (most recent first by default)
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = min($request->get('per_page', 20), 50);
            $results = $query->paginate($perPage);

            $formattedResults = $results->items()->map(function($conversation) use ($searchTerm) {
                $formatted = $this->formatConversationResponse($conversation);
                
                // Add search highlighting context
                $content = $conversation->message_content;
                $highlightedContent = str_ireplace($searchTerm, "<mark>{$searchTerm}</mark>", $content);
                
                $formatted['highlighted_content'] = $highlightedContent;
                $formatted['match_strength'] = $this->calculateMatchStrength($content, $searchTerm);
                
                return $formatted;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'search_query' => $searchTerm,
                    'conversations' => $formattedResults,
                    'search_summary' => [
                        'total_matches' => $results->total(),
                        'leads_involved' => $results->items()->pluck('lead_id')->unique()->count(),
                        'avg_confidence' => round($results->items()->avg('confidence_score'), 3)
                    ]
                ],
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total()
                ],
                'message' => 'Conversation search completed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching conversations', [
                'error' => $e->getMessage(),
                'search_query' => $request->query ?? 'unknown',
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error performing search'
            ], 500);
        }
    }

    /**
     * Get conversations that need follow-up
     * 
     * GET /api/conversations/follow-ups
     */
    public function getFollowUps(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $customerId = Auth::user()->customer_id ?? $userId;
            
            // FEATURE GATE: Check if customer followups are allowed in current subscription plan
            $billingStatus = BillingService::getCachedStatus($customerId);
            
            if (!$billingStatus['permissions']['customer_followups']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer follow-ups are not available in your current plan',
                    'upgrade_required' => true,
                    'feature' => 'customer_followups',
                    'current_plan' => $billingStatus['subscription']['plan'],
                    'required_plan' => 'pro'
                ], 403);
            }

            $followUps = Conversation::whereHas('lead', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('is_active', true)
            ->whereNotNull('followup_attempt_at')
            ->where('followup_attempt_at', '<=', now())
            ->with(['lead.contact'])
            ->orderBy('followup_attempt_at', 'asc')
            ->get();

            $formattedFollowUps = $followUps->map(function($conversation) {
                $formatted = $this->formatConversationResponse($conversation);
                $formatted['is_overdue'] = Carbon::parse($conversation->followup_attempt_at)->isPast();
                $formatted['hours_overdue'] = Carbon::parse($conversation->followup_attempt_at)->diffInHours(now());
                return $formatted;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'follow_ups' => $formattedFollowUps,
                    'summary' => [
                        'total_follow_ups' => $followUps->count(),
                        'overdue_count' => $followUps->filter(function($conv) {
                            return Carbon::parse($conv->followup_attempt_at)->isPast();
                        })->count(),
                        'due_today' => $followUps->filter(function($conv) {
                            return Carbon::parse($conv->followup_attempt_at)->isToday();
                        })->count()
                    ]
                ],
                'message' => 'Follow-up conversations retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving follow-ups', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving follow-ups'
            ], 500);
        }
    }

    /**
     * Format conversation response for API
     */
    private function formatConversationResponse(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'lead_name' => $conversation->lead->name ?? 'Unknown',
            'contact_name' => $conversation->lead->contact->guest_name ?? 'Unknown',
            'product_id' => $conversation->product_id,
            'message_type' => $conversation->message_type,
            'message_content' => $conversation->message_content,
            'conversation_state' => $conversation->conversation_state,
            'confidence_score' => $conversation->confidence_score,
            'tokens_used' => $conversation->tokens_used,
            'sentiment' => $conversation->sentiment,
            'rag_sources' => $conversation->rag_sources,
            'rag_enhanced' => (bool) $conversation->rag_enhanced,
            'ai_actions' => $conversation->ai_actions,
            'conversation_context' => $conversation->conversation_context,
            'ai_sales_agent' => $conversation->aiSalesAgent ? [
                'id' => $conversation->aiSalesAgent->id,
                'name' => $conversation->aiSalesAgent->name
            ] : null,
            'followup_attempt_at' => $conversation->followup_attempt_at,
            'is_active' => $conversation->is_active,
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at
        ];
    }

    /**
     * Calculate match strength for search results
     */
    private function calculateMatchStrength(string $content, string $searchTerm): float
    {
        $contentLength = strlen($content);
        $searchLength = strlen($searchTerm);
        $occurrences = substr_count(strtolower($content), strtolower($searchTerm));
        
        if ($contentLength === 0 || $occurrences === 0) {
            return 0.0;
        }
        
        // Calculate based on frequency and relative term size
        $frequency = ($occurrences * $searchLength) / $contentLength;
        return min($frequency * 10, 1.0); // Cap at 1.0
    }
}