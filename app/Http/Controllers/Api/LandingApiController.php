<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LandingApiController extends Controller
{
    /**
     * Get real-time currency conversion rates
     */
    public function getCurrencyRates()
    {
        // In production, integrate with a real currency API like CurrencyLayer or ExchangeRate-API
        $rates = [
            'TSH' => 1,
            'USD' => 0.00040,
            'BRL' => 0.0023,
            'INR' => 0.033,
            'NGN' => 0.15,
            'IDR' => 6.2,
            'EUR' => 0.00037
        ];

        return response()->json([
            'success' => true,
            'base_currency' => 'TSH',
            'rates' => $rates,
            'updated_at' => now()->toISOString()
        ]);
    }

    /**
     * Get localized language content
     */
    public function getLanguageContent(Request $request, $locale = 'en')
    {
        if (!in_array($locale, ['en', 'es', 'pt-br', 'hi', 'ar', 'fr'])) {
            $locale = 'en';
        }

        App::setLocale($locale);
        $content = trans('landing', [], $locale);

        return response()->json([
            'success' => true,
            'locale' => $locale,
            'content' => $content
        ]);
    }

    /**
     * Calculate message volume recommendations
     */
    public function calculateMessageVolume(Request $request)
    {
        $request->validate([
            'business_type' => 'required|string',
            'team_size' => 'required|integer|min:1',
            'monthly_customers' => 'required|integer|min:1',
            'avg_interactions' => 'integer|min:1|max:50'
        ]);

        $businessType = $request->input('business_type');
        $teamSize = $request->input('team_size');
        $monthlyCustomers = $request->input('monthly_customers');
        $avgInteractions = $request->input('avg_interactions', 3);

        // Calculate estimated message volume
        $baseMultiplier = $this->getBusinessMultiplier($businessType);
        $teamMultiplier = min($teamSize * 0.2, 2.0); // Max 2x multiplier
        
        $estimatedMonthlyMessages = $monthlyCustomers * $avgInteractions * $baseMultiplier * $teamMultiplier;
        $estimatedMonthlyMessages = min($estimatedMonthlyMessages, 10000); // Cap at 10k

        // Recommend plan
        $recommendedPlan = $this->getRecommendedPlan($estimatedMonthlyMessages);

        return response()->json([
            'success' => true,
            'estimated_monthly_messages' => round($estimatedMonthlyMessages),
            'recommended_plan' => $recommendedPlan,
            'confidence_level' => $this->calculateConfidence($businessType, $teamSize),
            'breakdown' => [
                'monthly_customers' => $monthlyCustomers,
                'avg_interactions' => $avgInteractions,
                'business_multiplier' => $baseMultiplier,
                'team_multiplier' => $teamMultiplier
            ]
        ]);
    }

    /**
     * Get demo conversation templates
     */
    public function getDemoTemplates(Request $request)
    {
        $locale = $request->input('locale', 'en');
        App::setLocale($locale);

        $templates = [
            'sales_inquiry' => [
                'customer' => __('landing.demo.sales_inquiry_customer'),
                'ai_response' => __('landing.demo.sales_inquiry_ai'),
                'category' => 'sales'
            ],
            'product_question' => [
                'customer' => __('landing.demo.product_question_customer'),
                'ai_response' => __('landing.demo.product_question_ai'),
                'category' => 'support'
            ],
            'pricing_negotiation' => [
                'customer' => __('landing.demo.pricing_customer'),
                'ai_response' => __('landing.demo.pricing_ai'),
                'category' => 'negotiation'
            ]
        ];

        return response()->json([
            'success' => true,
            'templates' => $templates,
            'locale' => $locale
        ]);
    }

    /**
     * Track user interactions for analytics
     */
    public function trackInteraction(Request $request)
    {
        $request->validate([
            'event' => 'required|string',
            'page' => 'string',
            'data' => 'array'
        ]);

        // In production, store this in analytics database or send to analytics service
        $interaction = [
            'event' => $request->input('event'),
            'page' => $request->input('page', 'landing'),
            'data' => $request->input('data', []),
            'user_agent' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'timestamp' => now(),
            'session_id' => $request->session()->getId()
        ];

        // Log for now (in production, use proper analytics storage)
        logger('Landing page interaction', $interaction);

        return response()->json([
            'success' => true,
            'message' => 'Interaction tracked'
        ]);
    }

    /**
     * Get business type multiplier for message volume calculation
     */
    private function getBusinessMultiplier($businessType)
    {
        $multipliers = [
            'financial' => 1.5,     // Banks, insurance need detailed conversations
            'education' => 1.2,     // Schools have moderate volume
            'ecommerce' => 2.0,     // E-commerce has high interaction rates
            'healthcare' => 1.4,    // Healthcare needs detailed communication
            'real_estate' => 1.3,   // Real estate has moderate complexity
            'professional' => 1.1,  // Professional services baseline
            'retail' => 1.8,        // Retail has high customer interaction
            'other' => 1.0          // Default multiplier
        ];

        return $multipliers[$businessType] ?? 1.0;
    }

    /**
     * Get recommended plan based on message volume
     */
    private function getRecommendedPlan($messageVolume)
    {
        if ($messageVolume <= 497) {
            return [
                'plan' => 'starter',
                'name' => 'Starter Plan (Winga)',
                'monthly_cost' => 49700,
                'included_messages' => 497,
                'overage_cost' => 0
            ];
        } elseif ($messageVolume <= 1041) {
            return [
                'plan' => 'pro',
                'name' => 'Pro Plan',
                'monthly_cost' => 93700,
                'included_messages' => 1041,
                'overage_cost' => 0
            ];
        } elseif ($messageVolume <= 1545) {
            return [
                'plan' => 'enterprise',
                'name' => 'Enterprise Plan',
                'monthly_cost' => 123600,
                'included_messages' => 1545,
                'overage_cost' => 0
            ];
        } else {
            $overageMessages = $messageVolume - 1545;
            $overageCost = $overageMessages * 75;
            
            return [
                'plan' => 'enterprise_plus',
                'name' => 'Enterprise Plan + Overage',
                'monthly_cost' => 123600 + $overageCost,
                'included_messages' => 1545,
                'overage_messages' => $overageMessages,
                'overage_cost' => $overageCost
            ];
        }
    }

    /**
     * Calculate confidence level for recommendations
     */
    private function calculateConfidence($businessType, $teamSize)
    {
        $baseConfidence = 0.7;
        
        // Higher confidence for known business types
        if (in_array($businessType, ['financial', 'education', 'ecommerce', 'healthcare'])) {
            $baseConfidence += 0.2;
        }
        
        // Higher confidence for typical team sizes
        if ($teamSize >= 3 && $teamSize <= 20) {
            $baseConfidence += 0.1;
        }
        
        return min($baseConfidence, 0.95);
    }
}