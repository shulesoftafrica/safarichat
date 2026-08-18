## Plan: Multi-Channel Sales Engagement Design

Introduce a channel orchestration layer so contact engagement is no longer WhatsApp-only, while reusing the existing queue/jobs/messaging pipeline. The recommended approach is: business-level shared channel catalog + AI-driven channel selection policy + per-contact preferences/eligibility + per-send audit trail. Bulk-SMS will be modeled as a distinct channel type (per your decision), not just an SMS campaign mode.

**Steps**
1. Phase 1: Channel Domain Model (foundation)
2. Create `channels` (business-owned channel definitions) with CRUD support and soft-delete to allow add/edit/delete from UI without breaking historical sends.
3. Create `channel_capabilities` or JSON capability schema on channel records (supports `supports_bulk`, `supports_rich_media`, `requires_template`, `max_length`, `formal_score`) so "new channel" remains future-proof. *parallel with step 4*
4. Create `contact_channel_preferences` for per-contact preference and eligibility (`is_preferred`, `is_allowed`, `priority_rank`, `opt_out_at`, optional `formal_only`). *parallel with step 3*
5. Create `lead_channel_metrics` or `contact_channel_metrics` (response rate, conversion rate, last success/failure, avg response time) for score-based selection. *depends on 4*
6. Extend outbound message records (`outgoing_messages` + queue metadata) to persist selected channel, channel reason, and fallback chain for observability. *depends on 2*
7. Phase 2: Channel Management UI + Agent Setup Integration
8. Add a channel management section in AI setup (modal CRUD: add/edit/delete channel) and surface channel assignment matrix (enabled channels per agent). *depends on 2*
9. Add contact-level channel preferences in add/edit contact flow (optional section) and expose from contact detail panel. *depends on 4*
10. Add validation guards (cannot delete channel in active use; cannot disable all channels for active agent/contact).
11. Phase 3: Best-Channel Selection Engine
12. Implement `ChannelSelectionService` that selects channel by weighted score: `eligibility -> contact opt-in/opt-out -> product/channel policy -> business hours -> historical success -> formality requirement -> cost guard`.
13. Add policy tables for product restrictions and corporate rules (example: product X disallows WhatsApp, requires Email or Phone-SMS). *parallel with step 12*
14. Define fallback strategy per attempt: Primary channel -> secondary channel -> tertiary channel, with cooldown windows and failure reasons.
15. Ensure message formatter strategies per channel (`EmailFormatter`, `WhatsAppFormatter`, `PhoneSmsFormatter`, `BulkSmsFormatter`) with channel-specific length/tone/link rules.
16. Phase 4: Dispatch Orchestration and Existing Pipeline Reuse
17. Introduce `OutboundOrchestratorService` as the single entry point for all outbound sends (campaigns, followups, win-back, manual sends). *depends on 12,14,15*
18. Route each send to a channel adapter (`WhatsAppAdapter`, `EmailAdapter`, `PhoneSmsAdapter`, `BulkSmsAdapter`) that reuses existing implementations where available.
19. Keep existing WhatsApp jobs/services intact, but invoke them through orchestrator decisions to avoid regressions.
20. Add queue partitioning per channel (`messages_whatsapp`, `messages_email`, `messages_sms`, `messages_bulk_sms`) with retry policy per adapter.
21. Phase 5: Governance, Analytics, and Rollout
22. Add dashboards/reporting for channel effectiveness per business, product, and agent: sent/delivered/replied/converted by channel.
23. Add feature flag rollout (`multi_channel_routing`) default OFF, pilot with selected businesses, then expand.
24. Add migration scripts/backfill defaults: existing businesses get WhatsApp channel auto-created and default preference rank.

**Relevant files**
- `app/Http/Controllers/AiSalesAgentController.php` — extend validation/store/update to include channel assignments in setup flow.
- `resources/views/service/job-description.blade.php` — add channel setup and modal launch controls in AI setup UI.
- `resources/views/service/ai-agents/index.blade.php` — add channel management entry and summary status badges.
- `app/Models/AiSalesAgent.php` — add relationships for assigned channels and channel policies.
- `app/Models/BusinessContact.php` — add relationships to contact channel preferences/metrics.
- `app/Http/Controllers/Guest.php` — add optional contact-level channel preferences on add/edit customer.
- `app/Services/AiWhatsAppService.php` — route outbound engagement sends through new orchestrator (instead of direct channel send).
- `app/Services/SmartFollowupService.php` — use orchestrator + channel selection for followups.
- `app/Jobs/SendWhatsAppMessage.php` — keep as WhatsApp adapter backend target.
- `app/Services/WaSenderService.php` — keep WhatsApp transport implementation.
- `app/Models/OutgoingMessage.php` — extend with selected-channel and selection-reason metadata.
- `app/Models/MessageQueue.php` — persist channel decision and fallback metadata for campaigns.
- `app/Http/Controllers/Message.php` — unify dispatch entry to orchestrator for manual/bulk send paths.
- `routes/web.php` — add channel management routes for setup modals and CRUD.
- `routes/api.php` — add optional API routes for channel preferences/selection previews.
- `database/migrations` — add migrations for `channels`, `contact_channel_preferences`, `channel_product_policies`, and message metadata extensions.

**Verification**
1. Unit tests: `ChannelSelectionService` scoring and deterministic fallback outcomes for 12+ scenarios.
2. Integration tests: add/edit/delete channel from setup UI, including validation and persistence.
3. End-to-end tests: create contact with preferred Email, send outreach, verify Email adapter selected and metadata stored.
4. Regression tests: WhatsApp-only tenant still sends via current pipeline when only WhatsApp is enabled.
5. Policy tests: product marked "no WhatsApp" always routes to allowed channels.
6. Failure tests: simulate provider failure and verify fallback channel dispatch with cooldown and audit reason.
7. Reporting tests: channel effectiveness counters update from outbound + inbound outcomes.

**Decisions**
- Scope: `channels` managed per business (shared pool).
- Selection mode: auto by rules + engagement score.
- Bulk-SMS: modeled as a separate channel type.
- Included: channel CRUD in setup, auto-selection, formatter strategy, fallback, analytics.
- Excluded (phase 1): voice call automation and external CRM-specific per-channel sync.

**Further Considerations**
1. Channel reason transparency for agents: show “why this channel was chosen” on each outbound message for trust and override.
2. Compliance model: add business-level defaults for consent requirements per channel to prevent accidental policy violations.
3. Cost controls: incorporate per-channel cost budget into selection scoring to avoid expensive channel overuse.
