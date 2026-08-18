<?php

namespace App\Services\MultiChannel;

use App\Models\AiSalesAgent;
use App\Models\BusinessContact;
use App\Models\Channel;
use App\Models\ChannelProductPolicy;

class ChannelSelectionService
{
    /**
     * Select best channel and fallback order for a contact.
     */
    public function select(BusinessContact $contact, ?AiSalesAgent $agent = null, array $context = []): array
    {
        $productId = $context['product_id'] ?? null;
        $requiresFormal = (bool) ($context['requires_formal'] ?? false);

        $channels = Channel::query()
            ->where('business_id', $contact->business_id)
            ->active()
            ->orderBy('priority_rank')
            ->get();

        if ($channels->isEmpty()) {
            return $this->defaultSelection('no_business_channels');
        }

        $preferences = $this->loadPreferences($contact, $agent);
        $metrics = $contact->channelMetrics()->get()->keyBy('channel_key');
        $policies = $this->loadPolicies($contact->business_id, $productId);

        $scored = [];

        foreach ($channels as $channel) {
            $channelKey = strtolower((string) $channel->channel_key);
            $preference = $preferences[$channelKey] ?? null;
            $metric = $metrics->get($channelKey);
            $policy = $policies[$channelKey] ?? null;

            if (!$this->isEligible($preference, $policy, $contact)) {
                continue;
            }

            $scoreParts = [];
            $score = 100;

            // Prefer low rank values from business config.
            $score -= ((int) $channel->priority_rank) * 3;
            $scoreParts[] = 'business_priority';

            if ($preference) {
                if ($preference->is_preferred) {
                    $score += 25;
                    $scoreParts[] = 'contact_preferred';
                }
                $score -= ((int) $preference->priority_rank) * 2;
            }

            if ($policy && $policy->priority_rank !== null) {
                $score -= ((int) $policy->priority_rank) * 2;
                $scoreParts[] = 'product_policy_priority';
            }

            if ($metric) {
                $score += (float) $metric->response_rate * 0.2;
                $score += (float) $metric->conversion_rate * 0.15;
                if (!empty($metric->last_success_at)) {
                    $score += 5;
                }
                if (!empty($metric->last_failure_at)) {
                    $score -= 5;
                }
                $scoreParts[] = 'historical_performance';
            }

            if ($requiresFormal) {
                $formalScore = (int) (($channel->capabilities['formal_score'] ?? 50));
                if ($formalScore < 50) {
                    $score -= 20;
                } else {
                    $score += 5;
                }
                $scoreParts[] = 'formality_requirement';
            }

            if ($agent && !$agent->isAvailableNow()) {
                if ($channelKey === 'email') {
                    $score += 10;
                } else {
                    $score -= 10;
                }
                $scoreParts[] = 'outside_business_hours';
            }

            $costWeight = (float) (config('multi_channel.selection.cost_weight.' . $channelKey, 0));
            $score -= $costWeight;

            $scored[] = [
                'channel' => $channelKey,
                'score' => round($score, 2),
                'reasons' => $scoreParts,
            ];
        }

        if (empty($scored)) {
            return $this->defaultSelection('no_eligible_channels');
        }

        usort($scored, function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                return strcmp($a['channel'], $b['channel']);
            }

            return $a['score'] < $b['score'] ? 1 : -1;
        });

        $selected = $scored[0];
        $fallbackChain = $this->buildFallbackChain($scored);

        return [
            'selected_channel' => $selected['channel'],
            'channel_selection_reason' => implode('|', $selected['reasons']) ?: 'score_based',
            'fallback_chain' => $fallbackChain,
            'scores' => $scored,
        ];
    }

    private function loadPreferences(BusinessContact $contact, ?AiSalesAgent $agent): array
    {
        $query = $contact->channelPreferences();

        if ($agent) {
            $query->where(function ($q) use ($agent) {
                $q->whereNull('ai_sales_agent_id')
                    ->orWhere('ai_sales_agent_id', $agent->id);
            });
        } else {
            $query->whereNull('ai_sales_agent_id');
        }

        $preferences = [];
        foreach ($query->get() as $preference) {
            $preferences[strtolower((string) $preference->channel_key)] = $preference;
        }

        return $preferences;
    }

    private function loadPolicies(int $businessId, $productId): array
    {
        if (!$productId) {
            return [];
        }

        return ChannelProductPolicy::query()
            ->where('business_id', $businessId)
            ->where('product_id', $productId)
            ->get()
            ->keyBy(function ($policy) {
                return strtolower((string) $policy->channel_key);
            })
            ->all();
    }

    private function isEligible($preference, $policy, BusinessContact $contact): bool
    {
        if ((bool) $contact->opt_out_status) {
            return false;
        }

        if ($preference) {
            if (!$preference->is_allowed) {
                return false;
            }
            if ($preference->opt_out_at !== null) {
                return false;
            }
        }

        if ($policy && !$policy->is_allowed) {
            return false;
        }

        return true;
    }

    private function buildFallbackChain(array $scored): array
    {
        $maxCrossChannel = (int) config('multi_channel.fallback.max_cross_channel_attempts', 2);
        $limit = max(1, $maxCrossChannel + 1);

        return array_map(static function (array $item): string {
            return $item['channel'];
        }, array_slice($scored, 0, $limit));
    }

    private function defaultSelection(string $reason): array
    {
        return [
            'selected_channel' => 'whatsapp',
            'channel_selection_reason' => $reason,
            'fallback_chain' => ['whatsapp'],
            'scores' => [
                [
                    'channel' => 'whatsapp',
                    'score' => 0,
                    'reasons' => [$reason],
                ],
            ],
        ];
    }
}
