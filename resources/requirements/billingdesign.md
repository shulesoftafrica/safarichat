# Billing Payment Page Enhancement Requirements

## Context
SafariChat integrates with an external billing platform (Shulesoft Billing API) for subscription management. When users upgrade or renew plans, the system creates invoices on the billing platform and redirects to a payment page where users can choose their payment method (UCN/Lipa Namba, Stripe, or Flutterwave).

**API Documentation:** https://shulesoftapi.shulesoft.africa/api-docs

## Shulesoft Billing API Endpoints Used

SafariChat interacts with the following Shulesoft API endpoints:

### Invoice Management
- **Create Invoice:** `POST /api/invoices`
  - Creates a new invoice with customer and product details
  - Returns invoice ID and basic information
  
- **Get Invoice Payment Gateways:** `GET /api/invoices/{invoice_id}/payment-gateways`
  - Returns payment links for Stripe, Flutterwave, and UCN
  - Must be called after invoice creation to get payment URLs

### Product & Pricing
- **List Products:** `GET /api/products?organization_id={org_id}`
  - Returns all products for the organization
  
- **List Price Plans:** `GET /api/products/{product_id}/price-plans`
  - Returns all price plans for a specific product
  - Contains pricing information and plan details
  
- **Get Specific Price Plan:** `GET /api/products/{product_id}/price-plans/{price_plan_id}`
  - Returns details of a single price plan

### Authentication
All API requests require:
- **Header:** `Authorization: Bearer {ACCESS_TOKEN}`
- **Header:** `Accept: application/json`
- **Header:** `Content-Type: application/json` (for POST/PUT requests)

## Configuration Requirements

Add the following to `config/services.php`:

```php
'billing' => [
    'api_url' => env('BILLING_API_URL', 'https://shulesoftapi.shulesoft.africa/api'),
    'access_token' => env('BILLING_ACCESS_TOKEN'),
    'organization_id' => env('BILLING_ORGANIZATION_ID'),
    'product_id' => env('BILLING_PRODUCT_ID'), // SafariChat product ID
    'credits_price_plan_id' => env('BILLING_CREDITS_PRICE_PLAN_ID'), // AI Credits price plan ID
],
```

Add to `.env` file:
```env
BILLING_API_URL=https://shulesoftapi.shulesoft.africa/api
BILLING_ACCESS_TOKEN=your_access_token_here
BILLING_ORGANIZATION_ID=your_org_id
BILLING_PRODUCT_ID=your_product_id
BILLING_CREDITS_PRICE_PLAN_ID=your_credits_price_plan_id
```

## Error Handling

### Common Shulesoft API Error Responses

**401 Unauthorized**
```json
{
  "message": "Unauthenticated",
  "error": "invalid_access_token"
}
```
**Action:** Check that `BILLING_ACCESS_TOKEN` is valid and not expired.

**422 Unprocessable Entity**
```json
{
  "errors": {
    "organization_id": ["The organization id field is invalid."],
    "customer.email": ["The customer email field is invalid."],
    "products.*.price_plan_id": ["The products 0 price plan id field is invalid."]
  }
}
```
**Action:** Validate all required fields are present and properly formatted.

**404 Not Found**
```json
{
  "success": false,
  "message": "Resource not found"
}
```
**Action:** Verify invoice_id, product_id, or price_plan_id exists.

**429 Too Many Requests**
```json
{
  "message": "Too Many Attempts."
}
```
**Action:** Implement rate limiting and retry with exponential backoff.

### SafariChat Error Handling Strategy

1. **Graceful Degradation:** If billing platform API is down, show cached pricing with warning message
2. **Retry Logic:** Implement automatic retry for transient failures (3 retries with exponential backoff)
3. **User-Friendly Messages:** Convert technical API errors to user-friendly explanations
4. **Logging:** Log all API errors with full context (user_id, request payload, response)
5. **Fallback UCN:** If UCN generation fails, allow users to contact support for manual UCN generation

## Current Flow
1. User clicks "Upgrade Now" or "Renew Plan - Pay Now" from the pricing modal
2. Frontend calls `/api/billing/upgrade` or `/api/billing/renew` endpoint
3. BillingApiController creates invoice on billing platform via API
4. User is redirected to `/billing/payment?plan_code=X&amount=Y&renewal=true/false`
5. Payment page displays payment method options

## Requirements

### 1. Database Schema Update
**Table:** `billing_accounts`

Add two new columns to store UCN payment references:
- `subscription_ucn` (VARCHAR/STRING, nullable) - Stores the UCN reference number for subscription payments (upgrades/renewals)
- `credit_ucn` (VARCHAR/STRING, nullable) - Stores the UCN reference number for AI credit top-ups

**Migration File:** Create `database/migrations/YYYY_MM_DD_add_ucn_columns_to_billing_accounts.php`

```php
Schema::table('billing_accounts', function (Blueprint $table) {
    $table->string('subscription_ucn')->nullable()->after('subscription_expires_at');
    $table->string('credit_ucn')->nullable()->after('ai_credits');
});
```

### 1.5. API Routes to Add

**File:** `routes/api.php`

Add the following routes to the billing API group:

```php
Route::prefix('billing')->name('api.billing.')->group(function () {
    // Existing routes...
    Route::get('/status', [BillingApiController::class, 'getBillingStatus'])->middleware('auth:sanctum');
    Route::get('/plans', [BillingApiController::class, 'getProductInfo'])->middleware('auth:sanctum');
    Route::post('/upgrade', [BillingApiController::class, 'upgradePlan'])->middleware('auth:sanctum');
    Route::post('/renew', [BillingApiController::class, 'renewPlan'])->middleware('auth:sanctum');
    Route::post('/credits', [BillingApiController::class, 'purchaseCredits'])->middleware('auth:sanctum');
    
    // NEW ROUTES FOR WALLET/CREDIT TOP-UP:
    Route::get('/wallet/get-ucn', [BillingApiController::class, 'getWalletUCN'])->middleware('auth:sanctum');
    Route::post('/wallet/topup', [BillingApiController::class, 'topUpWallet'])->middleware('auth:sanctum');
    Route::get('/wallet/info', [BillingApiController::class, 'getWalletInfo'])->middleware('auth:sanctum');
});
```

