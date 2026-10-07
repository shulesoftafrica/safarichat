<?php

namespace App\Http\Controllers;

use App\Models\AiSalesAgent;
use App\Models\Channel;
use App\Models\Business;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AiSalesAgentController extends Controller
{
    /**
     * Connection that hosts the admin.school_keys table (the Phone-SMS connector
     * codes). It lives in the admin database, NOT the default other_app database,
     * so it must be reached through this connection.
     */
    const SCHOOL_KEYS_CONNECTION = 'admin_crm';

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
        
        // Get WhatsApp instance and fetch real-time status from WaSender API
        $whatsappInstance = \App\Models\WhatsappInstance::where('user_id', Auth::id())->first();
        $realTimeStatus = null;
        
        if ($whatsappInstance && $whatsappInstance->instance_id) {
            try {
                $unifiedService = app(\App\Services\UnifiedNotificationService::class);
                $statusResult = $unifiedService->getSessionStatus($whatsappInstance->instance_id);
                
                if (isset($statusResult['success']) && $statusResult['success']) {
                    $realTimeStatus = $statusResult['status'] ?? null;
                    
                    // Update database with real-time status to keep it in sync
                    if ($realTimeStatus) {
                        $whatsappInstance->update([
                            'connect_status' => $realTimeStatus,
                            'last_active_at' => now()
                        ]);
                    }
                    
                    Log::info('Real-time WaSender status fetched', [
                        'instance_id' => $whatsappInstance->instance_id,
                        'status' => $realTimeStatus
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch real-time WaSender status', [
                    'instance_id' => $whatsappInstance->instance_id,
                    'error' => $e->getMessage()
                ]);
                // Fallback to database status if API fails
                $realTimeStatus = $whatsappInstance->connect_status;
            }
        }

        $channels = $this->getBusinessChannelsForUser();
        $agentChannelMatrix = $agents->mapWithKeys(function (AiSalesAgent $agent) {
            return [
                $agent->id => $this->normalizeEnabledChannels($agent->notification_methods ?? []),
            ];
        })->all();

        // Guided outreach-channel config (Email / Phone-SMS / Bulk-SMS).
        $channelConfigs = $this->loadChannelConfigs();

        return view('service.ai-agents.index', compact('agents', 'subscription_plan', 'ai_credits', 'whatsappInstance', 'realTimeStatus', 'channels', 'agentChannelMatrix', 'channelConfigs'));
    }

    /**
     * Show the form for creating a new AI Sales Agent
     */
    public function create()
    {
        $userTypes = UserType::active()->orderBy('name')->get();
        $existingAgent = AiSalesAgent::forUser(Auth::id())->latest()->first();
        $ignoredContactsLine = $this->getIgnoredContactsLineForUser();
        $channels = $this->getBusinessChannelsForUser();
        
        return view('service.job-description', compact('userTypes', 'existingAgent', 'ignoredContactsLine', 'channels'));
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
        $this->assertNotificationMethodsPresent($validatedData);
        
        try {
            DB::beginTransaction();
            
            // Add user ID, status and terms acceptance timestamp
            $validatedData['user_id'] = Auth::id();
            $validatedData['status'] = 'active'; // Set default status
            $validatedData['terms_accepted_at'] = now();
            
            // Create the AI sales agent
            $agent = AiSalesAgent::create($validatedData);

            // Keep ignored contacts on the user's WhatsApp instance in sync
            $this->syncIgnoredContactsFromLine($request);
            
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
        $ignoredContactsLine = $this->getIgnoredContactsLineForUser();
        $channels = $this->getBusinessChannelsForUser();
        
        return view('service.job-description', compact('userTypes', 'existingAgent', 'ignoredContactsLine', 'channels'));
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
            $this->assertNotificationMethodsPresent($validatedData);

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

            // Keep ignored contacts on the user's WhatsApp instance in sync
            $this->syncIgnoredContactsFromLine($request);

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

    public function getChannels()
    {
        try {
            return response()->json([
                'success' => true,
                'channels' => $this->getBusinessChannelsForUser(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load channels.'
            ], 500);
        }
    }

    public function storeChannel(Request $request)
    {
        $business = $this->resolveCurrentBusiness();
        if (! $business) {
            return response()->json(['success' => false, 'message' => 'No business found for current user.'], 422);
        }

        $validated = $request->validate([
            'channel_key' => [
                'required',
                'string',
                'max:30',
                Rule::unique('channels', 'channel_key')->where(function ($query) use ($business) {
                    return $query->where('business_id', $business->id);
                }),
            ],
            'display_name' => 'required|string|max:100',
            'provider' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'priority_rank' => 'nullable|integer|min:1|max:10',
        ]);

        $channel = Channel::create([
            'business_id' => $business->id,
            'channel_key' => strtolower(trim($validated['channel_key'])),
            'display_name' => $validated['display_name'],
            'provider' => $validated['provider'] ?? 'unified_api',
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'priority_rank' => (int) ($validated['priority_rank'] ?? 5),
        ]);

        return response()->json(['success' => true, 'channel' => $channel]);
    }

    public function updateChannel(Request $request, Channel $channel)
    {
        $business = $this->resolveCurrentBusiness();
        if (! $business || $channel->business_id !== $business->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized channel access.'], 403);
        }

        $validated = $request->validate([
            'channel_key' => [
                'required',
                'string',
                'max:30',
                Rule::unique('channels', 'channel_key')
                    ->where(function ($query) use ($business) {
                        return $query->where('business_id', $business->id);
                    })
                    ->ignore($channel->id),
            ],
            'display_name' => 'required|string|max:100',
            'provider' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'priority_rank' => 'nullable|integer|min:1|max:10',
        ]);

        $channel->update([
            'channel_key' => strtolower(trim($validated['channel_key'])),
            'display_name' => $validated['display_name'],
            'provider' => $validated['provider'] ?? $channel->provider,
            'is_active' => (bool) ($validated['is_active'] ?? $channel->is_active),
            'priority_rank' => (int) ($validated['priority_rank'] ?? $channel->priority_rank),
        ]);

        return response()->json(['success' => true, 'channel' => $channel->fresh()]);
    }

    public function destroyChannel(Channel $channel)
    {
        $business = $this->resolveCurrentBusiness();
        if (!$business || $channel->business_id !== $business->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized channel access.'], 403);
        }

        $isInUse = AiSalesAgent::forUser(Auth::id())
            ->whereJsonContains('notification_methods', $channel->channel_key)
            ->exists();

        if ($isInUse) {
            return response()->json([
                'success' => false,
                'message' => 'Channel is currently enabled for one or more agents and cannot be deleted.'
            ], 422);
        }

        $channel->delete();

        return response()->json(['success' => true]);
    }

    public function updateAgentChannels(Request $request, AiSalesAgent $aiSalesAgent)
    {
        if ($aiSalesAgent->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this AI sales agent.'], 403);
        }

        $validated = $request->validate([
            'enabled_channels' => 'nullable|array',
            'enabled_channels.*' => 'string|max:30',
        ]);

        $enabledChannels = $this->normalizeEnabledChannels($validated['enabled_channels'] ?? []);
        $availableChannels = collect($this->getBusinessChannelsForUser())->pluck('channel_key')->all();

        $filteredChannels = array_values(array_intersect($enabledChannels, $availableChannels));
        if (empty($filteredChannels)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one enabled channel is required.'
            ], 422);
        }

        $aiSalesAgent->update([
            'notification_methods' => $filteredChannels,
        ]);

        return response()->json([
            'success' => true,
            'enabled_channels' => $filteredChannels,
        ]);
    }

    /**
     * Load the Email / Phone-SMS / Bulk-SMS channel config for the current business.
     * Phone-SMS exposes a connector code sourced from admin.school_keys.
     */
    private function loadChannelConfigs(): array
    {
        $business = $this->resolveCurrentBusiness();
        $out = [
            'email'     => ['is_active' => false, 'settings' => []],
            'phone_sms' => ['is_active' => false, 'settings' => [], 'code' => null],
            'bulk_sms'  => ['is_active' => false, 'settings' => []],
        ];

        if (! $business) {
            return $out;
        }

        foreach (['email', 'phone_sms', 'bulk_sms'] as $key) {
            $ch = Channel::where('business_id', $business->id)->where('channel_key', $key)->first();
            if ($ch) {
                $out[$key]['is_active'] = (bool) $ch->is_active;
                $out[$key]['settings']  = $ch->settings ?? [];
            }
        }

        // Phone-SMS connector code (read-only; created lazily on enable). Prefer the
        // code already stored on the channel, else resolve/generate one.
        $storedPhoneCode = $out['phone_sms']['settings']['code'] ?? null;
        $out['phone_sms']['code'] = $out['phone_sms']['is_active']
            ? $this->getOrCreatePhoneSmsCode($business, $storedPhoneCode)
            : $storedPhoneCode;

        return $out;
    }

    /**
     * Save a guided outreach-channel config (Email / Phone-SMS / Bulk-SMS).
     */
    public function saveChannelConfig(Request $request)
    {
        $business = $this->resolveCurrentBusiness();
        if (! $business) {
            return back()->with('channel_success', null)->withErrors(['channel' => 'No business found for current user.']);
        }

        $key = $request->input('channel_key');
        if (! in_array($key, ['email', 'phone_sms', 'bulk_sms'], true)) {
            return back()->withErrors(['channel' => 'Invalid channel.']);
        }

        $enable = $request->boolean('is_active');
        $existing = Channel::where('business_id', $business->id)->where('channel_key', $key)->first();
        $settings = $existing ? ($existing->settings ?? []) : [];
        $meta = [
            'email'     => ['name' => 'Email',     'provider' => 'sendgrid', 'rank' => 4],
            'phone_sms' => ['name' => 'Phone SMS', 'provider' => 'twilio',   'rank' => 2],
            'bulk_sms'  => ['name' => 'Bulk SMS',  'provider' => 'bulk_sms', 'rank' => 3],
        ][$key];

        if ($key === 'email' && $enable) {
            $request->validate(['reply_to' => 'required|email']);
            $settings = ['reply_to' => trim($request->input('reply_to'))];
        } elseif ($key === 'bulk_sms' && $enable) {
            $request->validate([
                'url'      => 'required|url',
                'username' => 'required|string|max:191',
                'password' => 'required|string|max:191',
            ]);
            $settings = [
                'url'      => trim($request->input('url')),
                'username' => trim($request->input('username')),
                'password' => $request->input('password'),
            ];
        } elseif ($key === 'phone_sms') {
            // Code is managed via admin.school_keys; ensure it exists when enabling.
            // Always end up with a usable code so the UI can display it.
            $existingCode = $settings['code'] ?? null;
            $code = $enable ? $this->getOrCreatePhoneSmsCode($business, $existingCode) : $existingCode;
            $settings = array_merge($settings, [
                'code'        => $code,
                'schema_name' => $this->resolveBusinessSchemaName($business),
            ]);
        }

        Channel::updateOrCreate(
            ['business_id' => $business->id, 'channel_key' => $key],
            [
                'display_name'  => $meta['name'],
                'provider'      => $meta['provider'],
                'is_active'     => $enable,
                'priority_rank' => $meta['rank'],
                'settings'      => $settings,
            ]
        );

        return back()->with('channel_success', $meta['name'] . ' channel ' . ($enable ? 'enabled' : 'saved') . '.');
    }

    /**
     * Send a test message on a channel so the user can confirm delivery.
     * Currently supports Phone-SMS: inserts a one-off row into notifications.messages
     * (same pipeline as live sends), keyed by this business's schema_name.
     */
    public function testChannel(Request $request)
    {
        $business = $this->resolveCurrentBusiness();
        if (! $business) {
            return $this->testResponse($request, false, 'No business found for current user.');
        }

        if ($request->input('channel_key') !== 'phone_sms') {
            return $this->testResponse($request, false, 'Test is only available for Phone-SMS.');
        }

        $request->validate(['phone' => 'required|string|max:30']);

        // Normalize the phone number using the app helper when available.
        $phone = trim($request->input('phone'));
        if (function_exists('validate_phone_number')) {
            $v = validate_phone_number($phone);
            if (is_array($v) && !empty($v[1])) {
                $phone = $v[1];
            } elseif ($v === false) {
                return $this->testResponse($request, false, 'That phone number looks invalid. Use the full international format, e.g. 2557XXXXXXXX.');
            }
        }

        $schema = $this->resolveBusinessSchemaName($business);

        // Ensure a connector code exists (and persist it on the channel).
        $channel  = Channel::where('business_id', $business->id)->where('channel_key', 'phone_sms')->first();
        $settings = ($channel && is_array($channel->settings)) ? $channel->settings : [];
        $code     = $this->getOrCreatePhoneSmsCode($business, $settings['code'] ?? null);
        if ($channel && ($settings['code'] ?? null) !== $code) {
            $channel->update(['settings' => array_merge($settings, ['code' => $code, 'schema_name' => $schema])]);
        }

        $text = 'Test message from ' . ($business->name ?: 'SafariChat') . ': your Phone-SMS channel is working.';

        try {
            $id = \DB::connection('notification')->table('messages')->insertGetId([
                'channel'     => 'phone-sms',
                'recipient'   => $phone,
                'message'     => $text,
                'status'      => 'pending',
                'priority'    => 'high',
                'schema_name' => $schema,
                'ip_address'  => $request->ip() ?: '127.0.0.1',
                'metadata'    => json_encode(array_filter([
                    'test'           => true,
                    'connector_code' => $code,
                    'source'         => 'safarichat',
                ], fn ($v) => $v !== null && $v !== '')),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return $this->testResponse($request, true, 'Test SMS queued to ' . $phone . '. It should arrive on that phone shortly.', ['id' => $id]);
        } catch (\Throwable $e) {
            Log::error('Phone-SMS test send failed', ['error' => $e->getMessage()]);
            return $this->testResponse($request, false, 'Could not queue the test message: ' . $e->getMessage());
        }
    }

    /**
     * Normalize a channel-test result into JSON (AJAX) or a redirect-back flash.
     */
    private function testResponse(Request $request, bool $ok, string $message, array $extra = [])
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(array_merge(['success' => $ok, 'message' => $message], $extra), $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('channel_success', $message)
            : back()->withErrors(['channel' => $message]);
    }

    /**
     * The business's unified schema name = owner user's uuid.
     */
    private function resolveBusinessSchemaName(Business $business): string
    {
        $owner = \App\Models\User::find($business->user_id);
        return ($owner && $owner->uuid)
            ? $owner->uuid
            : (string) (config('notifications.unified_api.schema_name') ?? $business->id);
    }

    /**
     * Resolve the phone-SMS connector code for this business's schema.
     *
     * The connector code is the admin.school_keys.api_key for this business's
     * schema_name. IMPORTANT: that table lives in the admin database, reachable
     * ONLY via the "admin_crm" connection — not the default (other_app) connection —
     * so all reads/writes here go through that connection. The row is created if
     * missing: schema_name = this instance's uuid (e.g. 78b38e51-…, derived from
     * users.uuid) and api_key = a freshly minted unique numeric code, matching the
     * numeric format of the existing keys. The returned value is the api_key (the
     * login code), never the schema_name.
     */
    private function getOrCreatePhoneSmsCode(Business $business, ?string $existing = null): ?string
    {
        $schema = $this->resolveBusinessSchemaName($business);
        $conn   = self::SCHOOL_KEYS_CONNECTION;

        try {
            $db = \DB::connection($conn);

            // Return the existing api_key for this schema if one is already stored.
            $stored = $db->table('admin.school_keys')->where('schema_name', $schema)->value('api_key');
            if (! empty($stored)) {
                return (string) $stored;
            }

            // None yet: mint a unique numeric code (same shape as existing keys),
            // insert the row (schema_name = instance uuid), and return the api_key.
            $apiKey = $this->generateUniqueSchoolKey($db);
            $db->table('admin.school_keys')->insert([
                'schema_name' => $schema,
                'api_key'     => $apiKey,
                'created_at'  => now(),
            ]);

            return $apiKey;
        } catch (\Throwable $e) {
            Log::warning('Phone-SMS school_keys access failed', [
                'connection' => $conn,
                'schema'     => $schema,
                'error'      => $e->getMessage(),
            ]);
        }

        // Only fall back to a previously stored channel code; never invent one that
        // isn't persisted in admin.school_keys (that would be an invalid login code).
        return ! empty($existing) ? $existing : null;
    }

    /**
     * Mint a unique numeric connector code not already present in admin.school_keys.
     */
    private function generateUniqueSchoolKey(\Illuminate\Database\ConnectionInterface $db): string
    {
        do {
            $candidate = (string) random_int(100000000, 999999999); // 9-digit numeric
        } while ($db->table('admin.school_keys')->where('api_key', $candidate)->exists());

        return $candidate;
    }

    private function resolveCurrentBusiness(): ?Business
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->business) {
            return $user->business;
        }

        return $user->business ?: Business::where('user_id', $user->id)->first();
    }

    private function getBusinessChannelsForUser()
    {
        $business = $this->resolveCurrentBusiness();

        if (! $business) {
            return collect();
        }

        return $business->channels()
            ->orderBy('priority_rank')
            ->orderBy('display_name')
            ->get();
    }

    private function normalizeEnabledChannels($channels): array
    {
        return collect($channels)
            ->map(function ($channel) {
                return strtolower(trim((string) $channel));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function assertNotificationMethodsPresent(array $validatedData): void
    {
        if ($this->getBusinessChannelsForUser()->isNotEmpty() && empty($validatedData['notification_methods'] ?? [])) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Select at least one enabled channel before saving the agent.',
                'errors' => [
                    'notification_methods' => ['Please select at least one enabled channel.'],
                ],
            ], 422));
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
            'ignored_contacts_line' => 'nullable|string|max:2000',
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

    /**
     * Get preferred WhatsApp instance for current user (primary first, then oldest).
     */
    private function getPreferredWhatsappInstanceForUser(): ?\App\Models\WhatsappInstance
    {
        return \App\Models\WhatsappInstance::where('user_id', Auth::id())
            ->orderByDesc('is_primary')
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Pre-fill helper for the single-line ignored contacts input on agent setup.
     */
    private function getIgnoredContactsLineForUser(): string
    {
        $instance = $this->getPreferredWhatsappInstanceForUser();
        if (! $instance) {
            return '';
        }

        return collect($instance->ignored_contacts ?? [])
            ->pluck('phone')
            ->map(function ($phone) {
                return preg_replace('/[^0-9]/', '', (string) $phone);
            })
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');
    }

    /**
     * Sync comma-separated ignored contacts from the agent form to whatsapp instance.
     */
    private function syncIgnoredContactsFromLine(Request $request): void
    {
        $instance = $this->getPreferredWhatsappInstanceForUser();
        if (! $instance) {
            return;
        }

        $line = (string) $request->input('ignored_contacts_line', '');
        $parts = preg_split('/[\n,]+/', $line) ?: [];

        $phones = collect($parts)
            ->map(function ($value) {
                return preg_replace('/[^0-9]/', '', trim((string) $value));
            })
            ->filter()
            ->unique()
            ->values();

        $ignoredContacts = $phones
            ->map(function ($phone) {
                return ['phone' => $phone];
            })
            ->all();

        $instance->update(['ignored_contacts' => $ignoredContacts]);
    }
}
