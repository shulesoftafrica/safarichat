# Billing Webhook Integration Guide

## Overview
This document describes the webhook integration between SafariChat and the external billing platform. The webhook receives payment notifications and automatically updates subscription details in the `billing_accounts` table.

## Webhook Endpoint
```
POST https://yourdomain.com/api/billing/webhook
```

## Security
The webhook validates incoming requests using HMAC-SHA256 signature verification.

### Configuration
Add the webhook secret to your `.env` file:
```env
BILLING_WEBHOOK_SECRET=your_secure_random_secret_here
```

### Signature Validation
The billing platform must send the signature in the `X-Webhook-Signature` header:
```
X-Webhook-Signature: <hmac_sha256_hash>
```

Signature is computed as:
```php
$signature = hash_hmac('sha256', $payload, $secret);
```

## Webhook Events

### 1. Payment Success (`payment.success`)
Triggered when a payment is successfully processed.

**Payload:**
```json
{
  "event": "payment.success",
  "customer_id": 45,
  "business_id": 4,
  "payment": {
    "transaction_id": "TXN20260123001",
    "amount": 69000,
    "currency": "TZS",
    "status": "completed",
    "payment_method": "mobile_money",
    "reference": "PSP12345"
  },
  "subscription": {
    "plan": "starter",
    "duration_days": 30,
    "ai_credits": 69000,
    "features": {
      "max_contacts": 50,
      "max_products": 5,
      "whatsapp_channels": 1,
      "customer_followups": false,
      "customer_categorization": false,
      "booking_calendars": false,
      "sales_reports": false
    }
  },
  "timestamp": "2026-01-23T10:30:00Z"
}
```

**Actions:**
- Sets `subscription_status` to `active`
- Updates `subscription_plan` to the new plan
- Sets `subscription_started_at` to current timestamp
- Calculates and sets `subscription_expires_at` (now + duration_days)
- Adds AI credits to account balance
- Updates feature limits (contacts, products, channels)
- Updates feature flags (followups, categorization, calendars, reports)
- Records payment details (`last_payment_at`, `last_payment_amount`, `last_transaction_id`)

---

### 2. Payment Failed (`payment.failed`)
Triggered when a payment attempt fails.

**Payload:**
```json
{
  "event": "payment.failed",
  "customer_id": 45,
  "business_id": 4,
  "payment": {
    "transaction_id": "TXN20260123002",
    "amount": 69000,
    "currency": "TZS",
    "status": "failed",
    "error_code": "insufficient_funds",
    "error_message": "Payment declined by bank"
  },
  "timestamp": "2026-01-23T11:00:00Z"
}
```

**Actions:**
- Logs the payment failure
- Does NOT change subscription status (allows retry)

---

### 3. Subscription Created (`subscription.created`)
Triggered when a new subscription is created (same as payment.success).

**Payload:** Same as `payment.success`

**Actions:** Same as `payment.success`

---

### 4. Subscription Renewed (`subscription.renewed`)
Triggered when an existing subscription is renewed.

**Payload:**
```json
{
  "event": "subscription.renewed",
  "customer_id": 45,
  "business_id": 4,
  "subscription": {
    "plan": "pro",
    "duration_days": 30,
    "ai_credits": 149000
  },
  "timestamp": "2026-02-23T10:30:00Z"
}
```

**Actions:**
- Sets `subscription_status` to `active`
- Extends `subscription_expires_at` from current expiry date (or now if expired)
- Adds AI credits to account balance
- Updates `last_payment_at`

---

### 5. Subscription Cancelled (`subscription.cancelled`)
Triggered when a subscription is cancelled by user or admin.

**Payload:**
```json
{
  "event": "subscription.cancelled",
  "customer_id": 45,
  "business_id": 4,
  "reason": "User requested cancellation",
  "timestamp": "2026-01-23T15:00:00Z"
}
```