**New Routes Explained:**
- `GET /api/billing/wallet/get-ucn` - Retrieves or creates the `credit_ucn` for the authenticated user
- `POST /api/billing/wallet/topup` - Creates invoice and returns payment URL for credit top-up (accepts amount and payment_method)
- `GET /api/billing/wallet/info` - (Already exists) Returns wallet balance and credit information

### 2. Payment Page Invoice Management

**Subscription Payment Page:** `/billing/payment` (likely `resources/views/billing/payment.blade.php`)
**Credit Top-Up Page:** `/billing/wallet` (likely `resources/views/billing/wallet.blade.php`)

#### Workflow A: Subscription Payment (Upgrade/Renewal)

**On Payment Page Load:**

**Step 1: Check for Existing Invoice**
- When payment page loads, check if `subscription_ucn` already exists in `billing_accounts` for the current user
- If UCN exists AND invoice is not expired:
    - Display the existing UCN reference
    - Regenerate Stripe and Flutterwave payment links (these expire quickly and must be regenerated)
    - Skip invoice creation
- If UCN exists BUT invoice is expired:
    - Keep displaying the UCN (UCN remains valid indefinitely)
    - Regenerate Stripe and Flutterwave payment links only
    - Skip creating a new invoice

**Step 2: Create Invoice if Needed**
- If no valid `subscription_ucn` exists:
  - Call billing platform API endpoint: `POST /api/invoices`
  - Request payload should include:
    ```json
    {
      "organization_id": "YOUR_ORG_ID",
      "customer": {
        "name": "Business/User Name",
        "email": "user@example.com",
        "phone": "+255712345678"
      },
      "products": [
        {
          "price_plan_id": "PRICE_PLAN_ID",
          "amount": 149000
        }
      ],
      "currency": "TZS",
      "status": "pending",
      "description": "SafariChat Pro Plan - Subscription Renewal",
      "date": "2026-03-11",
      "due_date": "2026-03-31"
    }
    ```
  - **Note**: `price_plan_id` must be obtained from `GET /api/products/{product}/price-plans` endpoint
  - **Note**: SafariChat stores metadata (user_id, business_id, payment_type) in local database, not sent to billing platform

**Step 3: Process Invoice Response**
- Billing platform returns:
  ```json
  {
    "success": true,
    "message": "Invoices retrieved successfully",
    "data": {
      "id": 12345,
      "invoice_number": "INV-000012345",
      "organization_id": "YOUR_ORG_ID",
      "customer_id": 987,
      "total_amount": 149000,
      "currency": "TZS",
      "status": "pending",
      "date": "2026-03-11",
      "due_date": "2026-03-31"
    }
  }
  ```

**Step 3b: Get Payment Gateways for Invoice**
- After invoice creation, call: `GET /api/invoices/{invoice_id}/payment-gateways`
- Billing platform returns:
  ```json
  {
    "success": true,
    "message": "Invoice payment gateways retrieved successfully",
    "data": {
      "invoice_id": 12345,
      "invoice_number": "INV-000012345",
      "price_plans": [
        {
          "id": 1,
          "name": "Pro Plan",
          "amount": 149000,
          "currency": "TZS",
          "payment_links": {
            "stripe": "https://stripe.com/pay/pi_xxx",
            "flutterwave": "https://flutterwave.com/pay/flw_xxx",
            "ucn": "12345678"
          }
        }
      ]
    }
  }
  ```
  - **Extract UCN** from `data.price_plans[0].payment_links.ucn`
  - **Extract Stripe URL** from `data.price_plans[0].payment_links.stripe`
  - **Extract Flutterwave URL** from `data.price_plans[0].payment_links.flutterwave`

**Step 4: Save Invoice Data**
- Update `billing_accounts` table:
  - Set `subscription_ucn` = UCN from `payment_links.ucn` (permanent until payment)
  - Store `invoice_id` and `invoice_number` in local database for reference
  - Store invoice metadata: user_id, business_id, plan_code, payment_type in local table
- Display payment options on page:
  - **UCN Option:** Show UCN reference number from `payment_links.ucn` with copy button
  - **Stripe Option:** Display link from `payment_links.stripe`, redirect on click
  - **Flutterwave Option:** Display link from `payment_links.flutterwave`, redirect on click

#### Workflow B: Credit/Wallet Top-Up

**On Wallet Page Load (`/billing/wallet`):**

**Step 1: Check for Existing Credit Invoice**
- When wallet/top-up page loads, check if `credit_ucn` already exists in `billing_accounts` for the current user
- If `credit_ucn` exists:
    - Display the existing UCN reference (UCN remains valid for credits)
    - Show UCN with "Loading..." initially
    - In background, regenerate Stripe and Flutterwave payment links (these may expire)
    - Update payment links when background call completes
- If `credit_ucn` does NOT exist:
    - Proceed to Step 2

**Step 2: User Enters Credit Amount**
- User selects or enters amount to top-up (e.g., TZS 5,000, 10,000, 25,000, etc.)
- Minimum amount: TZS 1,000
- User clicks "Pay with UCN", "Pay with Stripe", or "Pay with Flutterwave"

