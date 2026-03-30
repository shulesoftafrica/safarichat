📦 Payload Reference
All events share a common envelope. Fields that don't apply to a given event are sent as null.

Common Envelope Fields
Field	Type	Description
event	string	Event name — route your handler on this field
event_id	string	Globally unique ID — use for deduplication
timestamp	ISO 8601	When the event was triggered
api_version	string	Payload schema version (2026-03-24)
customer_id	integer	Shortcut to the customer — also present inside customer.id
Complete payment.success Example
{
  "event":       "payment.success",
  "event_id":    "evt_68026f3a4b1e2",
  "timestamp":   "2026-03-29T10:15:00+00:00",
  "api_version": "2026-03-24",
  "customer_id": 42,

  "product": {
    "id": 3,
    "name": "School Management System",
    "product_code": "SMS-001",
    "organization_id": 1,
    "status": "active"
  },

  "organization": {
    "id": 1,
    "name": "Shule Soft Africa"
  },

  "payment": {
    "id":                187,
    "transaction_id":    "pi_3OqXyz",
    "amount":            150000.00,
    "currency":          "TZS",
    "status":            "success",
    "payment_method":    "card",
    "gateway":           "stripe",
    "gateway_reference": "pi_3OqXyz",
    "gateway_fee":       4500.00,
    "net_amount":        145500.00,
    "description":       "Invoice INV-2026-0042 payment",
    "paid_at":           "2026-03-29T10:14:58+00:00",
    "created_at":        "2026-03-29T10:14:50+00:00"
  },

  "invoice": {
    "id":             99,
    "invoice_number": "INV-2026-0042",
    "subtotal":       130435.00,
    "tax_total":      19565.00,
    "total":          150000.00,
    "amount_paid":    150000.00,
    "amount_due":     0.00,
    "currency":       "TZS",
    "status":         "paid",
    "due_date":       "2026-04-05",
    "issued_at":      "2026-03-29T08:00:00+00:00",
    "paid_at":        "2026-03-29T10:14:58+00:00",
    "items": [
      {
        "id":              201,
        "description":     "Term 1 Fees",
        "quantity":        1,
        "unit_price":      130435.00,
        "total":           130435.00,
        "price_plan_id":   5,
        "price_plan_name": "Standard Term Plan"
      }
    ],
    "ucn":             "9920240001234",
    "control_number":  "9920240001234",
    "control_numbers": ["9920240001234"]
  },

  "customer": {
    "id":         42,
    "product_id": 3,
    "name":       "Mwanafunzi Primary School",
    "email":      "accounts@mwanafunzi.ac.tz",
    "phone":      "+255712345678",
    "status":     "active"
  },

  "subscription": {
    "id":                   18,
    "status":               "active",
    "price_plan_id":        5,
    "price_plan_name":      "Standard Term Plan",
    "billing_interval":     "quarterly",
    "amount":               150000.00,
    "currency":             "TZS",
    "current_period_start": "2026-01-01",
    "current_period_end":   "2026-03-31",
    "next_billing_date":    "2026-04-01",
    "trial_ends_at":        null,
    "canceled_at":          null
  },

  "gateway_details": {
    "stripe": {
      "payment_intent_id":  "pi_3OqXyz",
      "charge_id":          "ch_3OqXyz",
      "payment_method_id":  "pm_3OqXyz",
      "customer_id":        "cus_Stripe123",
      "last4":              "4242",
      "brand":              "visa",
      "country":            "TZ",
      "receipt_url":        "https://pay.stripe.com/receipts/..."
    },
    "flutterwave": null,
    "ucn": null
  },

  "metadata": {
    "ip_address":           "41.75.200.10",
    "user_agent":           "Mozilla/5.0...",
    "webhook_triggered_at": "2026-03-29T10:15:00+00:00"
  }
}
📋Copy
💡 payment.status values: success (cleared), pending, failed, cancelled, refunded. The system stores payments internally as cleared but always sends success in the webhook payload.

Per-Event Differences
payment.failed — same as payment.success plus two extra fields on the payment object:

"payment": {
  "status":        "failed",
  "error_code":    "card_declined",
  "error_message": "Your card was declined."
}
📋Copy
Subscription events (subscription.created, subscription.renewed, subscription.cancelled, subscription.expired, subscription.upgraded) — invoice and payment are null unless a payment was captured (renewal). Each event adds its own block:

