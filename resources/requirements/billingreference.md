## Get subscription status

Request:

`` {
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
} ``

Results:
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
          "expires_at": "2026-03-05T11:15:00.000000Z",
          "instructions": "Click the payment link to pay via card, mobile money, or bank transfer"
        },
           "stripe": {
          "payment_link": "https://checkout.stripe.com/v3/hosted/pay/abc123xyz",
          "tx_ref": "INV-2026-00124-1708956234",
          "expires_at": "2026-03-05T11:15:00.000000Z",
          "instructions": "Click the payment link to pay via card"
        },
        "control_number": {
        "reference": "9912345678",
        "amount": 295000,
        "currency": "TZS",
        "expires_at": "2026-03-05T10:30:34.000000Z",
        "payment_instructions": {
          "mobile_banking": "Dial *150*01*9912345678# from your registered mobile number",
          "internet_banking": "Login to your internet banking and pay bill using control number",
          "agent_banking": "Visit any bank agent and provide the control number"
        }
      }
      },
       "urls": {
      "success_url": "https://yourapp.com/payment/success",
      "cancel_url": "https://yourapp.com/payment/cancel"
    }
    }
  }
}


   ## upgrade subscription
   curl -X POST "https://your-domain.com/api/invoices/plan-upgrade" \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "subscription_id": 89,
    "new_price_plan_id": 15,
    "payment_gateway": "flutterwave"
  }'

  Success Results
{
  "success": true,
  "message": "Subscription upgraded successfully",
  "data": {
    "invoice": {
      "id": 250,
      "invoice_number": "INV-2026-00250",
      "status": "issued",
      "currency": "TZS",
      "description": null,
      "subtotal": 30484,
      "tax_breakdown": [],
      "tax_total": 0,
      "grand_total": 30484,
      "invoiced_amount": 30484,
      "paid_amount": 0,
      "outstanding_amount": 30484,
      "date": null,
      "due_date": null,
      "issued_at": "2026-01-25T10:30:00.000000Z",
      "created_at": "2026-01-25T10:30:00.000000Z",
      "updated_at": "2026-01-25T10:30:00.000000Z",
      "customer": {
        "id": 45,
        "name": "Jane Smith",
        "email": "jane@company.com",
        "phone": "+255723456789",
        "organization_id": 1
      },
      "price_plans": [
        {
          "id": 15,
          "name": "Standard Plan",
          "subscription_type": null,
          "quantity": 1,
          "unit_price": 30484,
          "amount": 30484,
          "product_id": 3,
          "product_name": "Cloud Hosting Premium",
          "payment_gateways": [
            {
              "id": 5,
              "payment_gateway_id": 2,
              "gateway_name": "Flutterwave",
              "status": "active",
              "references": "https://checkout.flutterwave.com/v3/hosted/pay/abc123xyz789"
            },
            {
              "id": 8,
              "payment_gateway_id": 1,
              "gateway_name": "Universal Control Number",
              "status": "active",
              "references": "992001234567890"
            }
          ]
        }
      ],
      "subscriptions": [
        {
          "id": 89,
          "product_id": 3,
          "product_name": "Cloud Hosting Premium",
          "price_plan_id": 15,
          "price_plan_name": "Standard Plan",
          "subscription_type": null,
          "customer_id": 45,
          "status": "active",
          "start_date": "2026-01-15",
          "end_date": null,
          "next_billing_date": "2026-02-15",
          "created_at": "2026-01-15T08:00:00.000000Z",
          "updated_at": "2026-01-25T10:30:00.000000Z"
        }
      ]
    },
    "subscription": {
      "id": 89,
      "status": "active",
      "previous_plan_id": 8,
      "current_plan": {
        "id": 15,
        "name": "Standard Plan",
        "amount": 75000,
        "billing_interval": "monthly"
      },
      "next_billing_date": "2026-02-15"
    },
    "proration": {
      "amount_charged": 30484,
      "credit_applied": 20323,
      "description": "Prorated for remaining billing cycle"
    }
  }
}

  ## downgrade subscription
  curl -X POST "https://your-domain.com/api/invoices/plan-downgrade" \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "subscription_id": 89,
    "new_price_plan_id": 8,
    "apply_credit": true
  }'

  result response
  {
  "success": true,
  "message": "Subscription downgraded successfully",
  "data": {
    "subscription": {
      "id": 89,
      "status": "active",
      "previous_plan_id": 15,
      "current_plan": {
        "id": 8,
        "name": "Basic Plan",
        "amount": 30000,
        "billing_interval": "monthly"
      },
      "next_billing_date": "2026-02-15"
    },
    "credit": {
      "credit_amount": 30484,
      "credit_applied": true,
      "days_remaining": 21,
      "description": "Credit from unused portion of higher plan"
    },
    "payment_details": {
      "available_gateways": [
        {
          "id": 5,
          "payment_gateway_id": 2,
          "gateway_name": "Flutterwave",
          "status": "active"
        },
        {
          "id": 8,
          "payment_gateway_id": 1,
          "gateway_name": "Universal Control Number",
          "status": "active"
        }
      ],
      "note": "No payment required for downgrade. These payment methods will be available for your next billing cycle on 2026-02-15"
    }
  }
}


## create wallet type product
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

# result success
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


## create wallet invoice
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

# result
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