**Step 3: Create Credit Invoice**
- When user clicks payment button:
  - Call billing platform API endpoint: `POST /api/invoices`
  - Request payload:
    ```json
    {
      "organization_id": "YOUR_ORG_ID",
      "customer": {
        "name": "Business/User Name",
        "email": "user@example.com",
        "phone": "+255712345678"
      },
      "products": [
        {
          "price_plan_id": "CREDITS_PRICE_PLAN_ID",
          "amount": 10000
        }
      ],
      "currency": "TZS",
      "status": "pending",
      "description": "SafariChat AI Credits Top-Up - TZS 10,000",
      "date": "2026-03-11",
      "due_date": "2026-03-31"
    }
    ```
  - **Note**: SafariChat stores metadata locally (user_id, payment_type="credits", credits_to_add)

**Step 4: Process Credit Invoice Response & Get Payment Links**
- First, invoice creation returns:
  ```json
  {
    "success": true,
    "message": "Invoices retrieved successfully",
    "data": {
      "id": 67890,
      "invoice_number": "INV-000067890",
      "total_amount": 10000,
      "currency": "TZS"
    }
  }
  ```
- Then call `GET /api/invoices/{invoice_id}/payment-gateways` to get payment links:
  ```json
  {
    "success": true,
    "message": "Invoice payment gateways retrieved successfully",
    "data": {
      "invoice_id": 67890,
      "invoice_number": "INV-000067890",
      "price_plans": [
        {
          "payment_links": {
            "stripe": "https://stripe.com/pay/pi_credits_xxx",
            "flutterwave": "https://flutterwave.com/pay/flw_credits_xxx",
            "ucn": "87654321"
          }
        }
      ]
    }
  }
  ```

**Step 5: Save Credit Invoice Data**
- Update `billing_accounts` table:
  - Set `credit_ucn` = UCN from `payment_links.ucn`
  - Store `invoice_id`, `invoice_number`, amount, payment_type in local database
- Display payment options:
  - **UCN Option:** Show UCN from `payment_links.ucn` with copy button
  - **Stripe Option:** Redirect user to `payment_links.stripe`
  - **Flutterwave Option:** Redirect user to `payment_links.flutterwave`

**Step 6: Payment Link Expiry Handling**
- UCN never expires and can be used anytime for any amount
- Stripe/Flutterwave links expire after 24-48 hours
- When user returns to wallet page:
  - If `credit_ucn` exists: Show UCN immediately
  - Regenerate Stripe/Flutterwave links in background
  - Replace "Loading..." with fresh payment links

#### Key Differences: Subscription vs Credits

| Aspect | Subscription Payment | Credit Top-Up |
|--------|---------------------|---------------|
| **API Endpoint** | `POST /api/invoices` | `POST /api/invoices` |
| **Product Type** | Subscription price plan (monthly/yearly) | Credits price plan (one-time) |
| **Amount Source** | From billing platform price plan (fixed) | User-entered (variable, min TZS 1,000) |
| **UCN Column** | `subscription_ucn` | `credit_ucn` |
| **UCN Behavior** | One UCN per plan upgrade/renewal, cleared after payment | One UCN, permanent and reusable |
| **UCN Display** | Show immediately if exists | Show immediately, allow additional top-ups |
| **Payment Links** | Fetched from `/api/invoices/{id}/payment-gateways` | Fetched from `/api/invoices/{id}/payment-gateways` |
| **Payment Link Retrieval** | Each invoice creation requires gateway call | Each invoice creation requires gateway call |
| **Success Handling** | Update subscription status, clear UCN | Add credits to balance, keep UCN |

### 3. Accurate Pricing from Billing Platform

**Current Issue:** 
The amount is passed via URL parameters (`?amount=149000`) which can be manipulated or become outdated.

**Required Change:**
1. **Remove hardcoded amounts** from upgrade/renew API calls
2. **Fetch pricing from billing platform** before creating invoice:
   - First, get product ID: `GET /api/products` (filter by organization_id)
   - Then get price plans: `GET /api/products/{product_id}/price-plans`
   - Response includes price plans with accurate pricing:
     ```json
     {
       "success": true,
       "data": [
         {
           "id": 123,
           "name": "Pro Plan",
           "subscription_type": "monthly",
           "amount": 149000,
           "currency": "TZS",
           "rate": 1
         },
         {
           "id": 124,
           "name": "Premium Plan",
           "subscription_type": "monthly",
           "amount": 249000,
           "currency": "TZS",
           "rate": 1
         }
       ]
     }
     ```
   - To get specific price plan: `GET /api/products/{product_id}/price-plans/{price_plan_id}`
     ```json
     {
       "success": true,
       "data": {
         "id": 123,
         "name": "Pro Plan",
         "amount": 149000,
         "currency": "TZS"
       }
     }
     ```
3. **Use price_plan_id and amount from API** for invoice creation
4. **Display amount on payment page** from the invoice response, not from URL parameters

### 4. Controller Updates Needed

**File:** `app/Http/Controllers/Api/BillingApiController.php`

#### `upgradePlan()` Method Changes:
```php
// REMOVE: Using hardcoded amount from frontend
// OLD: $amount = $request->input('amount');

// ADD: Fetch accurate pricing from billing platform
$pricePlan = $this->fetchPricePlan($planCode);
$pricePlanId = $pricePlan['id'];
$amount = $pricePlan['amount'];

// Create invoice with accurate amount and price_plan_id
$invoiceData = [
  'organization_id' => config('services.billing.organization_id'),
  'customer' => [
    'name' => $user->business->name ?? $user->name,
    'email' => $user->email,
    'phone' => $user->phone
  ],
  'products' => [
    [
      'price_plan_id' => $pricePlanId, // From billing platform
      'amount' => $amount // From billing platform
    ]
  ],
  'currency' => 'TZS',
  'status' => 'pending',
  'description' => "SafariChat {$planCode} Plan - Upgrade",
  'date' => now()->format('Y-m-d'),
  'due_date' => now()->addDays(30)->format('Y-m-d')
];
```

