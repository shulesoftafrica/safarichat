# ShuleSoft Safari Billing Platform - Complete API Documentation

## Overview

The Safari Billing Platform is a comprehensive multi-currency billing system serving ShuleSoft, SafariChat, LineShop, and other integrated products. It supports subscription management, wallet topups, plan changes, and multi-currency operations across 7 currencies.

## Authentication & API Key Management

### API Key Storage
API keys are stored in the `api_keys` database table with the following structure:

```sql
CREATE TABLE api_keys (
    id SERIAL PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    product_codes JSON,           -- ["shulesoft", "safarichat", "lineshop"]
    permissions JSON,             -- ["create_invoice", "manage_wallets", "view_reports"]
    is_active BOOLEAN DEFAULT true,
    rate_limit INTEGER DEFAULT 60,   -- requests per minute
    created_by VARCHAR(255),
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Authentication Methods
All API requests require an API key provided in one of these ways:

**Header (Recommended):**
```http
X-API-Key: your_api_key_here
```

**Query Parameter:**
```http
?api_key=your_api_key_here
```

**Bearer Token:**
```http
Authorization: Bearer your_api_key_here
```

### Rate Limits
- Default: 60 requests per minute per API key
- Configurable per API key in database
- Headers returned: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- HTTP 429 returned when exceeded

## Base URL

```
Production: https://your-domain.com/api/billing
Development: http://localhost:8000/api/billing
```

## Response Format Standards

### Success Response Structure
```json
{
  "success": true,
  "data": {
    // Response data here
  },
  "timestamp": "2025-12-25T10:30:00Z",
  "request_id": "req_abc123"
}
```

### Error Response Structure
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message",
    "details": {
      // Additional error context
    }
  },
  "timestamp": "2025-12-25T10:30:00Z",
  "request_id": "req_abc123"
}
```

### Standard Error Codes
- `VALIDATION_ERROR` (422) - Request validation failed
- `AUTHENTICATION_FAILED` (401) - Invalid or missing API key
- `AUTHORIZATION_FAILED` (403) - API key lacks required permissions  
- `RESOURCE_NOT_FOUND` (404) - Requested resource not found
- `RATE_LIMIT_EXCEEDED` (429) - Too many requests
- `INTERNAL_SERVER_ERROR` (500) - Server error
- `SERVICE_UNAVAILABLE` (503) - Billing service temporarily down

## Multi-Currency Support

### Supported Currencies
The system supports 7 currencies with smart rounding for optimal UX:

| Code | Country | Name | Symbol | Rounding | Symbol Position |
|------|---------|------|---------|----------|----------------|
| TZS | Tanzania | Tanzanian Shilling | TSh | 100 | After |
| KES | Kenya | Kenyan Shilling | KSh | 50 | After |
| UGX | Uganda | Ugandan Shilling | USh | 500 | After |
| RWF | Rwanda | Rwandan Franc | RWF | 100 | After |
| USD | USA | US Dollar | $ | 1 | Before |
| GBP | UK | British Pound | £ | 1 | Before |
| EUR | EU | Euro | € | 1 | Before |

### Exchange Rate System
- **Base Currency**: TZS (Tanzanian Shilling)
- **Rate Updates**: Automated hourly via external API
- **FX Buffer**: 3% margin applied to protect against volatility
- **Fallback**: Manual rate management if API fails
- **Price Locking**: 15-minute windows during checkout

---

# API ENDPOINTS

## 1. MULTI-CURRENCY OPERATIONS

### Get Price Preview
Get real-time price conversions across multiple currencies.

```http
GET /prices
```

**Parameters:**
```json
{
  "amount": 50000,                    // Required: Base amount
  "currency": "TZS",                  // Required: Base currency (3-letter code)
  "target_currencies": ["KES", "USD", "UGX"], // Optional: Specific currencies
  "use_buffer": true                  // Optional: Apply FX buffer (default: true)
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "base_amount": 50000,
    "base_currency": "TZS",
    "conversions": {
      "KES": {
        "original_amount": 50000,
        "converted_amount": 2605,
        "currency": "KES",
        "exchange_rate": 0.05366300,
        "buffer_applied": true,
        "buffer_percentage": 3.00,
        "formatted_amount": "2,605 KSh"
      },
      "USD": {
        "original_amount": 50000,
        "converted_amount": 20.86,
        "currency": "USD", 
        "exchange_rate": 0.00041715,
        "buffer_applied": true,
        "buffer_percentage": 3.00,
        "formatted_amount": "$20.86"
      },
      "UGX": {
        "original_amount": 50000,
        "converted_amount": 75500,
        "currency": "UGX",
        "exchange_rate": 1.50380000,
        "buffer_applied": true,
        "buffer_percentage": 3.00,
        "formatted_amount": "75,500 USh"
      }
    },
    "generated_at": "2025-12-25T10:30:00Z"
  }
}
```

### Get Available Currencies
List all supported currencies with metadata.

```http
GET /currencies
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "currencies": [
      {
        "code": "TZS",
        "name": "Tanzanian Shilling",
        "symbol": "TSh",
        "country": "Tanzania",
        "is_base": true
      },
      {
        "code": "KES", 
        "name": "Kenyan Shilling",
        "symbol": "KSh",
        "country": "Kenya",
        "is_base": false
      }
    ],
    "total": 7
  }
}
```

### Convert Currency
Convert specific amount between two currencies.

```http
POST /convert
```

**Request Body:**
```json
{
  "amount": 50000,          // Required: Amount to convert
  "from_currency": "TZS",   // Required: Source currency
  "to_currency": "KES",     // Required: Target currency  
  "use_buffer": true        // Optional: Apply FX buffer (default: true)
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "original_amount": 50000,
    "converted_amount": 2605,
    "currency": "KES",
    "exchange_rate": 0.05366300,
    "buffer_applied": true,
    "buffer_percentage": 3.00,
    "formatted_amount": "2,605 KSh"
  }
}
```

