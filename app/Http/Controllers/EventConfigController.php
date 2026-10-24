<?php

namespace App\Http\Controllers;

use App\Models\AiSalesAgent;
use App\Models\EventConfig;
use App\Services\ConversionEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Owner-side CRUD for conversion events (the "Events & Automations" tab on the
 * AI Sales Agent page). Gated to Pro-and-above plans.
 */
class EventConfigController extends Controller
{
    /** Plans allowed to use conversion events. */
    const ALLOWED_PLANS = ['pro', 'premium', 'business', 'enterprise'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $business = $this->business();
        $this->authorizePlan();

        $data = $this->validatePayload($request);

        $config = EventConfig::create([
            'business_id'      => $business->id,
            'ai_sales_agent_id'=> $data['ai_sales_agent_id'] ?? null,
            'name'             => $data['name'],
            'event_type'       => $data['event_type'],
            'token'            => EventConfig::generateToken(),
            'is_enabled'       => $request->boolean('is_enabled', true),
            'set_status'       => $data['set_status'] ?? null,
            'actions'          => $data['actions'] ?? array_keys(EventConfig::availableActions()),
            'message_template' => $data['message_template'] ?? null,
            'match_by'         => $data['match_by'] ?? 'phone',
            'field_map'        => $data['field_map'] ?? null,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Event created. Use its webhook URL to start receiving events.',
            'event'       => $this->present($config),
        ]);
    }

    public function update(Request $request, EventConfig $eventConfig)
    {
        $this->business();
        $this->authorizeOwnership($eventConfig);
        $this->authorizePlan();

        $data = $this->validatePayload($request);

        $eventConfig->update([
            'ai_sales_agent_id'=> $data['ai_sales_agent_id'] ?? $eventConfig->ai_sales_agent_id,
            'name'             => $data['name'],
            'event_type'       => $data['event_type'],
            'is_enabled'       => $request->boolean('is_enabled', $eventConfig->is_enabled),
            'set_status'       => $data['set_status'] ?? null,
            'actions'          => $data['actions'] ?? $eventConfig->actions,
            'message_template' => $data['message_template'] ?? null,
            'match_by'         => $data['match_by'] ?? $eventConfig->match_by,
            'field_map'        => $data['field_map'] ?? $eventConfig->field_map,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated.',
            'event'   => $this->present($eventConfig->fresh()),
        ]);
    }

    public function toggle(Request $request, EventConfig $eventConfig)
    {
        $this->business();
        $this->authorizeOwnership($eventConfig);
        $this->authorizePlan();

        $eventConfig->update(['is_enabled' => !$eventConfig->is_enabled]);

        return response()->json([
            'success'    => true,
            'is_enabled' => $eventConfig->is_enabled,
        ]);
    }

    public function destroy(EventConfig $eventConfig)
    {
        $this->business();
        $this->authorizeOwnership($eventConfig);

        $eventConfig->delete();

        return response()->json(['success' => true, 'message' => 'Event deleted.']);
    }

    /**
     * Fire a sample event at this config so the owner can verify the whole flow
     * end-to-end (it runs against their own phone number).
     */
    public function test(Request $request, EventConfig $eventConfig, ConversionEventService $service)
    {
        $this->business();
        $this->authorizeOwnership($eventConfig);
        $this->authorizePlan();

        $phone = $request->input('phone') ?: Auth::user()->phone;
        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Add a phone number to your profile, or pass one, to run a test.',
            ], 422);
        }

        $event = $service->handleWebhook([
            'phone'     => $phone,
            'reference' => 'TEST-' . strtoupper(substr(md5((string) microtime(true)), 0, 6)),
            'amount'    => $request->input('amount', 0),
            'note'      => 'Test event from the dashboard',
        ], $eventConfig);

        return response()->json([
            'success' => true,
            'message' => $event->status === \App\Models\ConversionEvent::STATUS_ORPHAN
                ? 'Event received, but no lead/contact matched that phone number yet.'
                : 'Test event processed — the contact was marked won.',
            'result'  => $event->status,
        ]);
    }

    /**
     * Save the after-sale support number on the owner's AI agent (from the
     * Events & Automations tab, without touching the full agent form).
     */
    public function afterSalePhone(Request $request)
    {
        $this->business();
        $this->authorizePlan();

        $data = $request->validate([
            'after_sale_agent_phone' => 'nullable|string|max:30',
        ]);

        $agent = AiSalesAgent::where('user_id', Auth::id())->latest()->first();
        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Create your AI agent first.'], 422);
        }

        $agent->update(['after_sale_agent_phone' => $data['after_sale_agent_phone'] ?? null]);

        return response()->json([
            'success' => true,
            'message' => 'After-sale support number saved.',
            'after_sale_agent_phone' => $agent->after_sale_agent_phone,
        ]);
    }

    // ---------------------------------------------------------------------

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:120',
            'event_type'       => ['required', Rule::in(array_keys(EventConfig::types()))],
            'set_status'       => 'nullable|string|max:30',
            'actions'          => 'nullable|array',
            'actions.*'        => ['string', Rule::in(array_keys(EventConfig::availableActions()))],
            'message_template' => 'nullable|string|max:1000',
            'match_by'         => ['nullable', Rule::in(['phone', 'email'])],
            'field_map'        => 'nullable|array',
            'ai_sales_agent_id'=> 'nullable|integer',
        ]);
    }

    private function business()
    {
        $business = Auth::user()->business;
        abort_if(!$business, 403, 'No business for this account.');
        return $business;
    }

    private function authorizeOwnership(EventConfig $config): void
    {
        abort_if($config->business_id !== Auth::user()->business->id, 403, 'Not your event.');
    }

    private function authorizePlan(): void
    {
        abort_unless($this->planAllowsEvents(), 403, 'Conversion events are available on the Pro plan and above.');
    }

    private function planAllowsEvents(): bool
    {
        $plan = optional(Auth::user()->business->billingAccount)->subscription_plan ?? 'trial';
        return in_array($plan, self::ALLOWED_PLANS, true);
    }

    private function present(EventConfig $config): array
    {
        return [
            'id'          => $config->id,
            'name'        => $config->name,
            'event_type'  => $config->event_type,
            'is_enabled'  => $config->is_enabled,
            'set_status'  => $config->set_status,
            'actions'     => $config->actions,
            'match_by'    => $config->match_by,
            'webhook_url' => $config->webhookUrl(),
            'received_count'   => $config->received_count,
            'last_received_at' => optional($config->last_received_at)->toDateTimeString(),
        ];
    }
}
