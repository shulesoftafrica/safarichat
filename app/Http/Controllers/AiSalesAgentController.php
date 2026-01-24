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
        
        // Load billing data from billing_accounts table
        $user = Auth::user();
        $billingAccount = null;
        if ($user->business) {
            $billingAccount = $user->business->billingAccount;
        }
        
        // Set subscription and credits data
        if ($billingAccount) {
            $subscription_plan = $billingAccount->subscription_plan ?? 'trial';
            $ai_credits = $billingAccount->ai_credits ?? 0;
        } else {
            $subscription_plan = 'trial';
            $ai_credits = 0;
        }
            
        return view('service.ai-agents.index', compact('agents', 'subscription_plan', 'ai_credits'));
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
        // Check if user already has an AI sales agent (only one allowed per user)
        $existingAgent = AiSalesAgent::forUser(Auth::id())->first();
        if ($existingAgent) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an AI Sales Agent configured. Please edit the existing one instead of creating a new one.',
                'errors' => ['general' => ['Only one AI Sales Agent is allowed per user.']]
            ], 422);
        }
        
        $validatedData = $this->validateAgentData($request);
        
        try {
            DB::beginTransaction();
            
            // Add user ID, status and terms acceptance timestamp
            $validatedData['user_id'] = Auth::id();
            $validatedData['status'] = 'active'; // Set default status
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
     * Show the form for editing an existing AI Sales Agent
     */
    public function edit(AiSalesAgent $aiSalesAgent)
    {
        // Ensure the agent belongs to the current user
        if ($aiSalesAgent->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this AI sales agent.');
        }
        
        $userTypes = UserType::active()->orderBy('name')->get();
        $existingAgent = $aiSalesAgent;
        
        return view('service.job-description', compact('userTypes', 'existingAgent'));
    }

    /**
     * Update an existing AI sales agent configuration
     */
    public function update(Request $request, AiSalesAgent $aiSalesAgent)
    {
        try {
            Log::info('AiSalesAgentController::update - START', [
                'agent_uuid' => $aiSalesAgent->uuid,
                'agent_id' => $aiSalesAgent->id,
                'user_id' => Auth::id(),
                'request_method' => $request->method(),
                'is_ajax' => $request->ajax(),
            ]);

            // Ensure the agent belongs to the current user
            if ($aiSalesAgent->user_id !== Auth::id()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to this AI sales agent.'
                    ], 403);
                } else {
                    return redirect()->back()->withErrors(['general' => 'Unauthorized access to this AI sales agent.'])->withInput();
                }
            }

            // Log request data for debugging
            Log::info('Request data before validation', [
                'all_data' => $request->all(),
                'target_audience' => $request->input('target_audience'),
                'communication_tone' => $request->input('communication_tone'),
                'primary_language' => $request->input('primary_language'),
                'always_available' => $request->input('always_available')
            ]);

            $validatedData = $this->validateAgentData($request);

            Log::info('Agent found and data validated', [
                'agent_id' => $aiSalesAgent->id,
                'agent_name' => $aiSalesAgent->assistant_name
            ]);

            DB::beginTransaction();

            // Update terms acceptance if changed
            if ($request->accepted_terms && !$aiSalesAgent->accepted_terms) {
                $validatedData['terms_accepted_at'] = now();
            }

            $aiSalesAgent->update($validatedData);

            DB::commit();

            Log::info('AI Sales Agent updated successfully', ['agent_id' => $aiSalesAgent->id]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'AI Sales Agent configuration updated successfully!',
                    'agent' => [
                        'id' => $aiSalesAgent->id,
                        'uuid' => $aiSalesAgent->uuid,
                        'assistant_name' => $aiSalesAgent->assistant_name,
                        'status' => $aiSalesAgent->status
                    ]
                ]);
            } else {
                return redirect()->route('ai-agents.edit', $aiSalesAgent->uuid)
                    ->with('success', 'AI Sales Agent configuration updated successfully!');
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('AI Sales Agent not found', [
                'agent_uuid' => isset($aiSalesAgent->uuid) ? $aiSalesAgent->uuid : 'unknown',
                'user_id' => Auth::id()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI Sales Agent not found or you do not have permission to edit it.'
                ], 404);
            } else {
                return redirect()->back()->withErrors(['general' => 'AI Sales Agent not found or you do not have permission to edit it.'])->withInput();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('AI Sales Agent validation failed', [
                'agent_uuid' => isset($aiSalesAgent->uuid) ? $aiSalesAgent->uuid : 'unknown',
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
                'all_errors' => json_encode($e->errors())
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed. Please check your input.',
                    'errors' => $e->errors()
                ], 422);
            } else {
                return redirect()->back()->withErrors($e->errors())->withInput();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AI Sales Agent update failed: ' . $e->getMessage(), [
                'agent_uuid' => isset($aiSalesAgent->uuid) ? $aiSalesAgent->uuid : 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update configuration. Please try again.',
                    'errors' => ['general' => [$e->getMessage()]]
                ], 422);
            } else {
                return redirect()->back()->withErrors(['general' => 'Failed to update configuration. Please try again.'])->withInput();
            }
        }
    }

    /**
     * Get AI sales agent configuration
     */
    public function show($aiSalesAgent)
    {
        try {
            // Convert to ID if it's a model instance, otherwise treat as ID
            $agentId = is_object($aiSalesAgent) ? $aiSalesAgent->id : $aiSalesAgent;
            
            $agent = AiSalesAgent::forUser(Auth::id())->with('user')->findOrFail($agentId);
            
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
    public function toggleStatus(Request $request, $aiSalesAgent)
    {
        try {
            // Convert to ID if it's a model instance, otherwise treat as ID
            $agentId = is_object($aiSalesAgent) ? $aiSalesAgent->id : $aiSalesAgent;
            
            $agent = AiSalesAgent::forUser(Auth::id())->findOrFail($agentId);
            
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
    public function destroy($aiSalesAgent)
    {
        try {
            // Convert to ID if it's a model instance, otherwise treat as ID
            $agentId = is_object($aiSalesAgent) ? $aiSalesAgent->id : $aiSalesAgent;
            
            $agent = AiSalesAgent::forUser(Auth::id())->findOrFail($agentId);
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
            'target_user_types' => 'nullable|array', // Made nullable
            'target_user_types.*' => 'nullable|integer', // Changed validation
            'communication_tone' => 'required|string|in:professional,friendly,consultative,direct',
            
            // Working Hours
            'always_available' => 'boolean',
            'business_days' => 'nullable|array',
            'business_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'timezone' => 'required|string',
            'out_of_hours_message' => 'nullable|string|max:500',
            
            // Languages - Primary only
            'primary_language' => 'required|string|in:en,sw,fr,ar,pt,am',
            
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