// subscription.cancelled — adds:
"cancellation": {
  "reason":       "Customer requested cancellation",
  "cancelled_at": "2026-03-29T10:15:00+00:00"
}

// subscription.expired — adds:
"expired_at": "2026-03-31"

// subscription.upgraded — adds:
"upgrade": {
  "previous_plan": { "id": 5, "name": "Standard Term Plan", "amount": 150000.00, "interval": "quarterly" },
  "new_plan":      { "id": 7, "name": "Premium Annual Plan", "amount": 500000.00, "interval": "yearly" },
  "upgraded_at":   "2026-03-29T10:15:00+00:00"
}
📋Copy
credits.purchased — replaces invoice and subscription with a credits block:

"credits": {
  "id":           55,
  "amount":       1000,
  "balance":      4200,
  "description":  "SMS credit top-up",
  "purchased_at": "2026-03-29T10:15:00+00:00"
}
📋Copy
gateway_details by gateway
Only the key matching the active gateway is populated; others are null.

// Flutterwave
"gateway_details": {
  "stripe": null,
  "flutterwave": {
    "transaction_id": 123456789,
    "flw_ref":        "FLW-MOCK-abc",
    "tx_ref":         "billing-187",
    "payment_type":   "mobilemoneyuganda",
    "card_brand":     null,
    "last4":          null
  },
  "ucn": null
}

// UCN (bank / control-number transfer)
"gateway_details": {
  "stripe": null,
  "flutterwave": null,
  "ucn": {
    "control_number":  "9920240001234",
    "bill_id":         "BILL-99",
    "payer_name":      "JOHN DOE",
    "payer_phone":     "+255712345678",
    "payment_channel": "bank_transfer",
    "sp_code":         "SP001"
  }
}
📋Copy
🏢 Organizations
Manage organizations and their billing configurations.

GET
/api/v1/organizations
List Organizations
▼
Get all organizations

Success Response 200 OK
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Acme Corporation",
      "email": "billing@acme.com",
      "currency": "USD",
      "timezone": "America/New_York"
    }
  ]
}
📋Copy
POST
/api/v1/organizations
Create Organization
▼
Create a new organization

Request Body
{
  "name": "Acme Corporation",
  "email": "billing@acme.com",
  "currency": "USD",
  "timezone": "America/New_York",
  "address": {
    "street": "123 Main St",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "US"
  }
}
📋Copy
Success Response 201 Created
{
  "success": true,
  "message": "Organization created successfully",
  "data": {
    "id": 1,
    "name": "Acme Corporation"
  }
}
📋Copy
GET
/api/v1/organizations/{id}
Get Organization
▼
Get organization details

Success Response 200 OK
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Acme Corporation",
    "email": "billing@acme.com",
    "currency": "USD",
    "timezone": "America/New_York",
    "payment_gateways": ["stripe", "flutterwave"]
  }
}
📋Copy
PUT
/api/v1/organizations/{id}
Update Organization
▼
Update organization details

Request Body
{
  "name": "Acme Corp LLC",
  "email": "new-billing@acme.com",
  "timezone": "Europe/London"
}
📋Copy
Success Response 200 OK
{
  "success": true,
  "message": "Organization updated successfully"
}
📋Copy
💰 Wallets & Usage-Based Billing
Manage usage-based products with wallet/credit systems. This section covers pay-per-use billing for services like API calls, SMS credits, storage, bandwidth, and other consumable resources.

📋 Workflow Overview:
Setup Product: Create a usage product (product_type_id = 3) with a price plan (rate = price per unit)
View Wallets: Get all wallet products to see available options and their price_plan_id
Customer Purchase: Create invoice using the price_plan_id (identifies product) and amount (total payment). System generates a wallet_id (UCN)
System Calculation: Quantity = amount ÷ rate (e.g., 50,000 TZS ÷ 50 TZS/SMS = 1,000 SMS)
Record Usage: Track consumption using the wallet_id (UCN) as customer uses the service (deducts from balance)
Check Balance: Query balance using wallet_id (UCN): balance = total_purchased - total_used
🎯 Key Concept - Product Identification:
Each price_plan_id is linked to a specific product (SMS, API Calls, Storage, etc.)
Different products have different price plans with different rates
Example: price_plan_id 15 = SMS product (50 TZS/SMS), price_plan_id 17 = API product (10 TZS/call)
When you specify price_plan_id: 15, the system knows you're buying SMS credits
📦 Step 1: Create Usage Product
First, create a product with product_type_id = 3 (Usage Product) to enable wallet/usage-based billing.