**Error Response (404):**
```json
{
  "error": {
    "code": "CONVERSION_UNAVAILABLE",
    "message": "Exchange rate not available for this currency pair"
  }
}
```

---

## 2. INVOICE & SUBSCRIPTION MANAGEMENT

### Create Invoice - Subscription
Create subscription invoices for recurring billing.

```http
POST /create-invoice
```

**Request Body:**
```json
{
  "product_code": "shulesoft",        // Required: "shulesoft", "safarichat", "lineshop"
  "invoice_type": "subscription",     // Required: Subscription billing
  "customer": {
    "name": "Mwenge Secondary School", // Required: Customer name
    "phone": "255123456789",          // Required: Customer phone
    "email": "admin@mwenge.edu.tz"    // Optional: Customer email
  },
  "amount": 50000,                    // Required: Amount in base currency
  "currency": "TZS",                  // Optional: Display currency (default: TZS) 
  "feature_code": "core",             // Required: Feature being subscribed to
  "plan_code": "basic",               // Required: Subscription plan
  "billing_cycle": "monthly",         // Optional: "monthly", "yearly"
  "success_url": "https://school.app.com/success", // Required: Success redirect
  "cancel_url": "https://school.app.com/cancel",   // Required: Cancel redirect
  "metadata": {                       // Optional: Custom data
    "school_id": "SCH001",
    "region": "Dar es Salaam"
  }
}
```

### Create Invoice - Wallet Topup
Create wallet topup invoices for prepaid services.

```http
POST /create-invoice  
```

**Request Body:**
```json
{
  "product_code": "shulesoft",
  "invoice_type": "wallet_topup",     // Non-subscription: One-time payment
  "customer": {
    "name": "Mwenge Secondary School",
    "phone": "255123456789",
    "email": "admin@mwenge.edu.tz"
  },
  "amount": 100000,                   // Total amount
  "currency": "TZS",
  "units": 2000,                      // Required: Number of units (SMS, credits, etc.)
  "unit_price": 50,                   // Required: Price per unit
  "wallet_type": "sms",               // Required: "sms", "whatsapp_messages", "ai_credits"
  "success_url": "https://school.app.com/topup-success",
  "cancel_url": "https://school.app.com/topup-cancel",
  "metadata": {
    "wallet_purpose": "Parent communication",
    "expected_usage": "Monthly SMS campaigns"
  }
}
```

### Create Invoice - Plan Change
Create invoices for subscription upgrades/downgrades.

```http
POST /create-invoice
```

**Request Body:**
```json
{
  "product_code": "shulesoft",
  "invoice_type": "plan_upgrade",      // or "plan_downgrade"
  "customer": {
    "name": "Mwenge Secondary School",
    "phone": "255123456789"
  },
  "amount": 15000,                    // Prorated amount
  "currency": "TZS",
  "old_plan_code": "basic",           // Required: Current plan
  "new_plan_code": "premium",         // Required: Target plan
  "feature_code": "core",
  "proration_credit": 5000,           // Credit from current billing period
  "success_url": "https://school.app.com/upgrade-success", 
  "cancel_url": "https://school.app.com/upgrade-cancel"
}
```

### Common Invoice Success Response (201)
```json
{
  "success": true,
  "data": {
    "invoice": {
      "invoice_id": "INV-20251225-001234",
      "reference_number": "SF-INV-001234",
      "invoice_type": "subscription",
      "status": "pending",
      "amount": 50000,
      "currency": "TZS",
      "display_currency": "KES",        // If multi-currency requested
      "display_amount": 2605,           // Amount in display currency
      "locked_exchange_rate": 0.05366,  // Rate locked for 15 minutes
      "price_locked_until": "2025-12-25T10:45:00Z",
      "customer": {
        "name": "Mwenge Secondary School",
        "phone": "255123456789",
        "email": "admin@mwenge.edu.tz"
      },
      "due_date": "2025-12-30T23:59:59Z",
      "created_at": "2025-12-25T10:30:00Z"
    },
    "payment_session": {
      "session_id": "cs_test_a1b2c3d4e5f6g7h8",
      "payment_url": "https://checkout.stripe.com/pay/cs_test_a1b2c3d4e5f6g7h8",
      "expires_at": "2025-12-25T11:30:00Z"
    },
    "subscription_details": {          // Only for subscription invoices
      "feature_code": "core",
      "plan_code": "basic", 
      "billing_cycle": "monthly",
      "next_billing_date": "2026-01-25T00:00:00Z"
    }
  }
}
```

---

## 3. SUBSCRIPTION LIFECYCLE MANAGEMENT

### Get Customer Status
Retrieve complete customer billing information.

```http
GET /customer/{identifier}/status
```

