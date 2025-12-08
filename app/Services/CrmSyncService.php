<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\EventsGuest;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CrmSyncService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('crm_sync');
    }

    /**
     * Push lead updates to external CRM
     */
    public function pushLeadToExternalCrm(Lead $lead): array
    {
        try {
            $leadData = $this->formatLeadForExternalCrm($lead);
            
            // Make API call to external CRM
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json'
            ])->post($this->config['endpoints']['leads'], $leadData);

            if ($response->successful()) {
                // Update lead with external CRM ID if provided
                if ($response->json('id')) {
                    $lead->update([
                        'metadata' => array_merge($lead->metadata ?? [], [
                            'external_crm_id' => $response->json('id'),
                            'last_crm_sync' => now()->toISOString()
                        ])
                    ]);
                }

                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->body()];

        } catch (\Exception $e) {
            Log::error('Failed to push lead to external CRM', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Format lead data for external CRM
     */
    private function formatLeadForExternalCrm(Lead $lead): array
    {
        return [
            'contact' => [
                'name' => $lead->contact->guest_name,
                'phone' => $lead->contact->guest_phone,
                'email' => $lead->contact->guest_email,
            ],
            'company_name' => $lead->company_name,
            'industry' => $lead->industry,
            'status' => $lead->status,
            'lead_score' => $lead->lead_score,
            'source' => $lead->source,
            'notes' => $lead->notes,
            'products' => $lead->leadProducts->map(function($lp) {
                return [
                    'product_id' => $lp->product_id,
                    'product_name' => $lp->product->name,
                    'status' => $lp->status,
                    'quoted_price' => $lp->quoted_price
                ];
            }),
            'conversations_count' => $lead->conversations()->count(),
            'last_interaction_at' => $lead->last_interaction_at,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at
        ];
    }

    /**
     * Sync conversation summary to external CRM
     */
    public function syncConversationSummary(Lead $lead): array
    {
        try {
            $conversations = $lead->conversations()
                                ->orderBy('created_at', 'desc')
                                ->limit(50)
                                ->get();

            $summary = [
                'lead_id' => $lead->id,
                'total_conversations' => $conversations->count(),
                'last_conversation_at' => $conversations->first()?->created_at,
                'conversation_states' => $conversations->groupBy('conversation_state')->map->count(),
                'avg_confidence' => $conversations->whereNotNull('confidence_score')->avg('confidence_score'),
                'recent_conversations' => $conversations->take(5)->map(function($conv) {
                    return [
                        'message_type' => $conv->message_type,
                        'conversation_state' => $conv->conversation_state,
                        'confidence_score' => $conv->confidence_score,
                        'created_at' => $conv->created_at,
                        'message_preview' => substr($conv->message_content, 0, 100) . '...'
                    ];
                })
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json'
            ])->post($this->config['endpoints']['conversations'], $summary);

            return ['success' => $response->successful(), 'data' => $response->json()];

        } catch (\Exception $e) {
            Log::error('Failed to sync conversation summary', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}