#### `renewPlan()` Method Changes:
```php
// Same as above - fetch current plan pricing from billing platform
$pricePlan = $this->fetchPricePlan($planCode);
$pricePlanId = $pricePlan['id'];
$amount = $pricePlan['amount'];

// Invoice data for renewal
$invoiceData = [
  'organization_id' => config('services.billing.organization_id'),
  'customer' => [...],
  'products' => [
    [
      'price_plan_id' => $pricePlanId,
      'amount' => $amount
    ]
  ],
  'description' => "SafariChat {$planCode} Plan - Renewal"
];
```

#### New Helper Method:
```php
private function fetchPricePlan($planCode) {
    $billingApiUrl = config('services.billing.api_url');
    $accessToken = config('services.billing.access_token');
    $productId = config('services.billing.product_id'); // SafariChat product ID
    
    // Get all price plans for the product
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept' => 'application/json'
    ])->get($billingApiUrl . '/products/' . $productId . '/price-plans');
    
    if ($response->successful()) {
        $pricePlans = $response->json()['data'];
        
        // Find the matching plan by name (e.g., "Pro Plan", "Premium Plan")
        foreach ($pricePlans as $plan) {
            if (stripos($plan['name'], $planCode) !== false) {
                return $plan; // Returns: ['id' => 123, 'name' => 'Pro Plan', 'amount' => 149000, ...]
            }
        }
    }
    
    // Fallback to local config if API fails
    return $this->getFallbackPricing($planCode);
}

private function getFallbackPricing($planCode) {
    // Hardcoded fallback pricing (in case API is down)
    $fallbackPlans = [
        'starter' => ['id' => null, 'amount' => 49000, 'currency' => 'TZS'],
        'pro' => ['id' => null, 'amount' => 149000, 'currency' => 'TZS'],
        'premium' => ['id' => null, 'amount' => 249000, 'currency' => 'TZS']
    ];
    
    return $fallbackPlans[strtolower($planCode)] ?? ['id' => null, 'amount' => 0];
}
```

#### New Methods for Credit/Wallet Top-Up:

**`getWalletUCN()` Method:**
```php
public function getWalletUCN(Request $request) {
    $user = Auth::user();
    $billingAccount = $user->billingAccount;
    
    // Check if credit UCN already exists
    if ($billingAccount && $billingAccount->credit_ucn) {
        return response()->json([
            'success' => true,
            'ucn' => $billingAccount->credit_ucn,
            'message' => 'UCN retrieved from database'
        ]);
    }
    
    // Create credit invoice to get UCN
    try {
        $billingApiUrl = config('services.billing.api_url');
        $accessToken = config('services.billing.access_token');
        $organizationId = config('services.billing.organization_id');
        $creditsPricePlanId = config('services.billing.credits_price_plan_id');
        
        // Create invoice with minimal amount to get UCN
        $invoiceData = [
            'organization_id' => $organizationId,
            'customer' => [
                'name' => $user->business->name ?? $user->name,
                'email' => $user->email ?? ('user.' . $user->id . '@safarichat.africa'),
                'phone' => $user->business->phone ?? $user->phone ?? ''
            ],
            'products' => [
                [
                    'price_plan_id' => $creditsPricePlanId,
                    'amount' => 1000 // Minimum amount to generate UCN
                ]
            ],
            'currency' => 'TZS',
            'status' => 'pending',
            'description' => 'SafariChat AI Credits - UCN Generation',
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d')
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post($billingApiUrl . '/invoices', $invoiceData);

        if ($response->successful()) {
            $invoiceResponse = $response->json();
            $invoiceId = $invoiceResponse['data']['id'] ?? null;
            
            if ($invoiceId) {
                // Get payment gateways to extract UCN
                $gatewayResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json'
                ])->get($billingApiUrl . '/invoices/' . $invoiceId . '/payment-gateways');
                
                if ($gatewayResponse->successful()) {
                    $gatewayData = $gatewayResponse->json();
                    $ucn = $gatewayData['data']['price_plans'][0]['payment_links']['ucn'] ?? null;
                    
                    if ($ucn) {
                        // Save UCN to billing_accounts
                        if (!$billingAccount) {
                            $billingAccount = \App\Models\BillingAccount::create([
                                'user_id' => $user->id,
                                'business_id' => $user->business_id
                            ]);
                        }
                        
                        $billingAccount->update(['credit_ucn' => $ucn]);
                        
                        Log::info('Credit UCN created and saved', [
                            'user_id' => $user->id,
                            'invoice_id' => $invoiceId,
                            'ucn' => $ucn
                        ]);
                        
                        return response()->json([
                            'success' => true,
                            'ucn' => $ucn,
                            'message' => 'UCN generated successfully'
                        ]);
                    }
                }
            }
        }
        
        throw new \Exception('Failed to get UCN from billing platform');
        
    } catch (\Exception $e) {
        Log::error('Failed to get credit UCN', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate UCN. Please try again.'
        ], 500);
    }
}
```

