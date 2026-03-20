<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class BillingController extends Controller
{
    /**
     * Show payment page for plan upgrade
     */
    public function showPayment(Request $request)
    {
        $planCode = $request->input('plan_code');
        $amount = $request->input('amount');
        $feature = $request->input('feature');
        $renewal = $request->input('renewal', 0); // 0 = upgrade, 1 = renewal

        // Validate required parameters
        if (!$planCode || !$amount) {
            return redirect()->route('ai-agents.index')->with('error', 'Invalid payment parameters');
        }

        // Validate plan code
        $validPlans = ['starter', 'pro', 'premium'];
        if (!in_array($planCode, $validPlans)) {
            return redirect()->route('ai-agents.index')->with('error', 'Invalid plan selected');
        }
        
        $user = Auth::user();
        $business = $user->business;
        $billingAccount = $business->billingAccount ?? $business->getOrCreateBillingAccount();
        
        $paymentData = [
            'plan_code' => $planCode,
            'amount' => $amount,
            'feature' => $feature,
            'ucn' => null,
            'flutterwave_link' => null,
            'stripe_link' => null,
            'subscription_ucn' => $billingAccount->subscription_ucn
        ];
        
        try {
            if ($renewal == 1) {
                // RENEWAL FLOW
                // a) Check if subscription_ucn already exists in billing_accounts
                if ($billingAccount->subscription_ucn) {
                    $paymentData['ucn'] = $billingAccount->subscription_ucn;
                    Log::info('Using existing subscription UCN for renewal', [
                        'user_id' => $user->id,
                        'ucn' => $billingAccount->subscription_ucn
                    ]);
                }
                
                // b) Fetch subscription status from billing API
                $pricePlanId = $this->getPricePlanIdForPlan($planCode);
                $invoiceResult = \App\Services\BillingService::createSubscriptionInvoice(
                    $user,
                    $pricePlanId,
                    $amount,
                    'flutterwave',
                    route('billing.success', ['plan' => $planCode]),
                    route('billing.cancel', ['plan' => $planCode])
                );
                
                if ($invoiceResult['success'] && isset($invoiceResult['data'])) {
                    // Check if API returned "pending subscription" message
                    $apiMessage = $invoiceResult['message'] ?? '';
                    if (strpos($apiMessage, 'pending subscriptions') !== false || 
                        (isset($invoiceResult['data']['invoice']) && $invoiceResult['data']['invoice'] === null)) {
                        
                        Log::info('Pending subscription detected, fetching latest invoice', [
                            'user_id' => $user->id,
                            'customer_id' => $billingAccount->external_customer_id
                        ]);
                        
                        // Try to fetch the latest pending/issued invoice with payment gateways
                        if ($billingAccount->external_customer_id) {
                            $latestInvoiceResult = \App\Services\BillingService::getCustomerLatestInvoice(
                                $billingAccount->external_customer_id,
                                'issued'
                            );
                            
                            if ($latestInvoiceResult['success'] && isset($latestInvoiceResult['data'])) {
                                $invoiceResult['data'] = $latestInvoiceResult['data'];
                                Log::info('Using latest invoice for pending subscription', [
                                    'user_id' => $user->id,
                                    'invoice_id' => $latestInvoiceResult['data']['id'] ?? null
                                ]);
                            }
                        }
                    }
                    
                    // Handle both possible response structures
                    $invoiceData = $invoiceResult['data']['invoice'] ?? $invoiceResult['data'];
                    
                    Log::info('Processing renewal invoice data - RAW STRUCTURE', [
                        'user_id' => $user->id,
                        'has_invoice_key_in_result' => isset($invoiceResult['data']['invoice']),
                        'invoice_data_keys' => $invoiceData ? array_keys($invoiceData) : [],
                        'full_invoice_data' => $invoiceData
                    ]);
                    
                    // Skip processing if invoice data is null
                    if ($invoiceData && is_array($invoiceData)) {
                        // Check if invoice is nested
                        if (isset($invoiceData['invoice'])) {
                            $actualInvoice = $invoiceData['invoice'];
                            $paymentDetails = $actualInvoice['payment_details'] ?? [];
                            $pricePlans = $actualInvoice['price_plans'] ?? [];
                        } else {
                            $actualInvoice = $invoiceData;
                            $paymentDetails = $invoiceData['payment_details'] ?? [];
                            $pricePlans = $invoiceData['price_plans'] ?? [];
                        }
                        
                        // Check payment_gateways if payment_details not found
                        if (empty($paymentDetails) && isset($invoiceData['payment_gateways'])) {
                            $paymentDetails = $invoiceData['payment_gateways'];
                        }
                        
                        Log::info('Extracted payment details', [
                            'user_id' => $user->id,
                            'payment_details_keys' => $paymentDetails ? array_keys($paymentDetails) : [],
                            'payment_details' => $paymentDetails
                        ]);
                        
                        // Extract payment links and UCN
                        // Check both payment_details and payment_gateways structure
                        if (isset($paymentDetails['flutterwave']['payment_link'])) {
                            $paymentData['flutterwave_link'] = $paymentDetails['flutterwave']['payment_link'];
                        } elseif (isset($paymentDetails['flutterwave']['hosted_link'])) {
                            $paymentData['flutterwave_link'] = $paymentDetails['flutterwave']['hosted_link'];
                        }
                        
                        if (isset($paymentDetails['stripe']['payment_link'])) {
                            $paymentData['stripe_link'] = $paymentDetails['stripe']['payment_link'];
                        } elseif (isset($paymentDetails['stripe']['hosted_link'])) {
                            $paymentData['stripe_link'] = $paymentDetails['stripe']['hosted_link'];
                        }
                        
                        if (isset($paymentDetails['control_number']['reference'])) {
                            $paymentData['ucn'] = $paymentDetails['control_number']['reference'];
                        } elseif (isset($paymentDetails['control_number']['control_number'])) {
                            $paymentData['ucn'] = $paymentDetails['control_number']['control_number'];
                        }
                        
                        // Extract payment gateways from price plans if not found above
                        if (empty($paymentData['flutterwave_link']) || empty($paymentData['stripe_link']) || empty($paymentData['ucn'])) {
                            foreach ($pricePlans as $plan) {
                                $gateways = $plan['payment_gateways'] ?? [];
                                foreach ($gateways as $gateway) {
                                    if (empty($paymentData['flutterwave_link']) && $gateway['gateway_name'] === 'Flutterwave') {
                                        // Try payment_link first, then hosted_link, then fall back to references
                                        if (isset($gateway['payment_link'])) {
                                            $paymentData['flutterwave_link'] = $gateway['payment_link'];
                                        } elseif (isset($gateway['hosted_link'])) {
                                            $paymentData['flutterwave_link'] = $gateway['hosted_link'];
                                        } elseif (isset($gateway['references'])) {
                                            $paymentData['flutterwave_link'] = $gateway['references'];
                                        }
                                    }
                                    if (empty($paymentData['stripe_link']) && $gateway['gateway_name'] === 'Stripe') {
                                        // Try payment_link first, then hosted_link
                                        if (isset($gateway['payment_link'])) {
                                            $paymentData['stripe_link'] = $gateway['payment_link'];
                                        } elseif (isset($gateway['hosted_link'])) {
                                            $paymentData['stripe_link'] = $gateway['hosted_link'];
                                        } elseif (isset($gateway['client_secret'])) {
                                            $paymentData['stripe_link'] = $gateway['payment_link'] ?? null;
                                        }
                                    }
                                    if (empty($paymentData['ucn']) && $gateway['gateway_name'] === 'Universal Control Number' && isset($gateway['references'])) {
                                        $paymentData['ucn'] = $gateway['references'];
                                    }
                                }
                            }
                        }
                        
                        // Also check if UCN is in the invoice directly
                        if (empty($paymentData['ucn']) && isset($actualInvoice['control_number'])) {
                            $paymentData['ucn'] = $actualInvoice['control_number'];
                        }
                        
                        Log::info('Extracted payment data', [
                            'user_id' => $user->id,
                            'has_flutterwave' => !empty($paymentData['flutterwave_link']),
                            'has_stripe' => !empty($paymentData['stripe_link']),
                            'has_ucn' => !empty($paymentData['ucn']),
                            'ucn' => $paymentData['ucn'] ?? null
                        ]);
                        
                        // c) Update subscription_ucn if it's null
                        if (!$billingAccount->subscription_ucn && !empty($paymentData['ucn'])) {
                            $billingAccount->update(['subscription_ucn' => $paymentData['ucn']]);
                            Log::info('Updated subscription UCN in billing_accounts', [
                                'user_id' => $user->id,
                                'ucn' => $paymentData['ucn']
                            ]);
                        }
                        
                        // Store subscription_id if available
                        $subscriptionId = $actualInvoice['subscription_id'] ?? $actualInvoice['subscription']['id'] ?? null;
                        if ($subscriptionId && !$billingAccount->external_subscription_id) {
                            $billingAccount->update(['external_subscription_id' => $subscriptionId]);
                        }
                    } // End of if ($invoiceData && is_array($invoiceData))
                } else {
                    Log::warning('xFailed to create subscription invoice', [
                        'user_id' => $user->id,
                        'error' => $invoiceResult['error'] ?? 'Unknown error'
                    ]);
                }
                
            } else {
                // UPGRADE FLOW
                // Check if we have external subscription ID
                if (!$billingAccount->external_subscription_id) {
                    Log::warning('No external subscription ID found for upgrade', ['user_id' => $user->id]);
                    // Fallback to creating a new subscription invoice
                    $pricePlanId = $this->getPricePlanIdForPlan($planCode);
                    $invoiceResult = \App\Services\BillingService::createSubscriptionInvoice(
                        $user,
                        $pricePlanId,
                        $amount,
                        'flutterwave',
                        route('billing.success', ['plan' => $planCode]),
                        route('billing.cancel', ['plan' => $planCode])
                    );
                } else {
                    // a) Call upgrade method from billing API
                    $pricePlanId = $this->getPricePlanIdForPlan($planCode);
                    $invoiceResult = \App\Services\BillingService::upgradeSubscription(
                        $billingAccount->external_subscription_id,
                        $pricePlanId,
                        'flutterwave'
                    );
                }
                
                if ($invoiceResult['success'] && isset($invoiceResult['data'])) {
                    // Check if API returned "pending subscription" message
                    $apiMessage = $invoiceResult['message'] ?? '';
                    if (strpos($apiMessage, 'pending subscriptions') !== false || 
                        (isset($invoiceResult['data']['invoice']) && $invoiceResult['data']['invoice'] === null)) {
                        
                        Log::info('Pending subscription detected during upgrade, fetching latest invoice', [
                            'user_id' => $user->id,
                            'customer_id' => $billingAccount->external_customer_id
                        ]);
                        
                        // Try to fetch the latest pending/issued invoice with payment gateways
                        if ($billingAccount->external_customer_id) {
                            $latestInvoiceResult = \App\Services\BillingService::getCustomerLatestInvoice(
                                $billingAccount->external_customer_id,
                                'issued'
                            );
                            
                            if ($latestInvoiceResult['success'] && isset($latestInvoiceResult['data'])) {
                                $invoiceResult['data'] = $latestInvoiceResult['data'];
                                Log::info('Using latest invoice for pending subscription (upgrade)', [
                                    'user_id' => $user->id,
                                    'invoice_id' => $latestInvoiceResult['data']['id'] ?? null
                                ]);
                            }
                        }
                    }
                    
                    // Handle both possible response structures
                    $invoiceData = $invoiceResult['data']['invoice'] ?? $invoiceResult['data'];
                    
                    Log::info('Processing upgrade invoice data - RAW STRUCTURE', [
                        'user_id' => $user->id,
                        'has_invoice_key_in_result' => isset($invoiceResult['data']['invoice']),
                        'invoice_data_keys' => $invoiceData ? array_keys($invoiceData) : [],
                        'full_invoice_data' => $invoiceData
                    ]);
                    
                    // Skip processing if invoice data is null
                    if ($invoiceData && is_array($invoiceData)) {
                        // Check if invoice is nested
                        if (isset($invoiceData['invoice'])) {
                            $actualInvoice = $invoiceData['invoice'];
                            $paymentDetails = $actualInvoice['payment_details'] ?? [];
                            $pricePlans = $actualInvoice['price_plans'] ?? [];
                        } else {
                            $actualInvoice = $invoiceData;
                            $paymentDetails = $invoiceData['payment_details'] ?? [];
                            $pricePlans = $invoiceData['price_plans'] ?? [];
                        }
                        
                        // Check payment_gateways if payment_details not found
                        if (empty($paymentDetails) && isset($invoiceData['payment_gateways'])) {
                            $paymentDetails = $invoiceData['payment_gateways'];
                        }
                        
                        Log::info('Extracted payment details for upgrade', [
                            'user_id' => $user->id,
                            'payment_details_keys' => $paymentDetails ? array_keys($paymentDetails) : [],
                            'price_plans_count' => count($pricePlans),
                            'payment_details' => $paymentDetails
                        ]);
                        
                        // Extract payment links and UCN from payment_details first
                        if (isset($paymentDetails['flutterwave']['payment_link'])) {
                            $paymentData['flutterwave_link'] = $paymentDetails['flutterwave']['payment_link'];
                        } elseif (isset($paymentDetails['flutterwave']['hosted_link'])) {
                            $paymentData['flutterwave_link'] = $paymentDetails['flutterwave']['hosted_link'];
                        }
                        
                        if (isset($paymentDetails['stripe']['payment_link'])) {
                            $paymentData['stripe_link'] = $paymentDetails['stripe']['payment_link'];
                        } elseif (isset($paymentDetails['stripe']['hosted_link'])) {
                            $paymentData['stripe_link'] = $paymentDetails['stripe']['hosted_link'];
                        }
                        
                        if (isset($paymentDetails['control_number']['reference'])) {
                            $paymentData['ucn'] = $paymentDetails['control_number']['reference'];
                        } elseif (isset($paymentDetails['control_number']['control_number'])) {
                            $paymentData['ucn'] = $paymentDetails['control_number']['control_number'];
                        }
                        
                        // Extract payment gateways from price plans if not found above
                        if (empty($paymentData['flutterwave_link']) || empty($paymentData['stripe_link']) || empty($paymentData['ucn'])) {
                            foreach ($pricePlans as $plan) {
                                $gateways = $plan['payment_gateways'] ?? [];
                                foreach ($gateways as $gateway) {
                                    if (empty($paymentData['flutterwave_link']) && $gateway['gateway_name'] === 'Flutterwave') {
                                        // Try payment_link first, then hosted_link, then fall back to references
                                        if (isset($gateway['payment_link'])) {
                                            $paymentData['flutterwave_link'] = $gateway['payment_link'];
                                        } elseif (isset($gateway['hosted_link'])) {
                                            $paymentData['flutterwave_link'] = $gateway['hosted_link'];
                                        } elseif (isset($gateway['references'])) {
                                            $paymentData['flutterwave_link'] = $gateway['references'];
                                        }
                                    }
                                    if (empty($paymentData['stripe_link']) && $gateway['gateway_name'] === 'Stripe') {
                                        // Try payment_link first, then hosted_link, then fall back to references
                                        if (isset($gateway['payment_link'])) {
                                            $paymentData['stripe_link'] = $gateway['payment_link'];
                                        } elseif (isset($gateway['hosted_link'])) {
                                            $paymentData['stripe_link'] = $gateway['hosted_link'];
                                        } elseif (isset($gateway['client_secret'])) {
                                            $paymentData['stripe_link'] = $gateway['payment_link'] ?? null;
                                        }
                                    }
                                    if (empty($paymentData['ucn']) && $gateway['gateway_name'] === 'Universal Control Number' && isset($gateway['references'])) {
                                        $paymentData['ucn'] = $gateway['references'];
                                    }
                                }
                            }
                        }
                        
                        // Also check if UCN is in the invoice directly
                        if (empty($paymentData['ucn']) && isset($actualInvoice['control_number'])) {
                            $paymentData['ucn'] = $actualInvoice['control_number'];
                        }
                        
                        Log::info('Extracted payment data for upgrade', [
                            'user_id' => $user->id,
                            'has_flutterwave' => !empty($paymentData['flutterwave_link']),
                            'has_stripe' => !empty($paymentData['stripe_link']),
                            'has_ucn' => !empty($paymentData['ucn']),
                            'ucn' => $paymentData['ucn'] ?? null
                        ]);
                        
                        // b) Update subscription_ucn if it's null
                        if (!$billingAccount->subscription_ucn && !empty($paymentData['ucn'])) {
                            $billingAccount->update(['subscription_ucn' => $paymentData['ucn']]);
                            Log::info('Updated subscription UCN in billing_accounts for upgrade', [
                                'user_id' => $user->id,
                                'ucn' => $paymentData['ucn']
                            ]);
                        }
                        
                        // Store subscription_id if available
                        $subscriptionId = $actualInvoice['subscription_id'] ?? null;
                        $subscriptions = $invoiceData['subscriptions'] ?? [];
                        if (!$subscriptionId && !empty($subscriptions)) {
                            $subscriptionId = $subscriptions[0]['id'] ?? null;
                        }
                        if ($subscriptionId && !$billingAccount->external_subscription_id) {
                            $billingAccount->update(['external_subscription_id' => $subscriptionId]);
                        }
                    } // End of if ($invoiceData && is_array($invoiceData))
                } else {
                    Log::warning('Failed to upgrade subscription', [
                        'user_id' => $user->id,
                        'error' => $invoiceResult['error'] ?? 'Unknown error'
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error in showPayment', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return view('billing.payment', $paymentData);
    }
    
    /**
     * Get price plan ID for a given plan code
     * This should match the external billing system's price plan IDs
     */
    private function getPricePlanIdForPlan($planCode)
    {
        // Map plan codes to external price plan IDs
        // These should be configured based on your billing system
        $planMapping = [
            'starter' => config('services.billing.price_plans.starter', 4),
            'pro' => config('services.billing.price_plans.pro', 5),
            'premium' => config('services.billing.price_plans.premium', 6),
        ];
        
        return $planMapping[$planCode] ?? 8;
    }

    /**
     * Process payment based on selected method
     */
    public function processPayment(Request $request)
    {
        $user = Auth::user();
        $planCode = $request->input('plan_code');
        $amount = $request->input('amount');
        $paymentMethod = $request->input('payment_method');
        $feature = $request->input('feature');

        try {
            // Validate inputs
            $validPlans = ['starter', 'pro', 'premium'];
            $validMethods = ['ucn', 'stripe', 'flutterwave'];

            if (!in_array($planCode, $validPlans)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid plan selected'
                ], 400);
            }

            if (!in_array($paymentMethod, $validMethods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment method selected'
                ], 400);
            }

            // Check if this is actually an upgrade
            $billingAccount = $user->business->billingAccount ?? null;
            $currentPlan = $billingAccount ? ($billingAccount->subscription_plan ?? 'trial') : 'trial';
            $planHierarchy = ['trial' => 0, 'starter' => 1, 'pro' => 2, 'premium' => 3];
            
            if ($planHierarchy[$planCode] <= $planHierarchy[$currentPlan]) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan is not an upgrade from your current plan'
                ], 400);
            }

            // Process payment based on method
            switch ($paymentMethod) {
                case 'ucn':
                    return $this->processUCNPayment($user, $planCode, $amount, $feature);
                
                case 'stripe':
                    return $this->processStripePayment($user, $planCode, $amount, $feature);
                
                case 'flutterwave':
                    return $this->processFlutterwavePayment($user, $planCode, $amount, $feature);
                
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment method not implemented yet'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'user_id' => $user->id,
                'plan_code' => $planCode,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Process UCN payment
     */
    private function processUCNPayment($user, $planCode, $amount, $feature)
    {
        // For now, create a pending payment record and show UCN payment instructions
        $paymentReference = 'UCN_' . time() . '_' . $user->id;
        
        // Store payment intent in database
        DB::table('payment_intents')->insert([
            'user_id' => $user->id,
            'plan_code' => $planCode,
            'amount' => $amount,
            'payment_method' => 'ucn',
            'payment_reference' => $paymentReference,
            'feature' => $feature,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Log::info('UCN payment initiated', [
            'user_id' => $user->id,
            'plan_code' => $planCode,
            'amount' => $amount,
            'reference' => $paymentReference
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully',
            'redirect_url' => route('billing.ucn-instructions', ['reference' => $paymentReference])
        ]);
    }

    /**
     * Process Stripe payment
     */
    private function processStripePayment($user, $planCode, $amount, $feature)
    {
        try {
            // Check if Stripe is configured
            $stripeKey = config('services.stripe.secret');
            if (empty($stripeKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe payment gateway is not configured. Please contact support or use another payment method.'
                ], 400);
            }

            Stripe::setApiKey($stripeKey);

            // Create payment intent record
            $paymentReference = 'STRIPE_' . time() . '_' . $user->id;
            DB::table('payment_intents')->insert([
                'user_id' => $user->id,
                'plan_code' => $planCode,
                'amount' => $amount,
                'payment_method' => 'stripe',
                'payment_reference' => $paymentReference,
                'feature' => $feature,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get plan details
            $planNames = [
                'starter' => 'SafariChat Starter Plan',
                'pro' => 'SafariChat Pro Plan',
                'premium' => 'SafariChat Premium Plan'
            ];

            // Create Stripe Checkout Session
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => config('services.stripe.currency', 'usd'),
                        'product_data' => [
                            'name' => $planNames[$planCode] ?? 'SafariChat Plan Upgrade',
                            'description' => 'Upgrade to ' . ucfirst($planCode) . ' plan',
                        ],
                        'unit_amount' => (int)($amount * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('billing.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}&reference=' . $paymentReference,
                'cancel_url' => route('billing.cancel') . '?plan=' . $planCode,
                'client_reference_id' => $paymentReference,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_code' => $planCode,
                    'feature' => $feature,
                    'reference' => $paymentReference
                ]
            ]);

            Log::info('Stripe checkout session created', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'reference' => $paymentReference
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Redirecting to Stripe payment...',
                'redirect_url' => $session->url
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe payment creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize Stripe payment. Please try again or contact support.'
            ], 500);
        }
    }

    /**
     * Process Flutterwave payment
     */
    private function processFlutterwavePayment($user, $planCode, $amount, $feature)
    {
        // Flutterwave is not configured
        Log::warning('Flutterwave payment attempted but not configured', [
            'user_id' => $user->id,
            'plan_code' => $planCode
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Flutterwave payment gateway is not available at the moment. Please use Stripe, UCN, or contact support for alternative payment methods.'
        ], 400);
    }

    /**
     * Complete the plan upgrade after successful payment
     */
    private function completeUpgrade($user, $planCode, $feature)
    {
        // Get billing account
        $billingAccount = $user->business->billingAccount ?? null;
        
        if (!$billingAccount) {
            Log::error('Billing account not found for user', ['user_id' => $user->id]);
            throw new \Exception('Billing account not found. Please contact support.');
        }

        $currentPlan = $billingAccount->subscription_plan ?? 'trial';

        // Update billing account with new subscription
        $billingAccount->update([
            'subscription_plan' => $planCode,
            'subscription_expires_at' => now()->addMonth(),
            'status' => 'active'
        ]);

        // Get plan limits from config and update credits
        $planConfig = config("safarichat_billing.plans.{$planCode}");
        if ($planConfig && isset($planConfig['limits']['ai_credits'])) {
            $newCredits = $planConfig['limits']['ai_credits'];
            $billingAccount->update([
                'ai_credits' => DB::raw("ai_credits + {$newCredits}")
            ]);
        }

        Log::info('Plan upgraded successfully', [
            'user_id' => $user->id,
            'old_plan' => $currentPlan,
            'new_plan' => $planCode,
            'feature_requested' => $feature
        ]);
    }

    /**
     * Handle Stripe payment success callback
     */
    public function stripeSuccess(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $reference = $request->input('reference');

            if (!$sessionId || !$reference) {
                Log::error('Missing Stripe session parameters', $request->all());
                return redirect()->route('billing.cancel')->with('error', 'Invalid payment session');
            }

            // Verify payment intent
            $paymentIntent = DB::table('payment_intents')
                ->where('payment_reference', $reference)
                ->first();

            if (!$paymentIntent) {
                Log::error('Payment intent not found', ['reference' => $reference]);
                return redirect()->route('billing.cancel')->with('error', 'Payment record not found');
            }

            // Check if already processed
            if ($paymentIntent->status === 'completed') {
                return redirect()->route('billing.success', ['plan' => $paymentIntent->plan_code])
                    ->with('info', 'Payment already processed');
            }

            // Verify the session with Stripe
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                Log::error('Stripe payment not completed', [
                    'session_id' => $sessionId,
                    'payment_status' => $session->payment_status
                ]);
                return redirect()->route('billing.cancel')->with('error', 'Payment was not completed');
            }

            // Get user
            $user = \App\Models\User::find($paymentIntent->user_id);
            if (!$user) {
                Log::error('User not found for payment', ['user_id' => $paymentIntent->user_id]);
                return redirect()->route('billing.cancel')->with('error', 'User not found');
            }

            // Complete the upgrade
            $this->completeUpgrade($user, $paymentIntent->plan_code, $paymentIntent->feature);

            // Update payment intent status
            DB::table('payment_intents')
                ->where('payment_reference', $reference)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                    'payment_data' => json_encode([
                        'stripe_session_id' => $sessionId,
                        'payment_status' => $session->payment_status,
                        'amount_total' => $session->amount_total
                    ])
                ]);

            Log::info('Stripe payment completed successfully', [
                'user_id' => $user->id,
                'reference' => $reference,
                'plan_code' => $paymentIntent->plan_code
            ]);

            return redirect()->route('billing.success', ['plan' => $paymentIntent->plan_code])
                ->with('success', 'Payment completed successfully!');

        } catch (\Exception $e) {
            Log::error('Stripe success callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('billing.cancel')
                ->with('error', 'Payment verification failed. Please contact support with reference: ' . ($reference ?? 'N/A'));
        }
    }

    /**
     * Show payment success page
     */
    public function paymentSuccess(Request $request)
    {
        $plan = $request->input('plan', 'Unknown');
        return view('billing.success', ['plan' => $plan]);
    }

    /**
     * Show payment cancelled page
     */
    public function paymentCancel(Request $request)
    {
        return view('billing.cancel');
    }

    /**
     * Show UCN payment instructions
     */
    public function showUCNInstructions(Request $request, $reference)
    {
        $paymentIntent = DB::table('payment_intents')
            ->where('payment_reference', $reference)
            ->where('user_id', Auth::id())
            ->first();

        if (!$paymentIntent) {
            return redirect()->route('ai-agents.index')->with('error', 'Payment reference not found');
        }

        return view('billing.ucn-instructions', [
            'payment_intent' => $paymentIntent,
            'reference' => $reference
        ]);
    }

    /**
     * Show wallet management page
     */
    public function showWallet(Request $request)
    {
        $user = Auth::user();
        $billingAccount = $user->billingAccount;

        return view('billing.wallet', [
            'subscription_plan' => $billingAccount ? $billingAccount->subscription_plan : 'trial',
            'subscription_expires_at' => $billingAccount ? $billingAccount->subscription_expires_at : null,
            'ai_credits' => $billingAccount ? $billingAccount->ai_credits : 0
        ]);
    }
}