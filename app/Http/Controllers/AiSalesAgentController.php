<?php

namespace App\Http\Controllers;

use App\Models\AiSalesAgent;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AiSalesAgentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of AI Sales Agents
     */
    public function index()
    {
        $agents = AiSalesAgent::forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('service.ai-agents.index', compact('agents'));
    }

    /**
     * Show the form for creating a new AI Sales Agent
     */
    public function create()
    {
        $userTypes = UserType::active()->orderBy('name')->get();
        $existingAgent = AiSalesAgent::forUser(Auth::id())->latest()->first();
        
        return view('service.job-description', compact('userTypes', 'existingAgent'));
    }

    /**
     * Store a new AI sales agent configuration
     */
    public function store(Request $request)
    {
        $validatedData = $this->validateAgentData($request);
        
        try {
            DB::beginTransaction();
            
            // Add user ID and terms acceptance timestamp
            $validatedData['user_id'] = Auth::id();
            $validatedData['terms_accepted_at'] = now();
            
            // Create the AI sales agent
            $agent = AiSalesAgent::create($validatedData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'AI Sales Agent configuration saved successfully!',
                'redirect' => route('ai-agents.index'),
                'agent_id' => $agent->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AI Sales Agent creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save configuration. Please try again.',
                'errors' => ['general' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Update an existing AI sales agent configuration
     */
    public function update(Request $request, $id)
    {
        $agent = AiSalesAgent::forUser(Auth::id())->findOrFail($id);
        $validatedData = $this->validateAgentData($request);
        
        try {
            DB::beginTransaction();
            
            // Update terms acceptance if changed
            if ($request->accepted_terms && !$agent->accepted_terms) {
                $validatedData['terms_accepted_at'] = now();
            }
            
            $agent->update($validatedData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'AI Sales Agent configuration updated successfully!',
                'agent_id' => $agent->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AI Sales Agent update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration. Please try again.',
                'errors' => ['general' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Get AI sales agent configuration
     */
    public function show($id)
    {
        try {
            $agent = AiSalesAgent::forUser(Auth::id())->with('user')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'agent' => $agent
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Agent configuration not found.'
            ], 404);
        }
    }

    /**
     * Get user's AI sales agents
     */
    public function getUserAgents()
    {
        try {
            $agents = AiSalesAgent::forUser(Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'agents' => $agents
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load agents.'
            ], 500);
        }
    }

    /**
     * Activate/Deactivate an AI sales agent
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $agent = AiSalesAgent::forUser(Auth::id())->findOrFail($id);
            
            $newStatus = $request->status === 'active' ? 'active' : 'inactive';
            $agent->update(['status' => $newStatus]);
            
            return response()->json([
                'success' => true,
                'message' => "AI Sales Agent {$newStatus} successfully!",
                'status' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update agent status.'
            ], 422);
        }
    }

    /**
     * Delete an AI sales agent
     */
    public function destroy($id)
    {
        try {
            $agent = AiSalesAgent::forUser(Auth::id())->findOrFail($id);
            $agentName = $agent->assistant_name;
            
            $agent->delete();
            
            return response()->json([
                'success' => true,
                'message' => "AI Sales Agent '{$agentName}' deleted successfully!"
            ]);
            
        } catch (\Exception $e) {
            Log::error('AI Sales Agent deletion failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete agent. Please try again.'
            ], 422);
        }
    }

    /**
     * Get user types for selection
     */
    public function getUserTypes()
    {
        try {
            $userTypes = UserType::active()->orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'user_types' => $userTypes
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user types.'
            ], 500);
        }
    }

    /**
     * Validate agent data
     */
    private function validateAgentData(Request $request)
    {
        return $request->validate([
            // Basic Information
            'assistant_name' => 'required|string|max:255',
            'target_audience' => 'required|string|in:small-businesses,medium-businesses,enterprises,individuals,mixed',
            'target_user_types' => 'required|array|min:1',
            'target_user_types.*' => 'exists:user_types,id',
            'industries' => 'nullable|array',
            'industries.*' => 'string|in:retail,hospitality,healthcare,education,finance,technology,other',
            'communication_tone' => 'required|string|in:professional,friendly,consultative,direct',
            'personality_description' => 'nullable|string|max:1000',
            
            // Working Hours
            'always_available' => 'boolean',
            'business_days' => 'nullable|array',
            'business_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'timezone' => 'required|string',
            'out_of_hours_message' => 'nullable|string|max:500',
            
            // Languages
            'primary_language' => 'required|string|in:en,sw,fr,ar,pt,am',
            'additional_languages' => 'nullable|array',
            'additional_languages.*' => 'string|in:sw,fr,ar,pt,am,yo,ig,ha',
            'auto_detect_language' => 'boolean',
            'language_fallback_message' => 'nullable|string|max:500',
            
            // Negotiation
            'allow_negotiation' => 'boolean',
            'max_discount_allowed' => 'nullable|integer|min:0|max:50',
            'accept_installments' => 'boolean',
            'max_installments' => 'nullable|integer|min:2|max:12',
            'min_down_payment' => 'nullable|integer|min:10|max:100',
            'stop_orders_low_stock' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:1|max:100',
            'negotiation_script' => 'nullable|string|max:1000',
            
            // Fallback & Escalation
            'fallback_number' => 'required|string|max:20',
            'fallback_person' => 'nullable|string|max:255',
            'escalation_triggers' => 'nullable|array',
            'escalation_triggers.*' => 'string|in:complex-questions,complaints,large-orders,payment-issues,angry-customer',
            'large_order_threshold' => 'nullable|numeric|min:0',
            
            // Follow-up
            'auto_followup' => 'boolean',
            'followup_delay' => 'nullable|integer|min:1|max:168', // max 1 week
            'max_followups' => 'nullable|integer|min:1|max:5',
            'followup_message' => 'nullable|string|max:500',
            
            // Notifications
            'notify_on_deal' => 'boolean',
            'notification_methods' => 'nullable|array',
            'notification_methods.*' => 'string|in:whatsapp,email,sms',
            'additional_notifications' => 'nullable|array',
            'additional_notifications.*' => 'string|in:new-lead,escalation,errors',
            
            // Terms & Conditions
            'accepted_terms' => 'required|accepted'
        ]);
    }
}
