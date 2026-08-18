<?php

namespace App\Services\MultiChannel;

use App\Jobs\SendWhatsAppMessage;
use App\Jobs\SendChannelMessage;
use App\Models\AiSalesAgent;
use App\Models\BusinessContact;
use App\Models\Lead;
use App\Models\OutgoingMessage;
use App\Models\User;
use App\Models\WhatsappInstance;

class OutboundOrchestratorService
{
    public function __construct(
        private ChannelSelectionService $channelSelectionService,
        private ChannelPayloadBuilder $channelPayloadBuilder,
        private RolloutGateService $rolloutGateService
    ) {
    }

    public function dispatchForLead(Lead $lead, string $message, array $context = []): array
    {
        $contact = $lead->contact;
        if (!$contact) {
            return [
                'success' => false,
                'error' => 'Lead has no contact',
            ];
        }

        $agent = $context['agent'] ?? $lead->aiSalesAgent;
        $selection = $this->resolveSelection($contact, $agent, $context);

        [$channel, $recipient] = $this->resolveRecipientForSelection($contact, $selection['fallback_chain'] ?? [$selection['selected_channel']]);

        if (!$channel || !$recipient) {
            return [
                'success' => false,
                'error' => 'No eligible channel recipient found for contact',
            ];
        }

        $userId = $context['user_id'] ?? ($agent?->user_id ?? $lead->user_id);
        $schemaName = $this->resolveSchemaName((int) $userId, $context);
        $priority = $context['priority'] ?? 'normal';
        $provider = $context['provider'] ?? $this->defaultProviderFor($channel);

        $payloadContext = [
            'schema_name' => $schemaName,
            'to' => $recipient,
            'message' => $message,
            'provider' => $provider,
            'priority' => $priority,
            'subject' => $context['subject'] ?? null,
            'campaign_id' => $context['campaign_id'] ?? null,
            'extras' => $context['extras'] ?? [],
        ];

        $payload = $this->channelPayloadBuilder->build($channel, $payloadContext);

        $selectionReason = (string) ($selection['channel_selection_reason'] ?? 'score_based');
        if ($channel !== ($selection['selected_channel'] ?? $channel)) {
            $selectionReason = 'recipient_unavailable_fallback|' . $selectionReason;
        }

        $outgoingMessage = OutgoingMessage::create([
            'user_id' => $userId,
            'business_contact_id' => $contact->id,
            'phone_number' => $recipient,
            'message_body' => $message,
            'message_type' => 'text',
            'status' => 'pending',
            'provider' => $provider,
            'priority' => $priority,
            'queued_at' => now(),
            'retry_count' => 0,
            'selected_channel' => $channel,
            'channel_selection_reason' => $selectionReason,
            'fallback_chain' => $selection['fallback_chain'] ?? [$channel],
            'channel_attempt' => 1,
            'transport_endpoint' => rtrim((string) config('multi_channel.transport.base_url', ''), '/') . '/notifications/send',
            'transport_payload' => $payload,
            'metadata' => [
                'lead_id' => $lead->id,
                'agent_id' => $agent?->id,
                'campaign' => $context['campaign'] ?? null,
                'phase4_orchestrated' => true,
            ],
        ]);

        $featureEnabled = $this->rolloutGateService->isEnabledForUser((int) $userId, (int) $contact->business_id);

        if (!$featureEnabled && $channel === 'whatsapp') {
            $instance = WhatsappInstance::where('user_id', $userId)
                ->whereIn('status', ['connected', 'active'])
                ->first();

            if (!$instance) {
                $outgoingMessage->update([
                    'status' => 'failed',
                    'error_message' => 'No active WhatsApp instance found',
                ]);

                return [
                    'success' => false,
                    'error' => 'No active WhatsApp instance found',
                ];
            }

            SendWhatsAppMessage::dispatch(
                $message,
                $recipient,
                'whatsapp',
                $userId,
                null,
                $instance->instance_id,
                [
                    'provider' => 'wa_sender',
                    'priority' => $priority,
                    'outgoing_message_id' => $outgoingMessage->id,
                    'message_type' => $context['message_type'] ?? null,
                ]
            );
        } else {
            SendChannelMessage::dispatch($outgoingMessage->id, $payload, ['channel' => $channel]);
        }

        return [
            'success' => true,
            'queued' => true,
            'selected_channel' => $channel,
            'outgoing_message_id' => $outgoingMessage->id,
            'fallback_chain' => $selection['fallback_chain'] ?? [$channel],
        ];
    }