POST
/api/v1/products
Create Usage Product
▼
Create a product for usage-based billing (API calls, SMS, storage, etc.)

Request Body
{
  "organization_id": 1,
  "product_type_id": 3,
  "name": "SMS Credits",
  "product_code": "SMS-CREDITS",
  "description": "Prepaid SMS credits for bulk messaging",
  "unit": "SMS",
  "active": true,
  "price_plans": [
    {
      "name": "SMS Credit Package",
      "currency_id": 1,
      "rate": 50
    }
  ]
}
📋Copy
⚠️ Important: Set product_type_id = 3 to enable usage-based billing. This allows the system to track purchases and consumption separately.
Success Response 201 Created
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 12,
    "organization_id": 1,
    "product_type_id": 3,
    "name": "SMS Credits",
    "product_code": "SMS-CREDITS",
    "description": "Prepaid SMS credits for bulk messaging",
    "unit": "SMS",
    "active": true,
    "product_type": {
      "id": 3,
      "name": "Usage Product"
    },
    "price_plans": [
      {
        "id": 15,
        "name": "SMS Credit Package",
        "rate": 50,
        "currency": "TZS"
      }
    ]
  }
}
📋Copy
� Step 2: Get All Wallets
Retrieve all wallet products (product_type_id = 3) created for your organization. Use this to view available wallet types before creating invoices.

GET
/api/v1/wallets
Get All Wallets
▼
List all wallet/usage products in your organization

Request Body
{
  "active": true  // Optional: Filter by active status
}
📋Copy
💡 Tips: Use this endpoint to discover available wallet products and their price_plan_id values before creating invoices.
Success Response 200 OK
{
  "success": true,
  "message": "Wallets retrieved successfully",
  "data": [
    {
      "id": 12,
      "organization_id": 1,
      "product_type_id": 3,
      "name": "SMS Credits",
      "product_code": "SMS-CREDITS",
      "description": "Prepaid SMS credits for bulk messaging",
      "unit": "SMS",
      "active": true,
      "created_at": "2026-03-20T10:30:00.000000Z",
      "updated_at": "2026-03-20T10:30:00.000000Z",
      "organization": {
        "id": 1,
        "name": "Acme Corporation"
      },
      "product_type": {
        "id": 3,
        "name": "Usage Product",
        "description": "Usage-based or wallet product"
      },
      "price_plans": [
        {
          "id": 15,
          "name": "SMS Credit Package",
          "billing_type": "usage",
          "billing_interval": null,
          "amount": 0,
          "rate": 50,
          "currency_id": 1,
          "active": true,
          "created_at": "2026-03-20T10:30:00.000000Z"
        }
      ]
    },
    {
      "id": 18,
      "organization_id": 1,
      "product_type_id": 3,
      "name": "API Credits",
      "product_code": "API-CREDITS",
      "unit": "API_CALLS",
      "active": true,
      "price_plans": [
        {
          "id": 17,
          "name": "API Credit Package",
          "rate": 10,
          "currency_id": 1
        }
      ]
    }
  ]
}
📋Copy
✅ Use Case: Before creating a wallet top-up invoice, call this endpoint to see available products and their price_plan_id values.
💳 Step 3: Create Wallet Top-Up Invoice
Create an invoice for customers to purchase wallet credits. The price_plan_id identifies the specific product (e.g., SMS vs API Calls vs Storage), and the system automatically calculates the quantity based on the amount paid.

