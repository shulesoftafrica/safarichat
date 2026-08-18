# Multi-Channel Phase 0: Transport Contract (Frozen v1)

Status: FROZEN FOR IMPLEMENTATION
Date: 2026-07-14
Owner: Sales Platform Team

## 1. Canonical Channels

The platform supports these canonical channel keys:
- `whatsapp`
- `email`
- `phone_sms`
- `bulk_sms`

These exact keys must be used across UI, DB, queue metadata, and API payload generation.

## 2. Single Transport Endpoint

All outbound channel sends must use the same endpoint:
- `https://notifications.shulesoft.africa/`

No channel should bypass this endpoint in the new orchestration path.

## 3. Shared Payload Envelope

All channel payloads must include these fields:
- `schema_name` (string, required)
- `channel` (string, required; one of canonical channels)
- `to` (string, required)
- `message` (string, required)
- `provider` (string, required)
- `priority` (string, required; one of `low`, `normal`, `high`)

## 4. Channel-Specific Fields

- `email`
  - required: `subject`
- `whatsapp`
  - optional: template/media metadata when needed by provider
- `phone_sms`
  - optional: SMS provider metadata when needed
- `bulk_sms`
  - optional: bulk campaign metadata when needed

Rule: Channel-specific fields extend the shared envelope; they do not replace it.

## 5. Example Payloads

### 5.1 Email
```json
{
  "schema_name": "my_app",
  "channel": "email",
  "to": "customer@example.com",
  "subject": "Welcome to Our Service!",
  "message": "Thank you for signing up!",
  "provider": "sendgrid",
  "priority": "high"
}
```

### 5.2 WhatsApp
```json
{
  "schema_name": "my_app",
  "channel": "whatsapp",
  "to": "255689353642",
  "message": "Hello, your quote is ready.",
  "provider": "wa_sender",
  "priority": "normal"
}
```

### 5.3 Phone SMS
```json
{
  "schema_name": "my_app",
  "channel": "phone_sms",
  "to": "255689353642",
  "message": "Your verification code is 442991",
  "provider": "internal_sms_api",
  "priority": "high"
}
```

### 5.4 Bulk SMS
```json
{
  "schema_name": "my_app",
  "channel": "bulk_sms",
  "to": "255689353642",
  "message": "Promo: 10% off all annual plans this week.",
  "provider": "internal_sms_api",
  "priority": "normal"
}
```

## 6. Payload Builder Rule

The selector chooses channel. The formatter builds channel payload fields. The sender posts the final payload to the single transport endpoint.

## 7. Compatibility Rule

Legacy WhatsApp send paths may remain during rollout, but all new orchestrated sends must conform to this contract.
