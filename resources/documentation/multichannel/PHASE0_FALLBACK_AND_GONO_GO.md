# Multi-Channel Phase 0: Fallback Policy and Go/No-Go Checklist

Status: FROZEN FOR IMPLEMENTATION
Date: 2026-07-14

## 1. Fallback Policy (v1)

Primary objective: maximize successful contact while respecting policy and customer preference.

### 1.1 Fallback Order

Default fallback order (unless overridden by product or contact policy):
1. selected primary channel
2. next best eligible channel
3. final eligible channel

### 1.2 Fallback Preconditions

Fallback is allowed only when:
- transport/provider response indicates retryable failure
- contact is eligible for fallback channel
- product policy allows fallback channel
- cooldown window has elapsed

### 1.3 No-Fallback Cases

Do not fallback when:
- contact has opted out
- failure is policy/compliance failure
- message is marked channel-locked by business rule

### 1.4 Cooldown Defaults

- immediate technical failure: retry same channel according to retry policy first
- first cross-channel fallback: after retry policy exhausted
- cross-channel fallback attempts: spaced by configured cooldown window

## 2. Go/No-Go Checklist for Phase 0 Completion

### 2.1 Contract

- [ ] Canonical channel keys approved (`whatsapp`, `email`, `phone_sms`, `bulk_sms`)
- [ ] Shared envelope fields approved
- [ ] Channel-specific field requirements approved
- [ ] Notifications endpoint confirmed for all channels

### 2.2 Safety

- [ ] Opt-out and DNC checks are mandatory pre-send requirements
- [ ] Logging policy for PII and message body approved
- [ ] Backward compatibility strategy approved

### 2.3 Delivery Readiness

- [ ] Feature flag strategy approved (`multi_channel_routing`)
- [ ] Fallback policy approved
- [ ] Pilot tenants identified

### 2.4 Exit Criteria

Phase 0 is complete only when all above checklist items are marked done and signed off by Product + Engineering.
