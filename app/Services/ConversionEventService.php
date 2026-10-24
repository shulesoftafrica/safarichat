<?php

namespace App\Services;

use App\Models\AiSalesAgent;
use App\Models\Business;
use App\Models\BusinessContact;
use App\Models\ConversionEvent;
use App\Models\EventConfig;
use App\Models\Lead;
use App\Models\LeadCommitment;
use App\Models\User;
use App\Models\WhatsappInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Turns an incoming conversion signal into the WON flow:
 *   1. record the event (with the raw payload, de-duplicated),
 *   2. match it to a lead / contact in the business,
 *   3. apply the configured status + actions — congratulate, stop selling,
 *      hand off to the after-sale agent, notify the owner, record revenue,
 *   4. fulfil any matching open commitment.
 *
 * "Won" is two milestones (see the owner's decision):
 *   - CONVERTED: application/form submitted or trial started (not yet paying)
 *   - CLOSED:    the customer has paid
 */
class ConversionEventService
{
    private WaSenderService $waSender;

    public function __construct(?WaSenderService $waSender = null)
    {
        $this->waSender = $waSender ?: app(WaSenderService::class);
    }

    /**
     * Record and process an event received through a configured webhook.
     *
     * @param array       $payload Raw request body.
     * @param EventConfig  $config The config whose token was used.
     * @return ConversionEvent
     */
    public function handleWebhook(array $payload, EventConfig $config): ConversionEvent
    {
        $map = $config->field_map ?: [];

        $get = function (string $key, array $fallbacks = []) use ($payload, $map) {
            // Honour an explicit field mapping first, then common field names.
            $candidates = [];
            if (!empty($map[$key])) {
                $candidates[] = $map[$key];
            }
            $candidates = array_merge($candidates, [$key], $fallbacks);
            foreach ($candidates as $c) {
                if ($c !== null && array_key_exists($c, $payload) && $payload[$c] !== '' && $payload[$c] !== null) {
                    return $payload[$c];
                }
            }
            return null;
        };

        $data = [
            'business_id'     => $config->business_id,
            'event_config_id' => $config->id,
            'event_type'      => $config->event_type,
            'source'          => 'webhook',
            'phone'           => $get('phone', ['msisdn', 'mobile', 'phone_number', 'whatsapp']),
            'email'           => $get('email', ['email_address', 'mail']),
            'amount'          => $get('amount', ['value', 'total', 'price', 'amount_paid']),
            'currency'        => $get('currency', ['ccy']),
            'reference'       => $get('reference', ['ref', 'order_no', 'order_id', 'receipt', 'invoice', 'application_id']),
            'occurred_at'     => $get('occurred_at', ['date', 'timestamp', 'paid_at']),
            'note'            => $get('note', ['message', 'description']),
            'idempotency_key' => $get('idempotency_key', ['event_id', 'id']),
            'metadata'        => $payload,
        ];

        $config->forceFill([
            'last_received_at' => now(),
            'received_count'   => ($config->received_count ?? 0) + 1,
            'last_error'       => null,
        ])->save();

        return $this->record($data, $config, $config->match_by ?: 'phone');
    }

    /**
     * Record and process an event from any source (webhook or internal).
     *
     * @param array            $data   Normalised fields (see handleWebhook).
     * @param EventConfig|null $config
     * @param string           $matchBy phone|email
     */
    public function record(array $data, ?EventConfig $config = null, string $matchBy = 'phone'): ConversionEvent
    {
        $businessId = $data['business_id'];

        // Idempotency: never double-process the same source event.
        if (!empty($data['idempotency_key'])) {
            $existing = ConversionEvent::where('business_id', $businessId)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $phone = !empty($data['phone']) ? sanitize_phone_number($data['phone']) : null;
        $email = !empty($data['email']) ? strtolower(trim($data['email'])) : null;

        $identifier     = $matchBy === 'email' ? $email : $phone;
        $identifierType = $matchBy === 'email' ? 'email' : 'phone';
        // Fall back to whichever identifier we actually have.
        if (!$identifier) {
            $identifier     = $phone ?: $email;
            $identifierType = $phone ? 'phone' : ($email ? 'email' : null);
        }

        $event = ConversionEvent::create([
            'business_id'         => $businessId,
            'event_config_id'     => $config?->id,
            'event_type'          => $data['event_type'] ?? ($config->event_type ?? 'custom'),
            'source'              => $data['source'] ?? 'manual',
            'identifier'          => $identifier,
            'identifier_type'     => $identifierType,
            'amount'              => $this->toAmount($data['amount'] ?? null),
            'currency'            => $data['currency'] ?? null,
            'reference'           => $data['reference'] ?? null,
            'metadata'            => $data['metadata'] ?? null,
            'idempotency_key'     => $data['idempotency_key'] ?? null,
            'occurred_at'         => $this->toDate($data['occurred_at'] ?? null) ?? now(),
            'status'              => ConversionEvent::STATUS_RECEIVED,
        ]);

        try {
            $this->match($event, $phone, $email);
            $this->process($event, $config);
        } catch (\Throwable $e) {
            Log::error('Conversion event processing failed', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
            ]);
            $event->update(['status' => ConversionEvent::STATUS_FAILED]);
            if ($config) {
                $config->forceFill(['last_error' => $e->getMessage()])->save();
            }
        }

        return $event->fresh();
    }

    /**
     * Find the lead/contact this event belongs to, within the business.
     */
    private function match(ConversionEvent $event, ?string $phone, ?string $email): void
    {
        $businessId = $event->business_id;

        $contact = null;
        if ($phone) {
            $contact = BusinessContact::where('business_id', $businessId)
                ->where('guest_phone', $phone)->first();
        }
        if (!$contact && $email) {
            $contact = BusinessContact::where('business_id', $businessId)
                ->whereRaw('LOWER(guest_email) = ?', [$email])->first();
        }

        $lead = null;
        if ($contact) {
            $lead = Lead::where('business_id', $businessId)
                ->where('business_contact_id', $contact->id)
                ->latest()->first();
        }
        if (!$lead && $phone) {
            $lead = Lead::where('business_id', $businessId)
                ->where('phone_number', $phone)->latest()->first();
        }
        if (!$lead && $email) {
            $lead = Lead::where('business_id', $businessId)
                ->whereRaw('LOWER(email) = ?', [$email])->latest()->first();
        }
        if (!$contact && $lead && $lead->business_contact_id) {
            $contact = BusinessContact::find($lead->business_contact_id);
        }

        $event->lead_id             = $lead?->id;
        $event->business_contact_id = $contact?->id;
        $event->status = ($lead || $contact)
            ? ConversionEvent::STATUS_MATCHED
            : ConversionEvent::STATUS_ORPHAN;
        $event->save();
    }

    /**
     * Apply the configured status + actions for a matched event.
     */
    private function process(ConversionEvent $event, ?EventConfig $config): void
    {
        $lead    = $event->lead_id ? Lead::find($event->lead_id) : null;
        $contact = $event->business_contact_id ? BusinessContact::find($event->business_contact_id) : null;

        // Nothing to act on — keep it as an orphan for the owner to reconcile.
        if (!$lead && !$contact) {
            $this->notifyOwnerOrphan($event, $config);
            return;
        }

        $agent   = $this->resolveAgent($event->business_id, $lead);
        $actions = $config?->actions ?? [
            EventConfig::ACTION_CONGRATULATE,
            EventConfig::ACTION_STOP_SELLING,
            EventConfig::ACTION_HANDOFF_AFTER_SALE,
            EventConfig::ACTION_NOTIFY_OWNER,
            EventConfig::ACTION_RECORD_REVENUE,
        ];

        // 1. Status. Default: form/signup → CONVERTED, payment/purchase → CLOSED.
        $status = $config?->set_status ?: $this->defaultStatusFor($event->event_type);
        if ($lead && $status) {
            $update = ['status' => $status, 'last_interaction_at' => now()];
            if (in_array(EventConfig::ACTION_RECORD_REVENUE, $actions, true) && $event->amount !== null) {
                $update['deal_value']  = $event->amount;
                $update['final_price'] = $event->amount;
            }
            $lead->update($update);
        }

        // 2. Stop selling + hand off to the after-sale agent.
        if ($contact) {
            if (in_array(EventConfig::ACTION_STOP_SELLING, $actions, true)
                || in_array(EventConfig::ACTION_HANDOFF_AFTER_SALE, $actions, true)) {
                $reason = 'Won — ' . ($status ?: 'converted') . '. After-sale support.';
                // Set handoff fields directly (priority_level is an integer column
                // with its own default — leave it untouched).
                $contact->forceFill([
                    'handoff_status'       => 'pending_handoff',
                    'handoff_reason'       => $reason,
                    'handoff_notes'        => 'Auto handoff after conversion event #' . $event->id,
                    'handoff_requested_at' => now(),
                ])->save();

                $afterSaleUserId = $this->resolveAfterSaleUserId($agent, $event->business_id);
                if ($afterSaleUserId) {
                    $contact->assignToAgent($afterSaleUserId);
                }
            }
        }

        // 3. Congratulate the customer (once).
        if (in_array(EventConfig::ACTION_CONGRATULATE, $actions, true)) {
            $this->sendCongratulations($event, $lead, $contact, $config, $agent);
        }

        // 4. Hand off to the after-sale agent's phone (notify them).
        if (in_array(EventConfig::ACTION_HANDOFF_AFTER_SALE, $actions, true)) {
            $this->notifyAfterSaleAgent($event, $lead, $contact, $agent);
        }

        // 5. Notify the owner/team.
        if (in_array(EventConfig::ACTION_NOTIFY_OWNER, $actions, true)) {
            $this->notifyOwner($event, $lead, $contact, $status);
        }

        // 6. Fulfil any open commitment this event satisfies.
        if ($lead) {
            $this->fulfilCommitments($event, $lead);
        }

        $event->update([
            'status'       => ConversionEvent::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    /**
     * CONVERTED for a form/signup/registration (in-trial, not yet paying);
     * CLOSED once money changes hands (purchase/payment).
     */
    private function defaultStatusFor(string $eventType): string
    {
        return in_array($eventType, [
            EventConfig::TYPE_PURCHASE,
            EventConfig::TYPE_PAYMENT,
        ], true)
            ? Lead::STATUS_CLOSED
            : Lead::STATUS_CONVERTED;
    }

    private function fulfilCommitments(ConversionEvent $event, Lead $lead): void
    {
        $commitments = LeadCommitment::where('lead_id', $lead->id)
            ->where('status', LeadCommitment::STATUS_PENDING)
            ->get();

        foreach ($commitments as $commitment) {
            // Match on expected type when set; otherwise fulfil the oldest pending.
            if ($commitment->expected_event_type
                && $commitment->expected_event_type !== $event->event_type) {
                continue;
            }
            $commitment->update([
                'status'             => LeadCommitment::STATUS_FULFILLED,
                'fulfilled_event_id' => $event->id,
            ]);
        }
    }

    // ---------------------------------------------------------------------
    // Messaging
    // ---------------------------------------------------------------------

    private function sendCongratulations(ConversionEvent $event, ?Lead $lead, ?BusinessContact $contact, ?EventConfig $config, ?AiSalesAgent $agent): void
    {
        $phone = $contact->guest_phone ?? $lead?->phone_number;
        if (!$phone) { return; }

        $name = $contact->guest_name ?? $lead?->name ?? 'there';
        $template = $config?->message_template;

        if ($template) {
            $message = strtr($template, [
                '{name}'      => $name,
                '{reference}' => $event->reference ?? '',
                '{amount}'    => $event->amount !== null ? number_format((float) $event->amount, 2) : '',
                '{currency}'  => $event->currency ?? '',
            ]);
        } else {
            $message = "Thank you, {$name}! 🎉 We've received your "
                . $this->eventNoun($event->event_type)
                . ($event->reference ? " (ref {$event->reference})" : '')
                . ". Our team will take great care of you from here. Welcome aboard! 🙌";
        }

        $userId = $this->resolveSendingUserId($agent, $lead, $event->business_id);
        $this->safeSend($phone, $message, $userId);
    }

    private function notifyAfterSaleAgent(ConversionEvent $event, ?Lead $lead, ?BusinessContact $contact, ?AiSalesAgent $agent): void
    {
        $afterSalePhone = $agent?->after_sale_agent_phone;
        if (!$afterSalePhone) { return; }

        $customer = $contact->guest_name ?? $lead?->name ?? 'A customer';
        $cphone   = $contact->guest_phone ?? $lead?->phone_number ?? '';

        $text  = "🤝 *New won customer — over to you*\n\n";
        $text .= "{$customer} just completed a " . $this->eventNoun($event->event_type) . ".\n";
        if ($cphone !== '') { $text .= "📞 {$cphone}\n"; }
        if ($event->reference) { $text .= "🧾 Ref: {$event->reference}\n"; }
        if ($event->amount !== null) {
            $text .= "💰 " . ($event->currency ? $event->currency . ' ' : '') . number_format((float) $event->amount, 2) . "\n";
        }
        $text .= "\nThey've been handed to you for after-sale support. Please reach out and look after them.";

        $userId = $this->resolveSendingUserId($agent, $lead, $event->business_id);
        $this->safeSend($afterSalePhone, $text, $userId);
    }

    private function notifyOwner(ConversionEvent $event, ?Lead $lead, ?BusinessContact $contact, ?string $status): void
    {
        $business = Business::find($event->business_id);
        if (!$business) { return; }

        $customer = $contact->guest_name ?? $lead?->name ?? 'A customer';
        $label    = $status === Lead::STATUS_CLOSED ? 'paying customer (CLOSED)' : 'converted customer (CONVERTED)';

        $text  = "🎉 *You won a customer!*\n\n";
        $text .= "{$customer} is now a {$label}.\n";
        if ($event->amount !== null) {
            $text .= "💰 " . ($event->currency ? $event->currency . ' ' : '') . number_format((float) $event->amount, 2) . "\n";
        }
        if ($event->reference) { $text .= "🧾 Ref: {$event->reference}\n"; }
        $text .= "\nThe AI has stopped selling to them and handed them to after-sale support.";

        $recipients = [];
        foreach ([$business->user_id ?? null] as $uid) {
            if (!$uid) { continue; }
            $u = User::find($uid);
            if ($u && !empty($u->phone)) {
                $recipients[preg_replace('/\D+/', '', $u->phone)] = ['phone' => $u->phone, 'user_id' => $u->id];
            }
        }

        foreach ($recipients as $r) {
            $this->safeSend($r['phone'], $text, $r['user_id']);
        }
    }

    private function notifyOwnerOrphan(ConversionEvent $event, ?EventConfig $config): void
    {
        Log::info('Conversion event had no matching lead/contact', [
            'event_id'   => $event->id,
            'identifier' => $event->identifier,
        ]);
        // Left as an orphan row the owner can reconcile from the dashboard.
    }

    // ---------------------------------------------------------------------
    // Resolution helpers
    // ---------------------------------------------------------------------

    private function resolveAgent(int $businessId, ?Lead $lead): ?AiSalesAgent
    {
        if ($lead && $lead->ai_sales_agent_id) {
            $a = AiSalesAgent::find($lead->ai_sales_agent_id);
            if ($a) { return $a; }
        }
        $business = Business::find($businessId);
        if ($business && $business->user_id) {
            return AiSalesAgent::where('user_id', $business->user_id)->latest()->first();
        }
        return null;
    }

    /**
     * The user that handles after-sale support: the agent's configured number
     * mapped to a user, else the business owner.
     */
    private function resolveAfterSaleUserId(?AiSalesAgent $agent, int $businessId): ?int
    {
        if ($agent && $agent->after_sale_agent_phone) {
            $phone = sanitize_phone_number($agent->after_sale_agent_phone);
            $user  = User::whereRaw("regexp_replace(phone, '\\D', '', 'g') = ?", [preg_replace('/\D+/', '', $phone)])->first();
            if ($user) { return $user->id; }
        }
        $business = Business::find($businessId);
        return $business->user_id ?? null;
    }

    private function resolveSendingUserId(?AiSalesAgent $agent, ?Lead $lead, int $businessId): ?int
    {
        if ($agent && $agent->user_id) { return $agent->user_id; }
        if ($lead && $lead->user_id) { return $lead->user_id; }
        $business = Business::find($businessId);
        return $business->user_id ?? null;
    }

    /**
     * Best-effort WhatsApp send — a provider rejection must never abort the WON
     * flow (status, revenue and handoff are already committed by this point).
     */
    private function safeSend(?string $phone, string $text, ?int $userId): void
    {
        if (!$phone) { return; }
        try {
            $instance = $this->resolveInstance($userId);
            if (!$instance) { return; }
            $this->waSender->sendMessage($phone, $text, [], $instance, $userId);
        } catch (\Throwable $e) {
            Log::warning('Conversion event notification send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveInstance(?int $userId): ?WhatsappInstance
    {
        if (!$userId) { return null; }

        return WhatsappInstance::where('user_id', $userId)
                ->whereIn('status', ['connected', 'ready'])
                ->latest()->first()
            ?: WhatsappInstance::where('user_id', $userId)->latest()->first();
    }

    // ---------------------------------------------------------------------
    // Small utilities
    // ---------------------------------------------------------------------

    private function eventNoun(string $eventType): string
    {
        return [
            EventConfig::TYPE_PURCHASE       => 'purchase',
            EventConfig::TYPE_PAYMENT        => 'payment',
            EventConfig::TYPE_FORM_SUBMITTED => 'application',
            EventConfig::TYPE_SIGNUP         => 'sign-up',
            EventConfig::TYPE_REGISTRATION   => 'registration',
        ][$eventType] ?? 'order';
    }

    private function toAmount($value): ?float
    {
        if ($value === null || $value === '') { return null; }
        if (is_numeric($value)) { return (float) $value; }
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($clean) ? (float) $clean : null;
    }

    private function toDate($value): ?Carbon
    {
        if (empty($value)) { return null; }
        try {
            return is_numeric($value)
                ? Carbon::createFromTimestamp((int) $value)
                : Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