**`topUpWallet()` Method:**
```php
public function topUpWallet(Request $request) {
    $user = Auth::user();
    $amount = $request->input('amount');
    $paymentMethod = $request->input('payment_method'); // 'stripe' or 'flutterwave'
    
    // Validate amount
    if (!$amount || $amount < 1000) {
        return response()->json([
            'success' => false,
            'message' => 'Minimum credit amount is TZS 1,000'
        ], 400);
    }
    
    try {
        $billingApiUrl = config('services.billing.api_url');
        $accessToken = config('services.billing.access_token');
        $organizationId = config('services.billing.organization_id');
        $creditsPricePlanId = config('services.billing.credits_price_plan_id');
        
        // Create invoice for credit top-up
        $invoiceData = [
            'organization_id' => $organizationId,
            'customer' => [
                'name' => $user->business->name ?? $user->name,
                'email' => $user->email ?? ('user.' . $user->id . '@safarichat.africa'),
                'phone' => $user->business->phone ?? $user->phone ?? ''
            ],
            'products' => [
                [
                    'price_plan_id' => $creditsPricePlanId,
                    'amount' => $amount
                ]
            ],
            'currency' => 'TZS',
            'status' => 'pending',
            'description' => \"SafariChat AI Credits Top-Up - TZS \" . number_format($amount),
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d')
        ];

        // Create invoice
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post($billingApiUrl . '/invoices', $invoiceData);

        if ($response->successful()) {
            $invoiceResponse = $response->json();
            $invoiceId = $invoiceResponse['data']['id'] ?? null;
            
            if ($invoiceId) {
                // Get payment gateways
                $gatewayResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json'
                ])->get($billingApiUrl . '/invoices/' . $invoiceId . '/payment-gateways');
                
                if ($gatewayResponse->successful()) {
                    $gatewayData = $gatewayResponse->json();
                    $paymentLinks = $gatewayData['data']['price_plans'][0]['payment_links'] ?? [];
                    
                    // Save UCN if not already saved
                    $ucn = $paymentLinks['ucn'] ?? null;
                    if ($ucn) {
                        $billingAccount = $user->billingAccount;
                        if (!$billingAccount) {
                            $billingAccount = \\App\\Models\\BillingAccount::create([
                                'user_id' => $user->id,
                                'business_id' => $user->business_id
                            ]);
                        }
                        if (!$billingAccount->credit_ucn) {
                            $billingAccount->update(['credit_ucn' => $ucn]);
                        }
                    }
                    
                    // Get payment URL based on method
                    $paymentUrl = null;
                    if ($paymentMethod === 'stripe') {
                        $paymentUrl = $paymentLinks['stripe'] ?? null;
                    } elseif ($paymentMethod === 'flutterwave') {
                        $paymentUrl = $paymentLinks['flutterwave'] ?? null;
                    }
                    
                    if ($paymentUrl) {
                        Log::info('Credit top-up invoice created', [
                            'user_id' => $user->id,
                            'amount' => $amount,
                            'payment_method' => $paymentMethod,
                            'invoice_id' => $invoiceId
                        ]);
                        
                        return response()->json([
                            'success' => true,
                            'payment_url' => $paymentUrl,
                            'message' => "Redirecting to {$paymentMethod} payment"
                        ]);
                    }
                }
            }
        }
        
        throw new \Exception('Failed to create payment link');
        
    } catch (\Exception $e) {
        Log::error('Credit top-up failed', [
            'user_id' => $user->id,
            'amount' => $amount,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to initialize payment. Please try again.'
        ], 500);
    }
}
```

### 5. Payment Page View Updates

#### Subscription Payment Page

**File:** `resources/views/billing/payment.blade.php` (or similar)

**Display Logic:**
```php
// Example Blade template structure for subscription payment
@if($invoice && $invoice->ucn)
    <div class="payment-method ucn">
        <h4>UCN (Lipa Namba)</h4>
        <p>Send payment to: <strong>{{ $invoice->ucn }}</strong></p>
        <button onclick="copyUCN('{{ $invoice->ucn }}')">
            <i class="fas fa-copy"></i> Copy UCN
        </button>
        <p class="text-muted">Send any amount to top up your wallet instantly</p>
    </div>
    
    <div class="payment-method stripe">
        <h4>Stripe</h4>
        <a href="{{ $invoice->payment_url_stripe }}" class="btn btn-primary">
            <i class="fas fa-credit-card"></i> Pay with Card
        </a>
    </div>
    
    <div class="payment-method flutterwave">
        <h4>Flutterwave</h4>
        <a href="{{ $invoice->payment_url_flutterwave }}" class="btn btn-warning">
            <i class="fas fa-mobile-alt"></i> Pay with Flutterwave
        </a>
    </div>
@else
    <p>Loading payment options...</p>
    <div class="spinner-border" role="status"></div>
    <script>
        // Auto-fetch invoice if not available
        fetchSubscriptionInvoice();
    </script>
@endif
```

#### Wallet/Credit Top-Up Page

**File:** `resources/views/billing/wallet.blade.php`