**Parameters:**
- `identifier`: Student ID, phone number, or email
- `include=subscriptions,wallets,invoices` (optional)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "customer": {
      "student_id": 12345,
      "name": "Mwenge Secondary School", 
      "phone": "255123456789",
      "email": "admin@mwenge.edu.tz",
      "billing_status": "active",
      "created_at": "2024-01-15T08:00:00Z"
    },
    "subscriptions": [
      {
        "product_code": "shulesoft",
        "feature_code": "core",
        "plan_code": "basic",
        "status": "active",
        "start_date": "2025-12-01T00:00:00Z",
        "end_date": "2026-01-01T00:00:00Z",
        "next_billing_date": "2026-01-01T00:00:00Z",
        "amount": 50000,
        "currency": "TZS",
        "billing_cycle": "monthly",
        "auto_renew": true,
        "grace_period_until": null
      }
    ],
    "wallets": [
      {
        "product_code": "shulesoft",
        "wallet_type": "sms",
        "balance": 1500,
        "unit": "sms",
        "frozen": false,
        "last_used": "2025-12-24T14:30:00Z",
        "total_credited": 5000,
        "total_debited": 3500
      }
    ],
    "recent_invoices": [
      {
        "invoice_id": "INV-20251201-001100",
        "type": "subscription",
        "amount": 50000,
        "currency": "TZS", 
        "status": "paid",
        "due_date": "2025-12-05T23:59:59Z",
        "paid_at": "2025-12-01T09:15:00Z"
      }
    ]
  }
}
```

### Cancel Subscription
Cancel an active subscription with optional immediate termination.

```http
POST /subscription/cancel
```

**Request Body:**
```json
{
  "student_id": 12345,              // Required: Customer identifier
  "feature_code": "core",           // Required: Feature to cancel
  "immediate": false,               // Optional: Cancel immediately vs end of period
  "reason": "Budget constraints",   // Optional: Cancellation reason
  "refund_requested": false         // Optional: Request prorated refund
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "subscription": {
      "feature_code": "core",
      "plan_code": "basic", 
      "status": "cancelled",
      "cancelled_at": "2025-12-25T10:30:00Z",
      "active_until": "2026-01-01T00:00:00Z",  // End of current billing period
      "immediate_cancellation": false
    },
    "refund": {                     // If refund_requested = true
      "eligible": true,
      "amount": 15000,
      "currency": "TZS",
      "processing_time": "3-5 business days"
    }
  }
}
```

### Reactivate Subscription  
Reactivate a cancelled or expired subscription.

```http
POST /subscription/reactivate
```

**Request Body:**
```json
{
  "student_id": 12345,
  "feature_code": "core",
  "plan_code": "basic",             // Optional: Change plan during reactivation
  "billing_cycle": "monthly",       // Optional: Change billing cycle
  "payment_method": "existing"      // Optional: "existing", "new"
}
```

### Change Subscription Plan
Upgrade, downgrade, or change billing cycle.

```http
POST /subscription/change-plan
```

**Request Body:**
```json
{
  "student_id": 12345,
  "current_feature_code": "core",
  "new_feature_code": "core",       // Can change features
  "current_plan_code": "basic", 
  "new_plan_code": "premium",       // Plan upgrade/downgrade
  "new_billing_cycle": "yearly",    // Optional: Change billing frequency
  "effective_date": "immediate"     // Optional: "immediate", "next_billing_cycle"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "subscription": {
      "feature_code": "core",
      "plan_code": "premium",
      "billing_cycle": "yearly",
      "status": "active",
      "change_effective_date": "2025-12-25T10:30:00Z",
      "next_billing_date": "2026-12-25T00:00:00Z"
    },
    "billing_adjustment": {
      "prorated_credit": 15000,      // Credit from current plan
      "prorated_charge": 85000,      // Charge for new plan
      "net_amount": 70000,           // Amount to charge now
      "currency": "TZS"
    },
    "invoice": {                     // Invoice created for the change
      "invoice_id": "INV-20251225-001235",
      "amount": 70000,
      "due_date": "2025-12-25T23:59:59Z"
    }
  }
}
```

---

## 4. WALLET & NON-SUBSCRIPTION MANAGEMENT

### Get Wallet Balance
Get current wallet balances for a customer.

```http
GET /wallet/balance
```

**Parameters:**
```json
{
  "student_id": 12345,              // Required: Customer ID
  "wallet_type": "sms"              // Optional: Specific wallet type
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "student_id": 12345,
    "balance": 1500,                // If specific wallet_type requested
    "currency": "TZS",
    "formatted_balance": "1,500 SMS credits",
    "last_transaction_date": "2025-12-24T14:30:00Z",
    "all_wallets": [                // If no wallet_type specified
      {
        "product_code": "shulesoft", 
        "wallet_type": "sms",
        "balance": 1500,
        "unit": "sms",
        "frozen": false,
        "value_in_tzs": 75000,      // Balance × unit price
        "last_used": "2025-12-24T14:30:00Z"
      },
      {
        "product_code": "safarichat",
        "wallet_type": "ai_credits",
        "balance": 25000,
        "unit": "credit",
        "frozen": false,
        "value_in_tzs": 500000,
        "last_used": "2025-12-23T16:45:00Z"
      }
    ],
    "total_value": {
      "amount": 575000,
      "currency": "TZS",
      "formatted": "575,000 TZS"
    }
  }
}
```

### Deduct from Wallet
Deduct credits/units from customer wallet (usage tracking).

```http
POST /deduct-wallet
```

**Request Body:**
```json
{
  "student_id": 12345,              // Required: Customer ID
  "product_code": "shulesoft",      // Required: Product using the wallet
  "wallet_type": "sms",             // Required: Type of wallet
  "amount": 50,                     // Required: Units to deduct
  "description": "Bulk SMS to parents - Grade 10 results", // Required: Usage description
  "metadata": {                     // Optional: Additional context
    "recipients_count": 45,
    "message_length": 160,
    "campaign_id": "CAMP-001"
  }
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "transaction_id": "TXN-20251225-001500",
      "type": "debit",
      "amount": 50,
      "balance_before": 1500,
      "balance_after": 1450,
      "description": "Bulk SMS to parents - Grade 10 results",
      "processed_at": "2025-12-25T10:30:00Z"
    },
    "wallet": {
      "wallet_type": "sms",
      "new_balance": 1450,
      "unit": "sms",
      "frozen": false
    }
  }
}
```

### Get Wallet Transaction History
Retrieve transaction history for audit and reporting.

```http
GET /wallet/{student_id}/transactions
```

**Parameters:**
- `wallet_type` (optional): Filter by wallet type
- `limit` (optional): Number of records (default: 50, max: 200)
- `offset` (optional): Pagination offset
- `start_date` (optional): Filter from date (YYYY-MM-DD)
- `end_date` (optional): Filter to date (YYYY-MM-DD)
- `transaction_type` (optional): "credit", "debit", "all"

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "transaction_id": "TXN-20251225-001500",
        "wallet_type": "sms",
        "type": "debit",
        "amount": 50,
        "balance_before": 1500,
        "balance_after": 1450,
        "unit_price": 50.00,
        "total_value": 2500.00,
        "currency": "TZS", 
        "description": "Bulk SMS to parents - Grade 10 results",
        "metadata": {
          "recipients_count": 45,
          "campaign_id": "CAMP-001"
        },
        "created_at": "2025-12-25T10:30:00Z"
      },
      {
        "transaction_id": "TXN-20251220-001200",
        "wallet_type": "sms", 
        "type": "credit",
        "amount": 1000,
        "balance_before": 500,
        "balance_after": 1500,
        "unit_price": 50.00,
        "total_value": 50000.00,
        "currency": "TZS",
        "description": "SMS Bundle Purchase - 1000 credits",
        "invoice_id": "INV-20251220-001150",
        "created_at": "2025-12-20T08:15:00Z"
      }
    ],
    "summary": {
      "total_credits": 75000,
      "total_debits": 12500,
      "net_balance": 1450,
      "transaction_count": 45
    },
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total_records": 45,
      "total_pages": 1,
      "has_more": false
    }
  }
}
```