**Actions:**
- Sets `subscription_status` to `cancelled`
- Subscription remains active until `subscription_expires_at` date

---

### 6. Subscription Expired (`subscription.expired`)
Triggered when a subscription reaches its expiration date.

**Payload:**
```json
{
  "event": "subscription.expired",
  "customer_id": 45,
  "business_id": 4,
  "expired_plan": "starter",
  "expired_at": "2026-02-23T00:00:00Z",
  "timestamp": "2026-02-23T00:01:00Z"
}
```

**Actions:**
- Sets `subscription_status` to `expired`
- User loses access to paid features

---

### 7. Credits Purchased (`credits.purchased`)
Triggered when user purchases standalone AI credits without subscription.

**Payload:**
```json
{
  "event": "credits.purchased",
  "customer_id": 45,
  "business_id": 4,
  "payment": {
    "transaction_id": "TXN20260123003",
    "amount": 10000,
    "currency": "TZS",
    "status": "completed"
  },
  "credits": 10000,
  "timestamp": "2026-01-23T12:00:00Z"
}
```

**Actions:**
- Adds credits to `ai_credits` balance
- Records payment details
- Does NOT affect subscription status or expiration

---

## Database Updates

All webhook events update the `billing_accounts` table:

### Key Fields Updated:
- `subscription_status`: active, cancelled, expired, trial, inactive
- `subscription_plan`: trial, starter, pro, premium
- `subscription_started_at`: Timestamp when current subscription started
- `subscription_expires_at`: Expiration date of current subscription
- `ai_credits`: Current AI credits balance
- `max_contacts`: Maximum number of contacts allowed
- `max_products`: Maximum number of products allowed
- `whatsapp_channels`: Number of WhatsApp channels allowed
- `customer_followups`: Boolean - Customer followup feature enabled
- `customer_categorization`: Boolean - Customer categorization enabled
- `booking_calendars`: Boolean - Booking calendar feature enabled
- `sales_reports`: Boolean - Sales reports feature enabled
- `last_payment_at`: Timestamp of last successful payment
- `last_payment_amount`: Amount of last payment
- `last_transaction_id`: Transaction ID of last payment

---

## Testing the Webhook

### Using cURL

```bash
curl -X POST https://yourdomain.com/api/billing/webhook \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: YOUR_SIGNATURE_HERE" \
  -d '{
    "event": "payment.success",
    "customer_id": 45,
    "business_id": 4,
    "payment": {
      "transaction_id": "TEST123",
      "amount": 69000,
      "currency": "TZS",
      "status": "completed"
    },
    "subscription": {
      "plan": "starter",
      "duration_days": 30,
      "ai_credits": 69000,
      "features": {
        "max_contacts": 50,
        "max_products": 5,
        "whatsapp_channels": 1,
        "customer_followups": false,
        "customer_categorization": false,
        "booking_calendars": false,
        "sales_reports": false
      }
    },
    "timestamp": "2026-01-23T10:30:00Z"
  }'
```

### Computing Signature in PHP

```php
$payload = json_encode($webhookData);
$secret = config('services.billing.webhook_secret');
$signature = hash_hmac('sha256', $payload, $secret);

// Include in request header
$headers = [
    'Content-Type: application/json',
    'X-Webhook-Signature: ' . $signature
];
```

### Computing Signature in Python

```python
import hmac
import hashlib
import json

payload = json.dumps(webhook_data)
secret = "your_webhook_secret"
signature = hmac.new(
    secret.encode('utf-8'),
    payload.encode('utf-8'),
    hashlib.sha256
).hexdigest()

headers = {
    'Content-Type': 'application/json',
    'X-Webhook-Signature': signature
}
```

---

## Error Handling

### Webhook Responses

**Success:**
```json
{
  "success": true,
  "message": "Payment processed successfully",
  "billing_account_id": 12,
  "subscription": {
    "plan": "starter",
    "status": "active",
    "expires_at": "2026-02-23T10:30:00Z"
  }
}
```