POST
/api/v1/invoices
Create Wallet Top-Up Invoice
▼
Generate an invoice for customers to buy usage credits (SMS, API calls, storage, etc.)

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
      "amount": 50000
    }
  ],
  "description": "SMS Credits Top-Up - 1000 SMS @ TZS 50 each",
  "currency": "TZS",
  "status": "issued"
}
📋Copy
🔑 How Product Selection Works:
price_plan_id: 15 identifies the specific usage product (e.g., SMS Credits product)
Each price plan is linked to ONE product and has a rate (price per unit)
The system calculates quantity as: quantity = amount ÷ rate
📊 Calculation Example:
Price Plan ID 15 → SMS Credits product (rate: TZS 50 per SMS)
Amount: TZS 50,000
Quantity Calculated: 50,000 ÷ 50 = 1,000 SMS credits
After payment, a ProductPurchase record is created with quantity: 1000
Parameter	Type	Required	Description
organization_id	integer	Required	Your organization ID
customer	object	Required	Customer details (name, email, phone)
products[].price_plan_id	integer	Required	Identifies the usage product (e.g., SMS product vs API product). Each price plan is linked to a specific product.
products[].amount	numeric	Required	Total payment amount. System calculates quantity as: amount ÷ rate
description	string	Optional	Invoice description for customer reference
currency	string	Required	3-letter currency code (e.g., TZS, USD, EUR)
💡 Multiple Products Example:
To purchase credits for different products, include multiple items in the products array:
{
  "products": [
    {
      "price_plan_id": 15,  // SMS Credits (rate: 50 TZS/SMS)
      "amount": 50000       // = 1000 SMS
    },
    {
      "price_plan_id": 17,  // API Credits (rate: 10 TZS/call)
      "amount": 100000      // = 10000 API calls
    }
  ]
}
Success Response 201 Created
{
  "success": true,
  "message": "Invoice created successfully",
  "data": {
    "id": 125,
    "invoice_number": "INV-20260315-0125",
    "customer_id": 45,
    "total": 50000,
    "currency": "TZS",
    "status": "issued",
    "description": "SMS Credits Top-Up - 1000 SMS @ TZS 50 each",
    "customer": {
      "id": 45,
      "name": "Tech Startup Inc",
      "email": "billing@techstartup.com"
    },
    "items": [
      {
        "id": 458,
        "price_plan_id": 15,
        "product_name": "SMS Credits",
        "quantity": 1,
        "unit_price": 50000,
        "total": 50000
      }
    ],
    "payment_details": {
      "flutterwave": {
        "payment_link": "https://checkout.flutterwave.com/v3/hosted/pay/abc123",
        "tx_ref": "INV-125-1710504000"
      }
    }
  }
}
📋Copy
⚠️ Next Step: After payment is received, the system automatically creates a ProductPurchase record that adds the credits to the customer's wallet balance.
📊 Step 4: Record Usage
Track customer consumption by recording usage events. This deducts from their available balance.

POST
/api/v1/product-usages
Record Product Usage
▼
Track customer usage of wallet credits (SMS sent, API calls made, storage consumed, etc.)

Request Body
{
  "wallet_id": "UCN1234567890",
  "quantity": 150
}
📋Copy
Parameter	Type	Required	Description
wallet_id	string	Required	Wallet UCN (Unique Control Number/Reference) identifying the customer's wallet for a specific product
quantity	integer	Required	Usage product ID (must have product_type_id = 3)
quantity	numeric	Required	Amount consumed (e.g., 150 SMS sent, 5GB stored)
Success Response 201 Created
{
  "success": true,
  "message": "Product usage recorded successfully",
  "data": {
    "id": 789,
    "wallet_id": "UCN1234567890",
    "quantity": 150,
    "customer": {
      "id": 45,
      "name": "Tech Startup Inc",
      "email": "billing@techstartup.com"
    },
    "product": {
      "id": 12,
      "name": "SMS Credits",
      "unit": "SMS"
    },
    "created_at": "2026-03-15T14:30:00.000000Z"
  }
}
📋Copy
Error Response 422 Unprocessable Entity
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "product_id": [
      "Product usage is only allowed for products with type usage."
    ]
  }
}
📋Copy
💰 Step 5: Check Balance
Get the current wallet balance using the wallet's unique control number (UCN).

GET
/api/v1/product-usages/{wallet_id}/balance
Get Usage Balance
▼
Check remaining credits for a specific wallet (Balance = Purchased - Used)

