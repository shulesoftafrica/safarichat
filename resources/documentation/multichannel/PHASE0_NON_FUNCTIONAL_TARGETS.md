# Multi-Channel Phase 0: Non-Functional Targets

Status: APPROVED TARGETS FOR PHASE 1-2 IMPLEMENTATION
Date: 2026-07-14

## 1. Reliability

- Target send success rate (accepted by transport): >= 99.5%
- End-to-end delivery attempt completion: >= 99.0%
- Retry policy must be deterministic and auditable

## 2. Performance

- P95 orchestration decision latency: <= 150 ms
- P95 payload build latency: <= 60 ms
- P95 transport request initiation latency: <= 200 ms (excluding provider delivery time)

## 3. Observability

Every outbound send must record:
- selected channel
- selection reason code
- fallback chain (if any)
- transport response code/body summary
- correlation id (if available)

## 4. Security and Compliance

- Do not log full message body for sensitive categories where policy forbids it
- Mask PII in error logs where practical
- Respect contact opt-out and do-not-contact settings before dispatch

## 5. Backward Compatibility

- WhatsApp-only tenants must continue working without any configuration change
- Feature flag `multi_channel_routing` must default to OFF until pilot sign-off

## 6. Operational Safety

- Channel-level circuit breaker or temporary disable switch must be available
- Bulk sends must support rate limiting and queue backpressure

## 7. Test Minimums Before Pilot

- Unit tests for channel selection and payload contract
- Integration tests for all four channels through the unified endpoint
- Regression tests for existing WhatsApp flows