**Invalid Signature:**
```json
{
  "success": false,
  "error": "Invalid signature"
}
```
HTTP Status: 401

**Invalid Payload:**
```json
{
  "success": false,
  "error": "Invalid payload",
  "details": [
    "Missing event type",
    "Missing customer_id or business_id"
  ]
}
```
HTTP Status: 400

**Server Error:**
```json
{
  "success": false,
  "error": "Internal server error",
  "message": "Database connection failed"
}
```
HTTP Status: 500

---

## Logging

All webhook events are logged to `storage/logs/laravel.log`:

```
[2026-01-23 10:30:15] local.INFO: Billing webhook received {"payload": {...}, "ip": "192.168.1.100"}
[2026-01-23 10:30:16] local.INFO: Payment success processed {"billing_account_id": 12, "customer_id": 45, "plan": "starter", ...}
```

Failed webhooks:
```
[2026-01-23 10:30:20] local.ERROR: Invalid webhook signature {"ip": "192.168.1.200", "signature": "invalid_hash"}
[2026-01-23 10:30:25] local.ERROR: Webhook processing error {"error": "Database error", "trace": "..."}
```

---

## Monitoring & Alerts

### Recommended Monitoring

1. **Failed Webhooks**: Monitor for 401/400/500 responses
2. **Processing Time**: Alert if webhook takes > 5 seconds
3. **Invalid Signatures**: Alert on repeated invalid signature attempts (security)
4. **Database Failures**: Alert if billing_accounts update fails
5. **Missing Events**: Compare payment count in billing platform vs received webhooks

### Health Check Endpoint

You can verify webhook endpoint is accessible:
```bash
curl -I https://yourdomain.com/api/billing/webhook
```

Should return: `405 Method Not Allowed` (POST required)

---

## Billing Platform Configuration

Configure the webhook URL in your billing platform:

**Webhook URL:** `https://yourdomain.com/api/billing/webhook`  
**Secret Key:** (from `BILLING_WEBHOOK_SECRET` env variable)  
**Events to Send:**
- payment.success
- payment.failed
- subscription.created
- subscription.renewed
- subscription.cancelled
- subscription.expired
- credits.purchased

**Retry Policy:** Recommended 3 retries with exponential backoff (1s, 5s, 15s)

---

## Security Best Practices

1. ✅ **Always validate signature** - Never process unsigned webhooks
2. ✅ **Use HTTPS only** - Webhook URL must use SSL/TLS
3. ✅ **Verify timestamp** - Reject webhooks older than 5 minutes (prevents replay attacks)
4. ✅ **IP Whitelist** - If billing platform has static IPs, whitelist them
5. ✅ **Rate Limiting** - Protect against webhook flooding
6. ✅ **Idempotency** - Use `transaction_id` to prevent duplicate processing
7. ✅ **Database Transactions** - Ensure atomic updates to billing_accounts

---

## Troubleshooting

### Webhook Not Received
- Check billing platform webhook configuration
- Verify webhook URL is accessible from internet
- Check firewall/security group settings
- Review `storage/logs/laravel.log` for errors

### Invalid Signature Error
- Verify `BILLING_WEBHOOK_SECRET` matches billing platform configuration
- Check payload is not modified in transit
- Ensure signature is computed from raw request body (not parsed JSON)

### Database Not Updating
- Check if user/business exists in database
- Verify billing_accounts table has correct structure
- Review database logs for constraint violations
- Check `subscription_expires_at` field is nullable

### Credits Not Added
- Verify `ai_credits` field type is integer
- Check for database transaction rollbacks
- Ensure `DB::raw("ai_credits + X")` syntax is supported

---

## Support

For webhook integration issues:
- Email: support@safarichat.africa
- Documentation: https://docs.safarichat.africa/billing-webhooks
- Status Page: https://status.safarichat.africa