    /**
     * Dispatch outbound message for flows without a lead record (API/manual/system jobs).
     */
    public function dispatchDirect(int $userId, string $message, array $context = []): array
    {
        $channel = strtolower((string) ($context['channel'] ?? 'whatsapp'));
        $recipient = (string) ($context['to'] ?? '');

        if ($recipient === '') {
            return [
                'success' => false,
                'error' => 'Recipient is required',
            ];
        }

        $priority = (string) ($context['priority'] ?? 'normal');
        $provider = (string) ($context['provider'] ?? $this->defaultProviderFor($channel));
        $schemaName = $this->resolveSchemaName($userId, $context);

        $payload = $this->channelPayloadBuilder->build($channel, [
            'schema_name' => $schemaName,
            'to' => $recipient,
            'message' => $message,
            'provider' => $provider,
            'priority' => $priority,
            'subject' => $context['subject'] ?? null,
            'campaign_id' => $context['campaign_id'] ?? null,
            'extras' => $context['extras'] ?? [],
        ]);

        $outgoingMessage = null;
        if (!empty($context['outgoing_message_id'])) {
            $outgoingMessage = OutgoingMessage::find((int) $context['outgoing_message_id']);
        }

        $metadata = $context['metadata'] ?? [];
        $metadata['phase4_orchestrated'] = true;

        if ($outgoingMessage) {
            $outgoingMessage->update([
                'user_id' => $userId,
                'business_contact_id' => $context['business_contact_id'] ?? $outgoingMessage->business_contact_id,
                'phone_number' => $recipient,
                'message_body' => $message,
                'message_type' => $context['message_type'] ?? $outgoingMessage->message_type ?? 'text',
                'status' => 'pending',
                'provider' => $provider,
                'priority' => $priority,
                'queued_at' => now(),
                'selected_channel' => $channel,
                'channel_selection_reason' => $context['channel_selection_reason'] ?? 'direct_dispatch',
                'fallback_chain' => [$channel],
                'channel_attempt' => 1,
                'transport_endpoint' => rtrim((string) config('multi_channel.transport.base_url', ''), '/') . '/notifications/send',
                'transport_payload' => $payload,
                'metadata' => $metadata,
            ]);
        } else {
            $outgoingMessage = OutgoingMessage::create([
                'user_id' => $userId,
                'business_contact_id' => $context['business_contact_id'] ?? null,
                'phone_number' => $recipient,
                'message_body' => $message,
                'message_type' => $context['message_type'] ?? 'text',
                'status' => 'pending',
                'provider' => $provider,
                'priority' => $priority,
                'queued_at' => now(),
                'retry_count' => 0,
                'selected_channel' => $channel,
                'channel_selection_reason' => $context['channel_selection_reason'] ?? 'direct_dispatch',
                'fallback_chain' => [$channel],
                'channel_attempt' => 1,
                'transport_endpoint' => rtrim((string) config('multi_channel.transport.base_url', ''), '/') . '/notifications/send',
                'transport_payload' => $payload,
                'metadata' => $metadata,
            ]);
        }

        $businessId = isset($context['business_id']) ? (int) $context['business_id'] : null;
        $featureEnabled = $this->rolloutGateService->isEnabledForUser($userId, $businessId);
        $delaySeconds = (int) ($context['delay_seconds'] ?? 0);

        if (!$featureEnabled && $channel === 'whatsapp') {
            $instance = null;

            if (!empty($context['whatsapp_instance_id'])) {
                $instance = WhatsappInstance::find((int) $context['whatsapp_instance_id']);
            }

            if (!$instance && !empty($context['instance_id'])) {
                $instance = WhatsappInstance::where('instance_id', (string) $context['instance_id'])->first();
            }

            if (!$instance) {
                $instance = WhatsappInstance::where('user_id', $userId)
                    ->whereIn('status', ['connected', 'active'])
                    ->first();
            }

            if (!$instance) {
                $outgoingMessage->update([
                    'status' => 'failed',
                    'error_message' => 'No active WhatsApp instance found',
                ]);

                return [
                    'success' => false,
                    'error' => 'No active WhatsApp instance found',
                ];
            }

            $job = SendWhatsAppMessage::dispatch(
                $message,
                $recipient,
                $context['source'] ?? 'whatsapp',
                $userId,
                $context['files'] ?? null,
                $instance->instance_id,
                [
                    'whatsapp_instance_id' => $instance->id,
                    'provider' => $provider,
                    'priority' => $priority,
                    'outgoing_message_id' => $outgoingMessage->id,
                    'message_type' => $context['message_type'] ?? null,
                ]
            );

            if ($delaySeconds > 0) {
                $job->delay(now()->addSeconds($delaySeconds));
            }
        } else {
            $job = SendChannelMessage::dispatch($outgoingMessage->id, $payload, ['channel' => $channel]);
            if ($delaySeconds > 0) {
                $job->delay(now()->addSeconds($delaySeconds));
            }
        }

        return [
            'success' => true,
            'queued' => true,
            'selected_channel' => $channel,
            'outgoing_message_id' => $outgoingMessage->id,
            'fallback_chain' => [$channel],
        ];
    }

    private function resolveSelection(BusinessContact $contact, ?AiSalesAgent $agent, array $context): array
    {
        $userId = (int) ($context['user_id'] ?? $agent?->user_id ?? 0);
        if ($userId <= 0 || !$this->rolloutGateService->isEnabledForUser($userId, (int) $contact->business_id)) {
            return [
                'selected_channel' => 'whatsapp',
                'channel_selection_reason' => 'feature_flag_off',
                'fallback_chain' => ['whatsapp', 'email', 'phone_sms', 'bulk_sms'],
            ];
        }

        return $this->channelSelectionService->select($contact, $agent, [
            'product_id' => $context['product_id'] ?? null,
            'requires_formal' => (bool) ($context['requires_formal'] ?? false),
        ]);
    }

    private function resolveRecipientForSelection(BusinessContact $contact, array $fallbackChain): array
    {
        foreach ($fallbackChain as $channel) {
            $recipient = $this->recipientForChannel($contact, (string) $channel);
            if ($recipient !== null && $recipient !== '') {
                return [(string) $channel, $recipient];
            }
        }

        return [null, null];
    }

    private function recipientForChannel(BusinessContact $contact, string $channel): ?string
    {
        return match (strtolower($channel)) {
            'email' => $contact->guest_email,
            default => $contact->guest_phone,
        };
    }

    private function resolveSchemaName(int $userId, array $context): string
    {
        if (!empty($context['schema_name'])) {
            return (string) $context['schema_name'];
        }

        $user = User::find($userId);
        return (string) ($user?->uuid ?: $userId ?: 'default');
    }

    private function defaultProviderFor(string $channel): string
    {
        return match (strtolower($channel)) {
            'email' => 'sendgrid',
            'phone_sms', 'bulk_sms' => 'internal_sms_api',
            default => 'wa_sender',
        };
    }
}
