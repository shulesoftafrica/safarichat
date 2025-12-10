<?php

namespace App\Services;

use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\AdminPayment;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentGatewayService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Convert amount from TSH to USD using live exchange rates
     */
    public function convertToUSD(float $tshAmount, string $fromCountryCode = 'TZ'): float
    {
        try {
            // Map country codes to currency codes
            $currencyMap = [
                'TZ' => 'TZS',  // Tanzania Shillings
                'KE' => 'KES',  // Kenya Shillings
                'UG' => 'UGX',  // Uganda Shillings
                'RW' => 'RWF',  // Rwanda Francs
                'BI' => 'BIF',  // Burundi Francs
            ];
            
            $fromCurrency = $currencyMap[$fromCountryCode] ?? 'TZS';
            
            // Get live exchange rate
            $exchangeData = $this->getExchangeRate($fromCurrency, 'USD');
            
            if (!$exchangeData || !isset($exchangeData['rate'])) {
                // Fallback to default rates if API fails
                return $this->convertToUSDFallback($tshAmount, $fromCountryCode);
            }
            
            // Convert to USD
            $usdAmount = round($tshAmount * $exchangeData['rate'], 2);
            
            // Ensure minimum charge of $1 USD
            return max($usdAmount, 1.0);
            
        } catch (\Exception $e) {
            \Log::error('Currency conversion failed, using fallback: ' . $e->getMessage());
            return $this->convertToUSDFallback($tshAmount, $fromCountryCode);
        }
    }

    /**
     * Get current exchange rate from exchangerate.host API
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency = 'USD'): array
    {
        try {
            $apiKey = 'nbgCr061I2QogJ25nfnW3G2AQcnbBQrT';
            $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}_" . date('Y-m-d');
            
            // Check cache first (cache for 1 hour)
            $cachedRate = \Cache::get($cacheKey);
            if ($cachedRate) {
                return $cachedRate;
            }
            
            // Make API request to exchangerate.host
            $response = Http::withHeaders([
                'apikey' => $apiKey
            ])->get('https://api.exchangerate.host/convert', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'amount' => 1
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] ?? false) {
                    $rateData = [
                        'rate' => $data['result'] ?? 0,
                        'multiplier' => $data['result'] ?? 0,
                        'updated_at' => now(),
                        'source' => 'exchangerate.host',
                        'from_currency' => $fromCurrency,
                        'to_currency' => $toCurrency,
                        'api_info' => $data['info'] ?? []
                    ];
                    
                    // Cache the rate for 1 hour
                    \Cache::put($cacheKey, $rateData, 3600);
                    
                    return $rateData;
                }
            }
            
            throw new \Exception('API request failed or returned invalid data');
            
        } catch (\Exception $e) {
            \Log::error('Exchange rate API failed: ' . $e->getMessage());
            
            // Return fallback rates
            return $this->getFallbackExchangeRate($fromCurrency, $toCurrency);
        }
    }
    
    /**
     * Fallback currency conversion when API fails
     */
    private function convertToUSDFallback(float $amount, string $fromCountryCode): float
    {
        // Fallback exchange rates (local currency per 1 USD)
        $fallbackRates = [
            'TZ' => 2700.0,  // Tanzania Shillings
            'KE' => 129.0,   // Kenya Shillings
            'UG' => 3700.0,  // Uganda Shillings
            'RW' => 1300.0,  // Rwanda Francs
            'BI' => 600.0,   // Burundi Francs
        ];

        $rate = $fallbackRates[$fromCountryCode] ?? $fallbackRates['TZ'];
        $usdAmount = round($amount / $rate, 2);
        
        return max($usdAmount, 1.0);
    }
    
    /**
     * Get fallback exchange rates when API fails
     */
    private function getFallbackExchangeRate(string $fromCurrency, string $toCurrency): array
    {
        $fallbackRates = [
            'TZS' => ['USD' => 1/2700.0, 'rate' => 1/2700.0],
            'KES' => ['USD' => 1/129.0, 'rate' => 1/129.0],
            'UGX' => ['USD' => 1/3700.0, 'rate' => 1/3700.0],
            'RWF' => ['USD' => 1/1300.0, 'rate' => 1/1300.0],
            'BIF' => ['USD' => 1/600.0, 'rate' => 1/600.0],
        ];

        $fromRate = $fallbackRates[$fromCurrency] ?? $fallbackRates['TZS'];
        
        return [
            'rate' => $fromRate['rate'],
            'multiplier' => $fromRate[$toCurrency] ?? $fromRate['rate'],
            'updated_at' => now(),
            'source' => 'fallback_rates',
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency
        ];
    }
    
    /**
     * Get multiple currency rates at once
     */
    public function getMultipleCurrencyRates(array $currencies, string $baseCurrency = 'USD'): array
    {
        try {
            $apiKey = 'nbgCr061I2QogJ25nfnW3G2AQcnbBQrT';
            $currencyList = implode(',', $currencies);
            
            $response = Http::withHeaders([
                'apikey' => $apiKey
            ])->get('https://api.exchangerate.host/latest', [
                'base' => $baseCurrency,
                'symbols' => $currencyList
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] ?? false) {
                    return [
                        'rates' => $data['rates'] ?? [],
                        'base' => $data['base'] ?? $baseCurrency,
                        'date' => $data['date'] ?? date('Y-m-d'),
                        'source' => 'exchangerate.host'
                    ];
                }
            }
            
            return ['rates' => [], 'source' => 'api_failed'];
            
        } catch (\Exception $e) {
            \Log::error('Multiple currency rates API failed: ' . $e->getMessage());
            return ['rates' => [], 'source' => 'api_error'];
        }
    }

    /**
     * Create Lipa Number merchant for Tanzania users
     */
    public function createLipaNumberMerchant(User $user, float $amount = null): array
    {
        try {
            $response = Http::post('https://api.shulesoft.africa/merchant/create', [
                'customer_name' => $user->name,
                'instance_id' => config('app.instance_id', 'safarichat'),
                'amount' => $amount,
                'reference' => 'subscription_' . $user->id . '_' . time()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Store payment method
                PaymentMethod::updateOrCreate([
                    'user_id' => $user->id,
                    'gateway_type' => 'lipa_number'
                ], [
                    'merchant_id' => $data['merchant_id'],
                    'is_default' => true,
                    'metadata' => [
                        'qr_code' => $data['qr_code'] ?? null,
                        'created_at' => now()
                    ]
                ]);

                return [
                    'merchant_id' => $data['merchant_id'],
                    'qr_code' => $data['qr_code'] ?? null,
                    'reference' => $data['reference'] ?? null
                ];
            }

            throw new \Exception('Failed to create Lipa Number merchant: ' . $response->body());
        } catch (\Exception $e) {
            throw new \Exception('Lipa Number API error: ' . $e->getMessage());
        }
    }

    /**
     * Generate QR code for Lipa Number payment
     */
    public function generateQRCode(string $merchantId, float $amount): string
    {
        // QR code is typically returned from the Lipa Number API
        // This method can be used for additional QR code generation if needed
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=LipaNamba:{$merchantId}:{$amount}";
    }

    /**
     * Process Stripe payment for international users
     */
    public function processStripePayment(User $user, float $amount, string $paymentMethodId = null): array
    {
        try {
            // Create or get Stripe customer
            $paymentMethod = PaymentMethod::where('user_id', $user->id)
                ->where('gateway_type', 'stripe')
                ->first();

            $customerId = $paymentMethod?->stripe_customer_id;

            if (!$customerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);
                $customerId = $customer->id;

                // Store customer ID
                PaymentMethod::updateOrCreate([
                    'user_id' => $user->id,
                    'gateway_type' => 'stripe'
                ], [
                    'stripe_customer_id' => $customerId,
                    'is_default' => true
                ]);
            }

            // Create payment intent or session
            if ($paymentMethodId) {
                // Direct payment with stored method
                $intent = \Stripe\PaymentIntent::create([
                    'amount' => $amount * 100, // Convert to cents
                    'currency' => 'usd',
                    'customer' => $customerId,
                    'payment_method' => $paymentMethodId,
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    'metadata' => [
                        'user_id' => $user->id,
                        'type' => 'subscription'
                    ]
                ]);

                return [
                    'payment_intent_id' => $intent->id,
                    'status' => $intent->status,
                    'client_secret' => $intent->client_secret
                ];
            } else {
                // Create checkout session
                $session = Session::create([
                    'customer' => $customerId,
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'SafariChat Subscription'
                            ],
                            'unit_amount' => $amount * 100,
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('payment.cancel'),
                    'metadata' => [
                        'user_id' => $user->id,
                        'type' => 'subscription'
                    ]
                ]);

                return [
                    'session_id' => $session->id,
                    'checkout_url' => $session->url
                ];
            }
        } catch (\Exception $e) {
            throw new \Exception('Stripe payment error: ' . $e->getMessage());
        }
    }

    /**
     * Handle webhook notifications
     */
    public function handleWebhook(string $gateway, array $payload): bool
    {
        try {
            switch ($gateway) {
                case 'lipa_number':
                    return $this->handleLipaNumberWebhook($payload);
                case 'stripe':
                    return $this->handleStripeWebhook($payload);
                default:
                    throw new \Exception('Unknown gateway: ' . $gateway);
            }
        } catch (\Exception $e) {
            \Log::error('Webhook handling failed: ' . $e->getMessage(), [
                'gateway' => $gateway,
                'payload' => $payload
            ]);
            return false;
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $transactionId, string $gateway): bool
    {
        try {
            switch ($gateway) {
                case 'lipa_number':
                    $response = Http::get("https://api.shulesoft.africa/transaction/{$transactionId}");
                    return $response->successful() && $response->json('status') === 'completed';
                
                case 'stripe':
                    $intent = \Stripe\PaymentIntent::retrieve($transactionId);
                    return $intent->status === 'succeeded';
                
                default:
                    return false;
            }
        } catch (\Exception $e) {
            \Log::error('Payment verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process refund
     */
    public function refundPayment(AdminPayment $payment, float $amount): bool
    {
        try {
            switch ($payment->payment_gateway) {
                case 'stripe':
                    $refund = \Stripe\Refund::create([
                        'payment_intent' => $payment->gateway_reference,
                        'amount' => $amount * 100 // Convert to cents
                    ]);
                    return $refund->status === 'succeeded';
                
                case 'lipa_number':
                    // Lipa Number refund logic
                    $response = Http::post('https://api.shulesoft.africa/refund', [
                        'transaction_id' => $payment->gateway_reference,
                        'amount' => $amount,
                        'reason' => 'Customer request'
                    ]);
                    return $response->successful();
                
                default:
                    return false;
            }
        } catch (\Exception $e) {
            \Log::error('Refund failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle Lipa Number webhook
     */
    private function handleLipaNumberWebhook(array $payload): bool
    {
        // Find the booking/payment by reference
        $booking = \App\Models\AdminBooking::where('reference', $payload['reference'])->first();
        
        if (!$booking) {
            \Log::warning('Lipa Number webhook: Booking not found', ['reference' => $payload['reference']]);
            return false;
        }

        // Process payment
        $payment = AdminPayment::create([
            'user_id' => $booking->user_id,
            'amount' => $payload['amount'],
            'transaction_id' => $payload['transaction_id'],
            'payment_gateway' => 'lipa_number',
            'gateway_reference' => $payload['reference'],
            'status' => $payload['status'] === 'completed' ? 'completed' : 'failed'
        ]);

        if ($payload['status'] === 'completed') {
            // Activate subscription
            app(SubscriptionService::class)->activateSubscription($booking->user, $payment);
            
            // Add credits if excess payment
            if ($payment->credit_amount > 0) {
                app(CreditService::class)->addCredits(
                    $booking->user, 
                    $payment->credit_amount, 
                    'payment_excess', 
                    $payment
                );
            }
        }

        return true;
    }

    /**
     * Handle Stripe webhook
     */
    private function handleStripeWebhook(array $payload): bool
    {
        $event = $payload['type'];
        $data = $payload['data']['object'];

        switch ($event) {
            case 'payment_intent.succeeded':
                return $this->handleStripePaymentSuccess($data);
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                return $this->handleStripeSubscriptionUpdate($data);
            default:
                return true; // Ignore other events
        }
    }

    /**
     * Handle successful Stripe payment
     */
    private function handleStripePaymentSuccess(array $data): bool
    {
        $userId = $data['metadata']['user_id'] ?? null;
        
        if (!$userId) {
            return false;
        }

        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $payment = AdminPayment::create([
            'user_id' => $user->id,
            'amount' => $data['amount'] / 100, // Convert from cents
            'transaction_id' => $data['id'],
            'payment_gateway' => 'stripe',
            'gateway_reference' => $data['id'],
            'status' => 'completed'
        ]);

        // Activate subscription and add credits
        app(SubscriptionService::class)->activateSubscription($user, $payment);
        
        return true;
    }

    /**
     * Handle Stripe subscription updates
     */
    private function handleStripeSubscriptionUpdate(array $data): bool
    {
        // Handle recurring subscription logic if needed
        return true;
    }
}