---

## 5. INVOICE & PAYMENT OPERATIONS

### Get Invoice Details
Retrieve complete invoice information.

```http
GET /invoices/{invoice_id}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "invoice": {
      "invoice_id": "INV-20251225-001234",
      "reference_number": "SF-INV-001234",
      "invoice_type": "subscription",
      "billing_status": "paid",
      "amount": 50000,
      "currency": "TZS",
      "display_currency": "KES",
      "display_amount": 2605,
      "exchange_rate_used": 0.05366,
      "customer": {
        "student_id": 12345,
        "name": "Mwenge Secondary School",
        "phone": "255123456789",
        "email": "admin@mwenge.edu.tz"
      },
      "payment_details": {
        "payment_method": "card",
        "gateway": "stripe",
        "gateway_reference": "pi_1AbCdEfGhIjKlMnO",
        "paid_at": "2025-12-25T10:45:00Z"
      },
      "subscription_details": {       // If subscription invoice
        "feature_code": "core",
        "plan_code": "basic",
        "billing_cycle": "monthly",
        "service_period": {
          "start": "2025-12-25T00:00:00Z",
          "end": "2026-01-25T00:00:00Z"
        }
      },
      "wallet_details": {             // If wallet topup invoice
        "wallet_type": "sms",
        "units_purchased": 1000,
        "unit_price": 50.00
      },
      "created_at": "2025-12-25T10:30:00Z",
      "due_date": "2025-12-30T23:59:59Z",
      "paid_at": "2025-12-25T10:45:00Z"
    }
  }
}
```

### Process Payment
Process payment for an existing invoice.

```http
POST /invoice/{invoice_id}/payment
```

**Request Body:**
```json
{
  "payment_method": "card",          // Required: "card", "mobile_money", "bank"
  "currency": "KES",                 // Optional: Payment currency (uses locked rate)
  "return_url": "https://school.app.com/payment-complete", // Required: Post-payment redirect
  "metadata": {                      // Optional: Payment context
    "payment_source": "school_dashboard",
    "user_agent": "Mozilla/5.0...",
    "ip_address": "192.168.1.100"
  }
}
```

### Get Products Catalog
List available products and pricing plans.

```http
GET /products
```