**Display Logic:**
```php
// UCN Section (always visible if exists)
@if($billingAccount && $billingAccount->credit_ucn)
<div class="ucn-payment-section">
    <h5>UCN (Lipa Namba)</h5>
    <p>Pay via any bank or mobile money (Tanzania Only)</p>
    <div class="ucn-display">
        <label>Send payment to:</label>
        <div class="ucn-reference">
            <span id="ucnReference">{{ $billingAccount->credit_ucn }}</span>
            <button onclick="copyUCN('{{ $billingAccount->credit_ucn }}')" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-copy"></i> Copy
            </button>
        </div>
        <p class="info-text">
            <i class="fas fa-info-circle"></i> Send any amount to top up your wallet instantly
        </p>
    </div>
</div>
@else
<div class="ucn-payment-section">
    <h5>UCN (Lipa Namba)</h5>
    <div id="ucnLoading">
        <p>Loading...</p>
    </div>
</div>
@endif

// Stripe Section
<div class="stripe-payment-section">
    <h5>Card Payment (Stripe)</h5>
    <p>Pay securely with Credit/Debit Card</p>
    <div class="amount-input">
        <label>Enter Amount (TZS)</label>
        <input type="number" id="stripeAmount" min="1000" placeholder="Enter amount" />
        <p class="text-muted">Minimum: TZS 1,000</p>
    </div>
    <button id="payWithStripeBtn" onclick="payWithStripe()" class="btn btn-primary" disabled>
        <i class="fas fa-credit-card"></i> Pay with Card
    </button>
</div>

// Flutterwave Section
<div class="flutterwave-payment-section">
    <h5>Flutterwave Payment</h5>
    <p>Pay via Flutterwave Channels in your Country</p>
    <div class="amount-input">
        <label>Enter Amount (TZS)</label>
        <input type="number" id="flutterwaveAmount" min="1000" placeholder="Enter amount" />
        <p class="text-muted">Minimum: TZS 1,000</p>
    </div>
    <button id="payWithFlutterwaveBtn" onclick="payWithFlutterwave()" class="btn btn-warning" disabled>
        <i class="fas fa-mobile-alt"></i> Pay with Flutterwave
    </button>
</div>

<script>
// On page load, fetch/regenerate payment links if needed
document.addEventListener('DOMContentLoaded', function() {
    @if(!$billingAccount || !$billingAccount->credit_ucn)
        // Fetch UCN from billing platform
        fetchCreditUCN();
    @endif
    
    // Enable buttons when user enters valid amount
    document.getElementById('stripeAmount').addEventListener('input', function() {
        document.getElementById('payWithStripeBtn').disabled = this.value < 1000;
    });
    
    document.getElementById('flutterwaveAmount').addEventListener('input', function() {
        document.getElementById('payWithFlutterwaveBtn').disabled = this.value < 1000;
    });
});

async function fetchCreditUCN() {
    try {
        const response = await fetch('/api/billing/wallet/get-ucn', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.ucn) {
            document.getElementById('ucnLoading').innerHTML = `
                <div class="ucn-display">
                    <label>Send payment to:</label>
                    <div class="ucn-reference">
                        <span>${data.ucn}</span>
                        <button onclick="copyUCN('${data.ucn}')" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <p class="info-text">
                        <i class="fas fa-info-circle"></i> Send any amount to top up your wallet instantly
                    </p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Failed to fetch UCN:', error);
        document.getElementById('ucnLoading').innerHTML = '<p class="text-danger">Failed to load UCN. Please refresh.</p>';
    }
}