Request Body
Parameter	Type	Location	Description
wallet_id	string	Path	Wallet UCN (Unique Control Number) - obtained from invoice creation or purchase response
GET /api/v1/product-usages/UCN1234567890/balance
📋Copy
Success Response 200 OK
{
  "success": true,
  "message": "Usage balance retrieved successfully",
  "data": {
    "wallet_id": "UCN1234567890",
    "customer": {
      "id": 45,
      "name": "Tech Startup Inc",
      "email": "billing@techstartup.com",
      "phone": "+255734567890"
    },
    "product": {
      "id": 12,
      "name": "SMS Credits",
      "description": "Prepaid SMS credits for bulk messaging",
      "unit": "SMS"
    },
    "usage": {
      "total_purchased": 1000,
      "total_used": 150,
      "balance": 850
    }
  }
}
📋Copy
📈 Step 6: Get Usage Report
Retrieve comprehensive usage report for a customer across all products.

GET
/api/v1/product-usages/{customer_id}/report
Get Usage Report
▼
Get detailed usage summary for billing period

Success Response 200 OK
{
  "success": true,
  "message": "Usage report retrieved successfully",
  "data": {
    "customer": {
      "id": 45,
      "name": "Tech Startup Inc",
      "email": "billing@techstartup.com",
      "phone": "+255734567890"
    },
    "usage_by_product": [
      {
        "product": {
          "id": 12,
          "name": "SMS Credits",
          "description": "Prepaid SMS credits",
          "unit": "SMS",
          "usage": {
            "total_purchased": 5000,
            "total_used": 3450,
            "balance": 1550
          }
        }
      },
      {
        "product": {
          "id": 13,
          "name": "API Calls",
          "description": "Prepaid API call credits",
          "unit": "calls",
          "usage": {
            "total_purchased": 50000,
            "total_used": 45230,
            "balance": 4770
          }
        }
      }
    ]
  }
}
📋Copy
📜 Step 7: Get Usage History
View detailed transaction history showing all purchases and consumption.

GET
/api/v1/product-usages/{customer_id}/{product_id}/history
Get Usage History
▼
Retrieve complete audit trail of purchases and usage

Success Response 200 OK
{
  "success": true,
  "message": "Usage history retrieved successfully",
  "data": {
    "customer_id": 45,
    "product_id": 12,
    "customer_name": "Tech Startup Inc",
    "product_name": "SMS Credits",
    "total_purchased": 1000,
    "total_used": 150,
    "balance": 850,
    "purchases": [
      {
        "id": 101,
        "quantity": 1000,
        "created_at": "2026-03-01T10:00:00.000000Z"
      }
    ],
    "usages": [
      {
        "id": 789,
        "quantity": 150,
        "created_at": "2026-03-15T14:30:00.000000Z"
      }
    ]
  }
}
📋Copy
✅ Complete Workflow Example
# 1. Create usage product (product_type_id = 3)
POST /api/v1/products

# 2. Customer buys 1000 SMS credits (generates wallet_id/UCN)
POST /api/v1/invoices
Response includes: {"control_numbers": [{"reference": "UCN1234567890", ...}]}

# 3. Customer sends 150 SMS using their wallet
POST /api/v1/product-usages
{"wallet_id": "UCN1234567890", "quantity": 150}

# 4. Check remaining balance (1000 - 150 = 850)
GET /api/v1/product-usages/UCN1234567890/balance

# 5. Generate invoice for accumulated usage (if post-paid model)
POST /api/v1/invoices
📌 Key Concepts
Price Plan → Product Mapping: Each price_plan_id is linked to ONE specific product
price_plan_id 15 → SMS Credits (rate: 50 TZS/SMS)
price_plan_id 17 → API Calls (rate: 10 TZS/call)
price_plan_id 19 → Cloud Storage (rate: 100 TZS/GB)
Automatic Quantity Calculation: quantity = amount ÷ rate
Customer pays 50,000 TZS for SMS (rate: 50 TZS/SMS)
System calculates: 50,000 ÷ 50 = 1,000 SMS credits
ProductPurchase record created with quantity: 1000
ProductPurchase: Credits added to wallet (created after invoice payment)
ProductUsage: Credits consumed from wallet (created when service is used)
Balance Formula: Sum(ProductPurchase.quantity) - Sum(ProductUsage.quantity)
Billing Models:
Pre-paid: Customer buys credits first (invoice → payment → ProductPurchase), then uses them (ProductUsage)
Post-paid: Record usage first (ProductUsage), then invoice at end of billing period