**Parameters:**
- `product_code` (optional): Filter by specific product
- `currency` (optional): Show prices in specific currency
- `active_only` (optional): Show only active products (default: true)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "product_code": "shulesoft",
        "product_name": "ShuleSoft School Management System",
        "description": "Complete school management and communication platform",
        "features": [
          {
            "feature_code": "core",
            "feature_name": "Core School Management",
            "description": "Student records, timetables, communication, basic reports",
            "plans": [
              {
                "plan_code": "basic",
                "plan_name": "Basic Plan",
                "description": "Perfect for small schools (up to 500 students)",
                "pricing": {
                  "monthly": {
                    "TZS": 50000,
                    "KES": 2605,
                    "USD": 20.86,
                    "formatted": {
                      "TZS": "50,000 TSh",
                      "KES": "2,605 KSh", 
                      "USD": "$20.86"
                    }
                  },
                  "yearly": {
                    "TZS": 500000,
                    "KES": 26050,
                    "USD": 208.60,
                    "discount_percentage": 16.67,
                    "savings": {
                      "TZS": 100000,
                      "KES": 5210,
                      "USD": 41.72
                    }
                  }
                },
                "limits": {
                  "max_students": 500,
                  "max_teachers": 50,
                  "storage_gb": 10,
                  "sms_monthly": 1000
                },
                "is_popular": true
              }
            ]
          }
        ]
      },
      {
        "product_code": "safarichat",
        "product_name": "SafariChat AI Assistant",
        "description": "AI-powered WhatsApp chatbot for customer service",
        "wallet_types": [
          {
            "wallet_type": "ai_credits",
            "unit_name": "AI Credits",
            "description": "Credits for AI assistant responses",
            "pricing": {
              "TZS": 20.00,
              "KES": 1.04,
              "USD": 0.0083,
              "unit": "per_credit"
            },
            "bulk_pricing": [
              {
                "min_quantity": 1000,
                "max_quantity": 4999,
                "discount_percentage": 5,
                "unit_price": {
                  "TZS": 19.00,
                  "KES": 0.99
                }
              },
              {
                "min_quantity": 5000,
                "discount_percentage": 10,
                "unit_price": {
                  "TZS": 18.00,
                  "KES": 0.94
                }
              }
            ]
          }
        ]
      }
    ],
    "currency_info": {
      "base_currency": "TZS",
      "supported_currencies": ["TZS", "KES", "UGX", "RWF", "USD", "GBP", "EUR"],
      "exchange_rates_updated": "2025-12-25T09:00:00Z"
    }
  }
}
```

---

## 6. PAYMENT FAILURE & RETRY MANAGEMENT

### Retry Failed Payment
Manually retry a failed payment.

```http
POST /payments/{invoice_id}/retry
```

**Request Body:**
```json
{
  "payment_method": "card",          // Optional: Change payment method
  "force_retry": false,              // Optional: Override retry limits
  "notification_customer": true      // Optional: Send retry notification
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "invoice_id": "INV-20251225-001234",
    "payment_status": "processing",
    "retry_attempt": 2,
    "max_retries": 3,
    "next_retry_at": "2025-12-26T10:30:00Z",
    "payment_session": {
      "session_id": "cs_retry_x1y2z3",
      "payment_url": "https://checkout.stripe.com/pay/cs_retry_x1y2z3"
    }
  }
}
```

**Error Response (400):**
```json
{
  "error": {
    "code": "RETRY_LIMIT_EXCEEDED", 
    "message": "Maximum retry attempts (3) exceeded for this invoice",
    "details": {
      "invoice_id": "INV-20251225-001234",
      "retry_attempts": 3,
      "last_attempt": "2025-12-27T10:30:00Z",
      "failure_reasons": ["card_declined", "insufficient_funds", "card_declined"]
    }
  }
}
```

---

## 7. SYSTEM HEALTH & MONITORING

### Health Check
Check billing system status and connectivity.

```http
GET /health
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "timestamp": "2025-12-25T10:30:00Z",
    "version": "2.1.0",
    "components": {
      "database": {
        "status": "healthy",
        "response_time_ms": 12,
        "last_check": "2025-12-25T10:30:00Z"
      },
      "stripe_gateway": {
        "status": "healthy",
        "response_time_ms": 89,
        "last_check": "2025-12-25T10:29:30Z"
      },
      "flutterwave_gateway": {
        "status": "healthy",
        "response_time_ms": 156,
        "last_check": "2025-12-25T10:29:30Z"
      },
      "exchange_rate_api": {
        "status": "healthy",
        "response_time_ms": 234,
        "last_update": "2025-12-25T09:00:00Z",
        "next_update": "2025-12-25T10:00:00Z"
      }
    },
    "statistics": {
      "invoices_today": 156,
      "successful_payments_today": 142,
      "failed_payments_today": 8,
      "success_rate_percent": 94.67,
      "total_revenue_today": {
        "TZS": 7850000,
        "USD": 3278.25
      }
    }
  }
}
```

---

## 8. WEBHOOK ENDPOINTS

### Stripe Webhook
Receive Stripe payment notifications (no API key required).

```http
POST /webhooks/stripe
```

### FlutterWave Webhook  
Receive FlutterWave payment notifications (no API key required).

```http
POST /webhooks/flutterwave
```

### Generic Provider Webhook
Receive notifications from any payment provider.

```http
POST /webhooks/{provider}
```

### Test Webhook
Development testing endpoint.

```http
POST /webhooks/test
```

---

## CONSOLE COMMANDS & AUTOMATION

### Exchange Rate Management
```bash
# Update all exchange rates from API
php artisan billing:update-exchange-rates

# Update specific currencies only
php artisan billing:update-exchange-rates --currency=KES,UGX

# Force update even if rates are recent
php artisan billing:update-exchange-rates --force
```

### Billing Operations
```bash
# Process subscription renewals
php artisan billing:process-renewals

# Retry failed payments
php artisan billing:retry-failed-payments

# Clean up expired price locks
php artisan billing:cleanup-price-locks

# Send payment reminders
php artisan billing:send-payment-reminders

# Generate daily billing report
php artisan billing:daily-report
```

---

## TESTING ENDPOINTS

### Development Testing
Use these endpoints in your development environment:

```bash
# Test API connectivity
curl -X GET "http://localhost:8000/api/billing/health" \
  -H "X-API-Key: test_api_key"

# Test price preview
curl -X GET "http://localhost:8000/api/billing/prices?amount=50000&currency=TZS" \
  -H "X-API-Key: test_api_key"

# Test currency conversion
curl -X POST "http://localhost:8000/api/billing/convert" \
  -H "X-API-Key: test_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50000,
    "from_currency": "TZS",
    "to_currency": "KES"
  }'

# Test subscription invoice creation
curl -X POST "http://localhost:8000/api/billing/create-invoice" \
  -H "X-API-Key: test_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "product_code": "shulesoft",
    "invoice_type": "subscription",
    "customer": {
      "name": "Test School",
      "phone": "255123456789"
    },
    "amount": 50000,
    "currency": "TZS",
    "feature_code": "core",
    "plan_code": "basic",
    "success_url": "http://localhost:3000/success",
    "cancel_url": "http://localhost:3000/cancel"
  }'

# Test wallet topup invoice
curl -X POST "http://localhost:8000/api/billing/create-invoice" \
  -H "X-API-Key: test_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "product_code": "shulesoft",
    "invoice_type": "wallet_topup",
    "customer": {
      "name": "Test School",
      "phone": "255123456789"
    },
    "amount": 100000,
    "currency": "TZS",
    "units": 2000,
    "unit_price": 50,
    "wallet_type": "sms",
    "success_url": "http://localhost:3000/success",
    "cancel_url": "http://localhost:3000/cancel"
  }'