async function payWithStripe() {
    const amount = document.getElementById('stripeAmount').value;
    if (amount < 1000) {
        alert('Minimum amount is TZS 1,000');
        return;
    }
    
    try {
        const response = await fetch('/api/billing/wallet/topup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                amount: amount,
                payment_method: 'stripe'
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            alert(data.message || 'Failed to initialize payment');
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert('Failed to process payment. Please try again.');
    }
}

async function payWithFlutterwave() {
    const amount = document.getElementById('flutterwaveAmount').value;
    if (amount < 1000) {
        alert('Minimum amount is TZS 1,000');
        return;
    }
    
    try {
        const response = await fetch('/api/billing/wallet/topup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                amount: amount,
                payment_method: 'flutterwave'
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            alert(data.message || 'Failed to initialize payment');
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert('Failed to process payment. Please try again.');
    }
}

function copyUCN(ucn) {
    navigator.clipboard.writeText(ucn).then(function() {
        // Show success message
        if (typeof toastr !== 'undefined') {
            toastr.success('UCN copied to clipboard!');
        } else {
            alert('UCN copied: ' + ucn);
        }
    }).catch(function(err) {
        console.error('Failed to copy:', err);
    });
}
</script>
```

## Implementation Order

### Phase 1: Database Schema
**Goal:** Add UCN storage columns
- Create migration: `database/migrations/YYYY_MM_DD_add_ucn_columns_to_billing_accounts.php`
- Add `subscription_ucn` and `credit_ucn` columns to `billing_accounts` table
- Run migration: `php artisan migrate`

### Phase 2: API Integration - Pricing
**Goal:** Fetch accurate plan pricing from billing platform
- Add `fetchPlanPricing($planCode)` private method to `BillingApiController`
- Add fallback pricing method for when API is unavailable
- Test: Verify pricing is fetched correctly for all plans (starter, pro, premium)

### Phase 3: Subscription Invoice Creation
**Goal:** Update upgrade/renew to use billing platform amounts
- Modify `upgradePlan()` method to call `fetchPlanPricing()` instead of using user input
- Modify `renewPlan()` method to call `fetchPlanPricing()`
- Update invoice creation payload to include accurate amount
- Test: Upgrade/renew should use billing platform pricing, not URL parameters

### Phase 4: Credit/Wallet Invoice API
**Goal:** Create API endpoints for credit top-up
- Create new API route: `GET /api/billing/wallet/get-ucn`
- Create new API route: `POST /api/billing/wallet/topup`
- Add `getWalletUCN()` method in `BillingApiController`:
  - Check if `credit_ucn` exists in database
  - If exists: return UCN
  - If not: create invoice on billing platform, save `credit_ucn`, return UCN
- Add `topUpWallet()` method in `BillingApiController`:
  - Accept amount and payment_method (stripe/flutterwave)
  - Create credit invoice on billing platform
  - Return payment URL for redirection
- Test: API returns UCN and payment URLs correctly

### Phase 5: Subscription Payment Page
**Goal:** Display UCN and payment options on subscription payment page
- Create or update `resources/views/billing/payment.blade.php`
- Create controller method to handle `/billing/payment` route
- Implement logic:
  - Check for existing `subscription_ucn`
  - If not exists, create invoice via billing platform
  - Save `subscription_ucn` to database
  - Display UCN, Stripe link, Flutterwave link
- Add copy-to-clipboard functionality for UCN
- Test: Payment page shows UCN on first visit and subsequent visits

### Phase 6: Wallet/Credit Top-Up Page
**Goal:** Display wallet page with UCN and dynamic payment options
- Update `resources/views/billing/wallet.blade.php`
- Implement wallet page controller method
- On page load:
  - Display existing `credit_ucn` if available
  - If not available, fetch via AJAX and display
  - Show amount input fields for Stripe/Flutterwave
- Add JavaScript for:
  - Fetching UCN in background
  - Handling Stripe payment submission
  - Handling Flutterwave payment submission
  - Copy UCN functionality
- Test: Wallet page loads UCN, allows amount entry, redirects to payment gateways

### Phase 7: Payment Link Regeneration
**Goal:** Handle expired Stripe/Flutterwave links while keeping UCN
- Add endpoint: `POST /api/billing/regenerate-payment-links`
- For subscriptions: Regenerate links using existing `subscription_ucn` data
- For credits: Regenerate links based on user-entered amount
- Auto-call on page load if links are expired
- Test: Returning to payment pages shows updated links

### Phase 8: Webhook Integration
**Goal:** Handle payment confirmations from Shulesoft Billing Platform

**Important:** Shulesoft Billing Platform will send webhooks TO SafariChat when payments are completed.

**Shulesoft's Webhook Endpoints (they send to these endpoints on YOUR server):**
- UCN Payments: `POST /api/billing/webhook/ucn` (or similar endpoint you configure)
- Stripe Payments: `POST /api/billing/webhook/stripe`
- Flutterwave Payments: `POST /api/billing/webhook/flutterwave`

**Your webhook handlers should:**
- **Verify webhook signature** (check headers for security)
- **Handle subscription payment confirmations:**
  - Identify invoice by invoice_id or invoice_number
  - Update subscription status in SafariChat database
  - Set subscription expiry date
  - Clear `subscription_ucn` after successful payment
  - Send confirmation email to user
- **Handle credit payment confirmations:**
  - Add credits to user's `ai_credits` column
  - Log transaction in payment history
  - Keep `credit_ucn` for future top-ups (never clear it)
  - Send credit top-up confirmation notification
  
**Testing:**
- Test: Payments via UCN update database correctly
- Test: Payments via Stripe update database correctly  
- Test: Payments via Flutterwave update database correctly
- Test: Invalid webhook signatures are rejected
- Test: Webhook idempotency (duplicate webhooks don't double-credit)

### Phase 9: Testing & Validation
**Goal:** End-to-end testing of complete flow
- Test subscription upgrade flow
- Test subscription renewal flow
- Test credit top-up flow
- Test payment with each method (UCN, Stripe, Flutterwave)
- Test returning to payment pages (no duplicate invoices)
- Test webhook handling for all payment types
- Load testing: Multiple concurrent invoice creations

### Phase 10: Documentation & Deployment
**Goal:** Document system and deploy
- Update API documentation
- Create admin dashboard for invoice monitoring
- Deploy to staging environment
- User acceptance testing
- Deploy to production

## Success Criteria

### Subscription Payment
- [x] User sees accurate, real-time pricing from billing platform (not URL parameters)
- [x] UCN reference is displayed on payment page for bank/mobile money payments
- [x] Stripe and Flutterwave payment links work correctly
- [x] Invoice is cached - returning to payment page shows same UCN (no duplicate invoices)
- [x] Amount cannot be manipulated via URL parameters
- [x] Payment page does not show pricing modal overlay
- [x] After payment, subscription status updates automatically
- [x] `subscription_ucn` is cleared after successful payment

### Credit/Wallet Top-Up
- [x] User can see `credit_ucn` on wallet page
- [x] If `credit_ucn` doesn't exist, it's fetched and saved automatically
- [x] UCN is displayed with copy-to-clipboard functionality
- [x] User can enter custom amount for Stripe/Flutterwave (minimum TZS 1,000)
- [x] Stripe payment creates invoice and redirects to Stripe checkout
- [x] Flutterwave payment creates invoice and redirects to Flutterwave checkout
- [x] After credit payment, AI credits are added to user's account
- [x] `credit_ucn` remains in database for future top-ups (never cleared)
- [x] Wallet page does not show pricing modal overlay

### General
- [x] All payment methods (UCN/Stripe/Flutterwave) are properly tested
- [x] Webhook handles all payment confirmations correctly
- [x] No duplicate invoices are created on page refresh
- [x] Payment links regenerate when expired
- [x] Error handling for API failures (fallback to local pricing)
- [x] Transaction logs are maintained for auditing
- [x] User experience is smooth (no unnecessary loading delays)

---

## Quick Reference Summary

### UCN Columns in `billing_accounts`:
- **`subscription_ucn`**: Stores UCN for plan upgrades/renewals. Cleared after successful payment.
- **`credit_ucn`**: Stores UCN for credit top-ups. Permanent, never cleared (reusable).

### Key Workflows:

**Subscription Payment:**
1. User clicks "Upgrade Now" or "Renew Plan"
2. Check if `subscription_ucn` exists → display it
3. If not exists → create invoice → save `subscription_ucn` → display it
4. User pays via UCN/Stripe/Flutterwave
5. Webhook updates subscription → clears `subscription_ucn`

**Credit Top-Up:**
1. User visits wallet page
2. Check if `credit_ucn` exists → display it immediately
3. If not exists → fetch from billing platform → save `credit_ucn` → display it
4. User enters amount and selects Stripe/Flutterwave
5. Create invoice → redirect to payment gateway
6. Webhook adds credits → keeps `credit_ucn` for reuse

### API Endpoints (SafariChat Internal):
- `GET /api/billing/wallet/get-ucn` - Get or create credit UCN
- `POST /api/billing/wallet/topup` - Create payment link for credit top-up
- `POST /api/billing/upgrade` - Create subscription upgrade invoice
- `POST /api/billing/renew` - Create subscription renewal invoice

### Shulesoft Billing Platform API Flow:

**Creating an Invoice and Getting Payment Links (2-step process):**

1. **Step 1: Create Invoice**
   ```
   POST https://shulesoftapi.shulesoft.africa/api/invoices
   Headers:
     Authorization: Bearer {ACCESS_TOKEN}
     Content-Type: application/json
   
   Request Body:
   {
     "organization_id": "YOUR_ORG_ID",
     "customer": {
       "name": "Customer Name",
       "email": "email@example.com",
       "phone": "+255712345678"
     },
     "products": [
       {
         "price_plan_id": "PRICE_PLAN_ID",
         "amount": 149000
       }
     ],
     "currency": "TZS",
     "status": "pending",
     "description": "Invoice description",
     "date": "2026-03-11",
     "due_date": "2026-03-31"
   }
   
   Response:
   {
     "success": true,
     "message": "Invoices retrieved successfully",
     "data": {
       "id": 12345,
       "invoice_number": "INV-000012345",
       ...
     }
   }
   ```

2. **Step 2: Get Payment Gateways**
   ```
   GET https://shulesoftapi.shulesoft.africa/api/invoices/12345/payment-gateways
   Headers:
     Authorization: Bearer {ACCESS_TOKEN}
   
   Response:
   {
     "success": true,
     "message": "Invoice payment gateways retrieved successfully",
     "data": {
       "invoice_id": 12345,
       "invoice_number": "INV-000012345",
       "price_plans": [
         {
           "payment_links": {
             "stripe": "https://stripe.com/pay/xxx",
             "flutterwave": "https://flutterwave.com/pay/xxx",
             "ucn": "12345678"
           }
         }
       ]
     }
   }
   ```

**Getting Price Plans:**
```
GET https://shulesoftapi.shulesoft.africa/api/products/{product_id}/price-plans
Headers:
  Authorization: Bearer {ACCESS_TOKEN}

Response:
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Pro Plan",
      "amount": 149000,
      "currency": "TZS",
      "subscription_type": "monthly"
    }
  ]
}
```

### Important Notes:
- **Pricing always comes from billing platform**, never from user input
- **UCN never expires** - can be used anytime
- **Stripe/Flutterwave links expire** - must call `/payment-gateways` endpoint to regenerate
- **No duplicate invoices** - check database before creating new invoice
- **Modal does not appear** on payment, wallet, or top-up pages
- **Two API calls required**: First create invoice, then get payment gateways



1. Create Subscription Invoice

Request Body
{
  "organization_id": 1,
  "customer": {
    "name": "Jane Smith",
    "email": "jane@company.com",
    "phone": "+255723456789"
  },
  "products": [
    {
      "price_plan_id": 8,
      "amount": 75000
    }
  ],
  "description": "Premium hosting - Monthly subscription",
  "currency": "TZS",
  "status": "issued",
  "payment_gateway": "flutterwave",
  "success_url": "https://yourapp.com/payment/success",
  "cancel_url": "https://yourapp.com/payment/cancel"
}
Success Response
201 Created
{
  "success": true,
  "message": "Invoice created successfully",
  "data": {
    "invoice": {
      "id": 124,
      "invoice_number": "INV-2026-00124",
      "customer_id": 46,
      "currency": "TZS",
      "status": "issued",
      "description": "Premium hosting - Monthly subscription",
      "subtotal": 75000,
      "tax_total": 0,
      "total": 75000,
      "due_date": null,
      "issued_at": "2026-02-26T11:15:00.000000Z",
      "items": [
        {
          "id": 457,
          "price_plan_id": 8,
          "subscription_id": 89,
          "product_name": "Premium Hosting Plan",
          "billing_interval": "monthly",
          "quantity": 1,
          "unit_price": 75000,
          "total": 75000
        }
      ],
      "subscription": {
        "id": 89,
        "status": "pending",
        "price_plan_id": 8,
        "start_date": null,
        "next_billing_date": null,
        "note": "Subscription will activate upon payment"
      },
      "payment_details": {
        "flutterwave": {
          "payment_link": "https://checkout.flutterwave.com/v3/hosted/pay/abc123xyz",
          "tx_ref": "INV-2026-00124-1708956234",
          "expires_at": "2026-03-05T11:15:00.000000Z"
        }
      }
    }
  }
}


POST
/api/invoices
Create Usage-Based Invoice
▾
Required Headers
Key	Value
Authorization	Bearer {APP_ACCESS_TOKEN}
Content-Type	application/json
Accept	application/json
Request Body
{
  "organization_id": 1,
  "customer": {
    "name": "Tech Startup Inc",
    "email": "billing@techstartup.com",
    "phone": "+255734567890"
  },
  "products": [
    {
      "price_plan_id": 15,
      "amount": 45000
    }
  ],
  "description": "API Usage - 45,000 calls @ TZS 1 per call",
  "currency": "TZS",
  "status": "issued"
}
Success Response
201 Created
{
  "success": true,
  "message": "Invoice created successfully",
  "data": {
    "invoice": {
      "id": 125,
      "invoice_number": "INV-2026-00125",
      "customer_id": 45,
      "currency": "TZS",
      "status": "issued",
      "description": "API Usage - 45,000 calls @ TZS 1 per call",
      "subtotal": 45000,
      "tax_total": 0,
      "total": 45000,
      "issued_at": "2026-02-26T12:30:00.000000Z",
      "items": [
        {
          "id": 458,
          "price_plan_id": 15,
          "product_name": "API Usage Charges",
          "quantity": 1,
          "unit_price": 45000,
          "total": 45000,
          "metadata": {
            "usage_period": "2026-02-01 to 2026-02-28",
            "total_calls": 45000,
            "rate_per_call": 1
          }
        }
      ]
    }
  }
}