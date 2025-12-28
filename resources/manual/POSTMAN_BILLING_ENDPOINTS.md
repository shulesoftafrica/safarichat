# SAFARIBOOK Billing API - Postman Live Testing Examples

## Base Configuration

**Base URL:** `http://localhost/shulesoft_newversion/api/billing`

**Headers for All Requests:**
```
X-API-Key: YOUR_API_KEY_HERE
Content-Type: application/json
Accept: application/json
```

---

## 1. SYSTEM VERIFICATION & BASIC OPERATIONS

### 1.1 Get Available Currencies
**Method:** `GET`  
**URL:** `{{base_url}}/currencies`  

**Expected Response:**
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
      }
    ]
  }
}
```

---

## 2. MULTI-CURRENCY OPERATIONS

### 2.1 Get Price Preview
**Method:** `GET`  
**URL:** `{{base_url}}/prices`  
**Query Params:**
```
amount: 50000
currency: TZS
target_currencies: KES,USD
use_buffer: true
```

### 2.2 Convert Currency
**Method:** `POST`  
**URL:** `{{base_url}}/convert`  
**Body (JSON):**
```json
{
  "amount": 50000,
  "from_currency": "TZS",
  "to_currency": "KES",
  "use_buffer": true
}
```

---

## 3. PRODUCTS CATALOG

### 3.1 Get Products Catalog
**Method:** `GET`  
**URL:** `{{base_url}}/products`  
**Description:** Retrieve the list of available products with pricing and plan information.
**Query Params:**
```
product_code: shulesoft
currency: TZS
active_only: true
```

### 3.2 Create Product
**Method:** `POST`  
**URL:** `{{base_url}}/products`  
**Description:** Create a new product with plans and pricing configuration.
**Body (JSON):**
```json
{
  "product_code": "shulesoft",
  "name": "ShuleSoft School Management System",
  "description": "Complete school management and communication platform",
  "default_currency": "TZS",
  "subscription_enabled": true,
  "wallet_enabled": true,
  "plans": {
    "basic": {
      "name": "Basic Plan",
      "price": 50000,
      "currency": "TZS",
      "billing_cycle": "monthly",
      "features": ["student_management", "basic_reports", "sms_notifications"]
    },
    "premium": {
      "name": "Premium Plan", 
      "price": 150000,
      "currency": "TZS",
      "billing_cycle": "monthly",
      "features": ["student_management", "advanced_reports", "sms_notifications", "parent_portal", "fee_management"]
    }
  },
  "wallet_types": ["sms", "whatsapp_messages"],
  "entitlements": {
    "max_students": 1000,
    "max_teachers": 100,
    "storage_gb": 50,
    "api_calls_per_month": 50000
  },
  "metadata": {
    "category": "education",
    "target_market": "schools",
    "region": "East Africa"
  }
}
```

### 3.3 Update Product
**Method:** `PUT`  
**URL:** `{{base_url}}/products/shulesoft`  
**Body (JSON):**
```json
{
  "name": "ShuleSoft School Management System - Updated",
  "description": "Enhanced school management platform with new features",
  "plans": {
    "basic": {
      "price": 55000,
      "currency": "TZS"
    },
    "premium": {
      "price": 160000,
      "currency": "TZS"
    },
    "enterprise": {
      "name": "Enterprise Plan",
      "price": 300000,
      "currency": "TZS",
      "billing_cycle": "monthly",
      "features": ["all_premium_features", "custom_integrations", "dedicated_support"]
    }
  }
}
```

### 3.4 Delete Product
**Method:** `DELETE`  
**URL:** `{{base_url}}/products/shulesoft`  
**Query Params:**
```
force: false
archive_subscriptions: true
```

---

## 4. INVOICE CREATION & MANAGEMENT

### 4.1 Create Subscription Invoice
**Method:** `POST`  
**URL:** `{{base_url}}/create-invoice`  
**Body (JSON):**
```json
{
  "product_code": "shulesoft",
  "invoice_type": "subscription",
  "customer": {
    "name": "Mwenge Secondary School",
    "phone": "255123456789",
    "email": "admin@mwenge.edu.tz"
  },
  "amount": 50000,
  "currency": "TZS",
  "feature_code": "core",
  "plan_code": "basic",
  "billing_cycle": "monthly",
  "success_url": "http://localhost/shulesoft_newversion/payment-success",
  "cancel_url": "http://localhost/shulesoft_newversion/payment-cancel",
  "metadata": {
    "school_id": "SCH001",
    "region": "Dar es Salaam"
  }
}
```

### 4.2 Create Wallet Topup Invoice
**Method:** `POST`  
**URL:** `{{base_url}}/create-invoice`  
**Body (JSON):**
```json
{
  "product_code": "shulesoft",
  "invoice_type": "wallet_topup",
  "customer": {
    "name": "Mwenge Secondary School",
    "phone": "255123456789",
    "email": "admin@mwenge.edu.tz"
  },
  "amount": 100000,
  "currency": "TZS",
  "units": 2000,
  "unit_price": 50,
  "wallet_type": "sms",
  "success_url": "http://localhost/shulesoft_newversion/topup-success",
  "cancel_url": "http://localhost/shulesoft_newversion/topup-cancel",
  "metadata": {
    "wallet_purpose": "Parent communication",
    "expected_usage": "Monthly SMS campaigns"
  }
}
```

### 4.3 Create Plan Change Invoice
**Method:** `POST`  
**URL:** `{{base_url}}/create-invoice`  
**Body (JSON):**
```json
{
  "product_code": "shulesoft",
  "invoice_type": "plan_upgrade",
  "customer": {
    "name": "Mwenge Secondary School",
    "phone": "255123456789"
  },
  "amount": 15000,
  "currency": "TZS",
  "old_plan_code": "basic",
  "new_plan_code": "premium",
  "feature_code": "core",
  "proration_credit": 5000,
  "success_url": "http://localhost/shulesoft_newversion/upgrade-success",
  "cancel_url": "http://localhost/shulesoft_newversion/upgrade-cancel"
}
```

### 4.4 Get Invoice Details
**Method:** `GET`  
**URL:** `{{base_url}}/invoices/INV-20251227-001234`  
**Note:** Replace with actual invoice ID from previous requests

---

## 5. CUSTOMER MANAGEMENT

### 5.1 Get Customer Status by Phone
**Method:** `GET`  
**URL:** `{{base_url}}/customer/255123456789/status`  
**Query Params:**
```
include: subscriptions,wallets,invoices
```

### 5.2 Get Customer Status by Email
**Method:** `GET`  
**URL:** `{{base_url}}/customer/admin@mwenge.edu.tz/status`  

### 5.3 Get Customer Status by Student ID
**Method:** `GET`  
**URL:** `{{base_url}}/customer/12345/status`  

---

## 6. SUBSCRIPTION LIFECYCLE MANAGEMENT

### 6.1 Cancel Subscription
**Method:** `POST`  
**URL:** `{{base_url}}/subscription/cancel`  
**Body (JSON):**
```json
{
  "student_id": 12345,
  "feature_code": "core",
  "immediate": false,
  "reason": "Budget constraints",
  "refund_requested": false
}
```

### 6.2 Reactivate Subscription
**Method:** `POST`  
**URL:** `{{base_url}}/subscription/reactivate`  
**Body (JSON):**
```json
{
  "student_id": 12345,
  "feature_code": "core",
  "plan_code": "basic",
  "billing_cycle": "monthly",
  "payment_method": "existing"
}
```

### 6.3 Change Subscription Plan
**Method:** `POST`  
**URL:** `{{base_url}}/subscription/change-plan`  
**Body (JSON):**
```json
{
  "student_id": 12345,
  "current_feature_code": "core",
  "new_feature_code": "core",
  "current_plan_code": "basic",
  "new_plan_code": "premium",
  "new_billing_cycle": "yearly",
  "effective_date": "immediate"
}
```

---

## 7. WALLET OPERATIONS

### 7.1 Get Wallet Balance (All Wallets)
**Method:** `GET`  
**URL:** `{{base_url}}/wallet/balance`  
**Query Params:**
```
student_id: 12345
```

### 7.2 Get Specific Wallet Balance
**Method:** `GET`  
**URL:** `{{base_url}}/wallet/balance`  
**Query Params:**
```
student_id: 12345
wallet_type: sms
```

### 7.3 Deduct from Wallet
**Method:** `POST`  
**URL:** `{{base_url}}/deduct-wallet`  
**Body (JSON):**
```json
{
  "student_id": 12345,
  "product_code": "shulesoft",
  "wallet_type": "sms",
  "amount": 50,
  "description": "Bulk SMS to parents - Grade 10 results",
  "metadata": {
    "recipients_count": 45,
    "message_length": 160,
    "campaign_id": "CAMP-001"
  }
}
```

### 7.4 Get Wallet Transaction History
**Method:** `GET`  
**URL:** `{{base_url}}/wallet/12345/transactions`  
**Query Params:**
```
wallet_type: sms
limit: 50
offset: 0
start_date: 2025-12-01
end_date: 2025-12-27
transaction_type: all
```

---

## 8. PAYMENT OPERATIONS

### 8.1 Process Payment for Invoice
**Method:** `POST`  
**URL:** `{{base_url}}/invoice/INV-20251227-001234/payment`  
**Body (JSON):**
```json
{
  "payment_method": "card",
  "currency": "TZS",
  "return_url": "http://localhost/shulesoft_newversion/payment-complete",
  "metadata": {
    "payment_source": "school_dashboard",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
    "ip_address": "127.0.0.1"
  }
}
```

### 8.2 Retry Failed Payment
**Method:** `POST`  
**URL:** `{{base_url}}/payments/INV-20251227-001234/retry`  
**Body (JSON):**
```json
{
  "payment_method": "card",
  "force_retry": false,
  "notification_customer": true
}
```

---

## 9. WEBHOOK TESTING

### 9.1 Test Webhook (Development)
**Method:** `POST`  
**URL:** `{{base_url}}/webhooks/test`  
**Body (JSON):**
```json
{
  "event_type": "payment.successful",
  "invoice_id": "INV-20251227-001234",
  "amount": 50000,
  "currency": "TZS",
  "payment_method": "card",
  "transaction_id": "test_txn_123",
  "customer": {
    "student_id": 12345,
    "name": "Mwenge Secondary School",
    "phone": "255123456789"
  }
}
```

### 9.2 Stripe Webhook Simulation
**Method:** `POST`  
**URL:** `{{base_url}}/webhooks/stripe`  
**Headers:** 
```
Stripe-Signature: whsec_test_signature
```
**Body (JSON):**
```json
{
  "id": "evt_test_webhook",
  "object": "event",
  "type": "payment_intent.succeeded",
  "data": {
    "object": {
      "id": "pi_test_123456",
      "amount": 5000000,
      "currency": "tzs",
      "status": "succeeded",
      "metadata": {
        "invoice_id": "INV-20251227-001234",
        "student_id": "12345"
      }
    }
  }
}
```

### 9.3 FlutterWave Webhook Simulation
**Method:** `POST`  
**URL:** `{{base_url}}/webhooks/flutterwave`  
**Body (JSON):**
```json
{
  "event": "charge.completed",
  "data": {
    "id": 123456,
    "tx_ref": "INV-20251227-001234",
    "status": "successful",
    "amount": 50000,
    "currency": "TZS",
    "customer": {
      "id": 12345,
      "name": "Mwenge Secondary School",
      "email": "admin@mwenge.edu.tz",
      "phone_number": "255123456789"
    }
  }
}
```

---

## 10. REPORTING & ANALYTICS

### 10.1 Generate Billing Report
**Method:** `GET`  
**URL:** `{{base_url}}/reports/billing`  
**Query Params:**
```
period: monthly
year: 2025
month: 12
```

### 10.2 Customer Analytics
**Method:** `GET`  
**URL:** `{{base_url}}/analytics/customers`  
**Query Params:**
```
date_from: 2025-12-01
date_to: 2025-12-27
```

---

## POSTMAN ENVIRONMENT VARIABLES

Create these environment variables in Postman:

```json
{
  "base_url": "http://localhost/shulesoft_newversion/api/billing",
  "api_key": "YOUR_API_KEY_HERE",
  "test_student_id": "12345",
  "test_phone": "255123456789",
  "test_email": "admin@mwenge.edu.tz",
  "test_invoice_id": "INV-20251227-001234"
}
```

---

## TESTING SEQUENCE RECOMMENDATION

### Phase 1: System Verification
1. Get Available Currencies
2. Get Products Catalog
3. Get Price Preview

### Phase 2: Product Management
4. Create Product
5. Update Product
6. Convert Currency

### Phase 3: Basic Operations
7. Create Subscription Invoice
8. Get Invoice Details

### Phase 4: Customer Management
9. Get Customer Status (will create customer if not exists)
10. Create Wallet Topup Invoice
11. Get Wallet Balance

### Phase 5: Wallet Operations
12. Deduct from Wallet
13. Get Wallet Transaction History

### Phase 6: Subscription Management
14. Change Subscription Plan
15. Cancel Subscription
16. Reactivate Subscription

### Phase 7: Payment Processing
17. Process Payment for Invoice
18. Retry Failed Payment

### Phase 8: Webhooks & Integration
19. Test Webhook
20. Stripe Webhook Simulation
21. FlutterWave Webhook Simulation

### Phase 9: Analytics
22. Generate Billing Report
23. Customer Analytics

### Phase 10: Cleanup
24. Delete Product (if testing)

---

## COMMON TEST DATA

Use these consistent test values across all requests:

**Test Customer:**
```json
{
  "student_id": 12345,
  "name": "Mwenge Secondary School",
  "phone": "255123456789",
  "email": "admin@mwenge.edu.tz",
  "country": "TZ",
  "currency": "TZS"
}
```

**Test Amounts:**
- Subscription: 50000 TZS
- Wallet Topup: 100000 TZS
- Plan Upgrade: 15000 TZS

**Test Products:**
- Product Code: "shulesoft"
- Feature Code: "core"
- Plan Codes: "basic", "premium"
- Wallet Types: "sms", "whatsapp_messages", "ai_credits"

---

## EXPECTED RESULTS

### Success Indicators:
- All GET requests return 200 status
- All POST requests return 201 status for creation
- Invoice creation returns payment URLs
- Wallet operations update balances correctly
- Webhooks return success confirmations

### Error Testing:
- Invalid API key returns 401
- Missing required fields return 422
- Non-existent resources return 404
- Rate limiting returns 429

---

## POSTMAN COLLECTION IMPORT

To import this as a Postman collection, save the requests in the format above and use Postman's import feature. Make sure to:

1. Set up environment variables
2. Configure authentication headers
3. Set up pre-request scripts for dynamic values
4. Add tests for response validation

This comprehensive test suite will verify all aspects of your SAFARIBOOK billing system functionality.