```

---

## IMPLEMENTATION NOTES

### Subscription vs Non-Subscription Management

**SUBSCRIPTION MANAGEMENT** handles:
- Recurring billing (monthly/yearly)
- Plan upgrades/downgrades  
- Subscription lifecycle (active, cancelled, expired)
- Auto-renewals and billing cycles
- Grace periods for failed payments
- Prorated billing for plan changes

**NON-SUBSCRIPTION (WALLET) MANAGEMENT** handles:
- One-time prepaid purchases
- Usage-based billing (pay-as-you-go)
- Credit/unit deduction tracking
- Wallet balance monitoring
- Bulk purchase discounts
- No recurring charges

### Multi-Currency Implementation Details

1. **Base Currency**: All prices stored in TZS (Tanzanian Shilling)
2. **Display Currencies**: Customers see prices in their preferred currency
3. **Price Locking**: Exchange rates locked for 15 minutes during checkout
4. **FX Buffer**: 3% margin added to protect against volatility
5. **Smart Rounding**: Currency-specific rounding for better UX
6. **Automated Updates**: Hourly exchange rate refresh from external API

### API Key Permissions

Configure API key permissions in the `api_keys` table:

```sql
-- Full access API key
INSERT INTO api_keys (key_name, api_key, product_codes, permissions, rate_limit) VALUES (
  'ShuleSoft Dashboard',
  'sk_live_abc123xyz789',
  '["shulesoft", "safarichat", "lineshop"]',
  '["create_invoice", "manage_subscriptions", "manage_wallets", "view_reports", "process_payments"]',
  120
);

-- Read-only API key
INSERT INTO api_keys (key_name, api_key, product_codes, permissions, rate_limit) VALUES (
  'Analytics Dashboard',
  'sk_readonly_def456uvw012', 
  '["shulesoft"]',
  '["view_invoices", "view_customers", "view_reports"]',
  60
);

-- Webhook-only API key
INSERT INTO api_keys (key_name, api_key, product_codes, permissions, rate_limit) VALUES (
  'Payment Webhooks',
  'sk_webhook_ghi789rst345',
  '["*"]',
  '["webhook_processing"]',
  300
);
```

This comprehensive documentation covers all aspects of the Safari Billing Platform API, including multi-currency support, subscription management, wallet operations, and detailed request/response examples.
{
  "success": true,
  "data": {
    "invoice": {
      "invoice_id": "INV-123456",
      "reference": "SF_1_123_20251224123456",
      "status": "pending",
      "amount": 50000,
      "currency": "TZS"
    },
    "customer": {
      "id": "CUST-123",
      "ucn": "SF_1_123_20251224123456",
      "name": "Test School"
    },
    "payment_session": {
      "session_id": "cs_test_123",
      "payment_url": "https://checkout.stripe.com/pay/cs_test_123"
    }
  }
}
```

### Get Products

Retrieve available billing products and their pricing.

```http
GET /products
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "product_code": "shulesoft",
      "name": "ShuleSoft Management System",
      "description": "Complete school management solution",
      "price": 50000,
      "currency": "TZS",
      "billing_cycle": "monthly"
    }
  ]
}
```

### Get Customer Status

Get comprehensive customer billing status and subscription information.

```http
GET /customer/{identifier}/status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": "CUST-123",
      "ucn": "SF_1_123_20251224123456",
      "name": "Test School",
      "country": "TZ",
      "currency": "TZS"
    },
    "subscriptions": [
      {
        "feature_code": "core",
        "plan_code": "pro",
        "status": "active",
        "start_date": "2025-01-01T00:00:00Z",
        "end_date": "2025-02-01T00:00:00Z"
      }
    ],
    "wallet": {
      "balance": 1000.00,
      "currency": "TZS"
    }
  }
}
```

### Deduct Wallet Balance

Deduct amount from customer's wallet for usage-based billing.

```http
POST /deduct-wallet
```

**Request Body:**
```json
{
  "student_phone": "255123456789",
  "product_code": "safarichat",
  "wallet_type": "whatsapp_messages",
  "amount": 10
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "deducted": 10,
    "wallet_type": "whatsapp_messages",
    "processed_at": "2025-12-24T12:34:56Z"
  }
}
```

### Get Wallet Balance

Retrieve customer's wallet balance.

```http
GET /wallet/balance?student_id=123
```

**Response:**
```json
{
  "success": true,
  "data": {
    "student_id": "123",
    "balance": 1000.00,
    "currency": "TZS",
    "formatted_balance": "1,000.00 TZS",
    "last_transaction_date": "2025-12-24T12:34:56Z"
  }
}
```

## Wallet Management

### Get All Wallet Balances

Retrieve all wallet balances for a customer.

```http
GET /wallets?student_id=123
```

**Response:**
```json
{
  "success": true,
  "data": {
    "student_id": "123",
    "wallets": [
      {
        "wallet_type": "sms",
        "product_code": "shulesoft",
        "balance": 5000,
        "unit": "sms",
        "frozen": false,
        "last_used": "2025-12-24T10:30:00Z"
      },
      {
        "wallet_type": "whatsapp_messages",
        "product_code": "safarichat",
        "balance": 1500,
        "unit": "message",
        "frozen": false,
        "last_used": "2025-12-24T12:00:00Z"
      },
      {
        "wallet_type": "ai_credits",
        "product_code": "safarichat",
        "balance": 25000,
        "unit": "credit",
        "frozen": false,
        "last_used": "2025-12-24T11:45:00Z"
      }
    ],
    "total_value": {
      "amount": 75500,
      "currency": "TZS",
      "formatted": "75,500.00 TZS"
    }
  }
}
```

### Top Up Wallet

Add credits to a specific wallet.

```http
POST /wallet/topup
```

**Request Body:**
```json
{
  "student_id": 123,
  "product_code": "shulesoft",
  "wallet_type": "sms",
  "amount": 1000,
  "unit_price": 50,
  "currency": "TZS",
  "payment_method": "card"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "invoice": {
      "invoice_id": "INV-789012",
      "amount": 50000,
      "currency": "TZS",
      "units_purchased": 1000
    },
    "payment_session": {
      "session_id": "cs_topup_456",
      "payment_url": "https://checkout.stripe.com/pay/cs_topup_456"
    }
  }
}
```

### Get Wallet Transaction History

Retrieve transaction history for a specific wallet.

```http
GET /wallet/{student_id}/transactions?wallet_type=sms&limit=50
```

**Response:**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "transaction_id": "TXN-001",
        "type": "credit",
        "amount": 1000,
        "balance_before": 4000,
        "balance_after": 5000,
        "description": "SMS Bundle Purchase",
        "created_at": "2025-12-24T09:00:00Z"
      },
      {
        "transaction_id": "TXN-002",
        "type": "debit",
        "amount": 50,
        "balance_before": 5000,
        "balance_after": 4950,
        "description": "Bulk SMS to parents",
        "created_at": "2025-12-24T10:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 125,
      "total_pages": 3
    }
  }
}
```

### Cancel Subscription

Cancel a customer's subscription.

```http
POST /subscription/cancel
```

**Request Body:**
```json
{
  "student_id": 123,
  "feature_code": "core",
  "immediate": false
}
```

### Change Subscription Plan

Upgrade or downgrade a subscription plan.

```http
POST /subscription/change-plan
```

**Request Body:**
```json
{
  "student_id": 123,
  "current_feature_code": "core",
  "new_feature_code": "core",
  "new_plan_code": "premium"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "subscription": {
      "feature_code": "core",
      "plan_code": "premium",
      "status": "active",
      "start_date": "2025-12-24T00:00:00Z",
      "end_date": "2026-01-24T00:00:00Z"
    },
    "prorated_amount": 15000,
    "next_billing_date": "2026-01-24T00:00:00Z"
  }
}
```

### Retry Failed Payment

Retry a failed payment for an existing invoice.

```http
POST /payments/{invoice_id}/retry
```

**Response:**
```json
{
  "success": true,
  "data": {
    "invoice_id": "INV-123456",
    "payment_status": "processing",
    "retry_attempt": 2,
    "next_retry_at": "2025-12-25T12:00:00Z"
  }
}
```

### Health Check

Check billing system health and connectivity.

```http
GET /health
```

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-12-24T12:34:56Z",
  "version": "1.0.0",
  "database": "connected",
  "cache": "connected",
  "payment_gateways": {
    "stripe": "operational",
    "flutterwave": "operational"
  }
}
```

### Get Invoice Details

Retrieve detailed information about a specific invoice.

```http
GET /invoices/{invoice_id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "invoice": {
      "invoice_id": "INV-123456",
      "reference": "SF_1_123_20251224123456",
      "status": "paid",
      "amount": 50000,
      "currency": "TZS",
      "invoice_type": "subscription",
      "feature_code": "core",
      "plan_code": "standard",
      "created_at": "2025-12-24T12:00:00Z",
      "paid_at": "2025-12-24T12:05:00Z",
      "due_at": "2025-12-31T23:59:59Z"
    },
    "customer": {
      "id": "CUST-123",
      "name": "Test School",
      "phone": "255123456789"
    },
    "payments": [
      {
        "payment_id": "PAY-789",
        "amount": 50000,
        "payment_method": "card",
        "status": "completed",
        "gateway": "stripe",
        "transaction_id": "txn_123abc",
        "processed_at": "2025-12-24T12:05:00Z"
      }
    ]
  }
}
```

## Webhooks

Webhook endpoints for payment gateway notifications. These endpoints do not require API key authentication but use signature verification.

### Stripe Webhooks

```http
POST /webhooks/stripe
```

### FlutterWave Webhooks

```http
POST /webhooks/flutterwave
```

### Test Webhooks (Development Only)

Test webhook processing in development environment.

```http
POST /webhooks/test
```

**Request Body:**
```json
{
  "event_type": "payment.successful",
  "invoice_id": "INV-123456",
  "amount": 50000,
  "currency": "TZS",
  "payment_method": "card",
  "transaction_id": "test_txn_123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Test webhook processed successfully",
  "data": {
    "invoice_updated": true,
    "subscription_activated": true,
    "notifications_sent": ["email", "sms"]
  }
}
```

## Subscription Management

### Get Subscription Details

Retrieve detailed subscription information for a customer.

```http
GET /subscriptions?student_id=123&feature_code=core
```

**Response:**
```json
{
  "success": true,
  "data": {
    "subscription": {
      "feature_code": "core",
      "plan_code": "standard",
      "status": "active",
      "billing_period": "monthly",
      "start_date": "2025-12-01T00:00:00Z",
      "end_date": "2025-12-31T23:59:59Z",
      "auto_renew": true,
      "next_billing_date": "2026-01-01T00:00:00Z",
      "trial_end_date": null
    },
    "usage": {
      "current_period_usage": 75,
      "usage_limit": 1000,
      "usage_percentage": 7.5
    }
  }
}
```

### Pause Subscription

Temporarily pause a subscription (retains access until current period ends).

```http
POST /subscription/pause
```

**Request Body:**
```json
{
  "student_id": 123,
  "feature_code": "core",
  "pause_reason": "temporary_break",
  "resume_date": "2026-02-01T00:00:00Z"
}
```

### Resume Subscription

Resume a paused subscription.

```http
POST /subscription/resume
```

**Request Body:**
```json
{
  "student_id": 123,
  "feature_code": "core"
}
```

## Error Responses

All API errors follow a consistent format:

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message",
    "errors": {} // Validation errors (if applicable)
  }
}
```

### Common Error Codes

#### Authentication & Authorization
- `INVALID_API_KEY`: Missing or invalid API key
- `UNAUTHORIZED`: Not authorized to access this resource
- `FORBIDDEN`: Access denied for this operation

#### Rate Limiting
- `RATE_LIMIT_EXCEEDED`: Too many requests
- `DAILY_LIMIT_EXCEEDED`: Daily API limit reached

#### Validation Errors
- `VALIDATION_ERROR`: Request validation failed
- `REQUIRED_FIELD_MISSING`: Required field is missing
- `INVALID_FORMAT`: Field format is invalid
- `INVALID_AMOUNT`: Amount must be positive number
- `INVALID_CURRENCY`: Unsupported currency code

#### Resource Errors
- `PRODUCT_NOT_FOUND`: Specified product does not exist
- `CUSTOMER_NOT_FOUND`: Customer not found
- `INVOICE_NOT_FOUND`: Invoice not found
- `SUBSCRIPTION_NOT_FOUND`: Subscription not found
- `PAYMENT_NOT_FOUND`: Payment record not found

#### Business Logic Errors
- `INSUFFICIENT_BALANCE`: Not enough wallet balance
- `SUBSCRIPTION_EXPIRED`: Subscription has expired
- `SUBSCRIPTION_ALREADY_CANCELLED`: Subscription is already cancelled
- `PAYMENT_ALREADY_PROCESSED`: Payment already processed
- `WALLET_FROZEN`: Wallet is frozen due to subscription issues
- `PLAN_CHANGE_NOT_ALLOWED`: Cannot change to requested plan
- `DUPLICATE_INVOICE`: Invoice with same reference already exists

#### Payment Gateway Errors
- `PAYMENT_FAILED`: Payment processing failed
- `PAYMENT_DECLINED`: Payment was declined by bank
- `CARD_EXPIRED`: Payment card has expired
- `INSUFFICIENT_FUNDS`: Insufficient funds in account
- `GATEWAY_ERROR`: Payment gateway error
- `GATEWAY_TIMEOUT`: Gateway response timeout

#### System Errors
- `WEBHOOK_PROCESSING_FAILED`: Webhook processing error
- `DATABASE_ERROR`: Database operation failed
- `EXTERNAL_SERVICE_ERROR`: External service unavailable
- `CONFIGURATION_ERROR`: System configuration error
- `MAINTENANCE_MODE`: System is in maintenance mode

## Billing Reports & Analytics

### Generate Billing Report

Generate comprehensive billing reports for analysis.

```http
GET /reports/billing?period=monthly&year=2025&month=12
```

**Response:**
```json
{
  "success": true,
  "data": {
    "report_period": "2025-12",
    "summary": {
      "total_revenue": 2500000,
      "total_invoices": 125,
      "paid_invoices": 118,
      "pending_invoices": 5,
      "failed_invoices": 2,
      "payment_success_rate": 94.4,
      "average_invoice_value": 20000
    },
    "revenue_by_product": [
      {
        "product_code": "shulesoft",
        "product_name": "ShuleSoft Management",
        "revenue": 1800000,
        "percentage": 72.0
      },
      {
        "product_code": "safarichat",
        "product_name": "SafariChat WhatsApp",
        "revenue": 700000,
        "percentage": 28.0
      }
    ],
    "payment_methods": [
      {
        "method": "mobile_money",
        "count": 85,
        "percentage": 72.0
      },
      {
        "method": "card",
        "count": 33,
        "percentage": 28.0
      }
    ]
  }
}
```

### Customer Analytics

Get detailed analytics for customer behavior and billing patterns.

```http
GET /analytics/customers?date_from=2025-12-01&date_to=2025-12-31
```

**Response:**
```json
{
  "success": true,
  "data": {
    "metrics": {
      "new_customers": 25,
      "churned_customers": 3,
      "reactivated_customers": 5,
      "average_customer_lifetime_value": 240000,
      "average_monthly_revenue_per_user": 20000
    },
    "retention_rates": {
      "month_1": 95.2,
      "month_3": 87.5,
      "month_6": 78.3,
      "month_12": 65.8
    },
    "churn_analysis": {
      "primary_reasons": [
        {"reason": "payment_failed", "percentage": 45.2},
        {"reason": "cancelled_by_customer", "percentage": 38.7},
        {"reason": "trial_expired", "percentage": 16.1}
      ]
    }
  }
}
```

## Command Line Integration

### Billing Integration Commands

The system provides several Artisan commands for billing operations:

```bash
# Process subscription renewals
php artisan billing:process-renewals

# Retry failed payments
php artisan billing:retry-failed-payments

# Send renewal reminders
php artisan billing:send-renewal-reminders

# Generate billing reports
php artisan billing:generate-report --period=monthly --output=csv

# Health check
php artisan billing:health-check

# Sync with billing platform
php artisan billing:integrate --sync-students --sync-invoices
```

## Testing

### Development Environment

Use the test endpoint in local environment:

```http
POST /webhooks/test
```

This endpoint accepts any JSON payload and returns it for testing webhook integrations.

### Test Payment Flow

```bash
# Test complete payment flow
curl -X POST http://localhost/api/billing/create-invoice \
  -H "X-API-Key: test_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "product_code": "shulesoft",
    "invoice_type": "subscription",
    "customer": {
      "name": "Test School",
      "phone": "255123456789",
      "email": "test@school.com"
    },
    "amount": 50000,
    "currency": "TZS",
    "success_url": "http://localhost/success",
    "cancel_url": "http://localhost/cancel"
  }'
```