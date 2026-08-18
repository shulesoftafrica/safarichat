# SafariChat / ShuleSoft — Sales Engine Stress-Test & Improvement Plan

*Prepared as a SaaS sales-systems teardown. Analysis only — no code changes.*

---

## Part A — How the platform actually sells today (as-built)

I traced the full outbound path. Here is the real machine, not the marketing version.

### A.1 The lead → outreach → follow-up loop

| Stage | Code | What it does | Trigger |
|-------|------|--------------|---------|
| Contact → Lead | `ConvertUnengagedContactsCommand` | Turns untouched `business_contacts` into `leads` attached to an `AiSalesAgent`. | Cron |
| First-touch outreach | `DailyOutreachCommand` → `OpenAiService::generateResponse()` | Picks `NEW`/`OUTREACHED` leads, generates one message, sends on WhatsApp. Dedupe: skip phones contacted in last 7 days, per-lead 1/day. | Cron |
| Follow-up | `SmartFollowupService::processSmartFollowups()` | Leads not closed, no interaction ≥3 days, last follow-up ≥7 days. Builds message from a **fixed template ladder**, sends via `OutboundOrchestratorService`. | Cron |
| Re-engagement | `WinBackOutreachCommand`, `NurtureMessageService` | "Value nugget" library; reframes pushy follow-ups into value-first. | Cron / on ghosting |
| Inbound reply | `ProcessIncomingMessage` → `AiWhatsAppService` → `OpenAiService` RAG | Answers using embeddings over uploaded product docs; deducts AI credits. | Webhook |

### A.2 How a follow-up message is chosen (the core of the problem)

`SmartFollowupService::buildContextualMessage()` is a **keyword-matched template selector**, not a strategist. It:

1. Reads the customer's **last message** and keyword-matches it (`extractConcerns`, `extractInterests` — hardcoded English word lists like `price`, `demo`, `integration`).
2. Picks one of ~8 templates: `first_contact`, `address_price_concern`, `address_time_concern`, `follow_interest`, `thinking_response`, `timing_response`, `high_intent`, `personalized_default`.
3. String-replaces `{name}`, `{product}`, `{days}`.

**The failure mode in your screenshot is deterministic, not random.** When a prospect never replies (the common case), there *is* no last customer message → `concerns`/`interests` are empty → the ladder collapses to `first_contact` (once) then `high_intent` / `personalized_default` forever. Every touch is "here is ShuleSoft, want a demo?" in a slightly different sentence. The engine has **no memory of which angle it already tried** and **no inventory of other angles to try.**

### A.3 What the AI knows how to sell

- Outreach context carries **one** `primary_product` per lead. There is no concept of ShuleSoft's **module portfolio** (admission, fee collection, Mkombozi/CRDB integration, UCN, bank reconciliation, budgeting, payroll, HR, recruitment) as **distinct pitchable value propositions**.
- Product knowledge for *inbound* replies comes from RAG over uploaded docs — good for answering questions, useless for *deciding which module to lead with* on outbound.
- Result: the system sells **"ShuleSoft" as one undifferentiated blob**, exactly as you observed. It cannot say "they ignored fee-collection — let me try the HR/payroll angle."

### A.4 Channels

- A genuinely **well-built multi-channel layer already exists** but is **switched off**: `config/multi_channel.php` → `enabled = false`.
  - `ChannelSelectionService` scores channels (whatsapp / email / phone_sms / bulk_sms) by business priority, contact preference, product policy, historical `response_rate`/`conversion_rate`, formality, business hours, and cost.
  - `OutboundOrchestratorService` builds a `fallback_chain` and writes rich telemetry columns on `outgoing_messages` (`selected_channel`, `channel_selection_reason`, `fallback_chain`, `channel_attempt`).
  - Transport is a unified external API (`notifications.shulesoft.africa`).
- **Two critical limitations even when enabled:**
  1. **Fallback fires only when a recipient *address* is missing** (`resolveRecipientForSelection` walks the chain until it finds a non-empty phone/email). It does **not** fall back because a channel *failed to get a response*. There is no "3 WhatsApp touches, no read → escalate to SMS → then email → then a call task" loop.
  2. Channel choice is computed **per single send**, not as a **cross-channel cadence** with cooldowns and attempt budgets (the `max_cross_channel_attempts` / `cooldown_minutes` config exists but nothing consumes it as an escalation policy).

### A.5 Engagement signals

- Cadence keys off `last_interaction_at` / `follow_up_sent_at` timestamps only. There is **no branching on delivered vs. read vs. replied**. Your screenshot shows ✓✓ (delivered, not read) — the system treats that identically to a read-and-ignored message. "Undelivered number" and "read but silent" get the same generic next touch.

---

## Part B — Stress test: where this breaks at scale

Rated by revenue impact.

1. **🔴 Angle blindness (highest impact).** One product per lead, no module value-prop matrix, no memory of tried angles → repetition → prospects mentally filter you as spam by touch #3. You are burning your most expensive asset (a warm-ish contact) with your weakest asset (a generic template).

2. **🔴 No disengagement state machine.** A prospect who ignores 4 WhatsApp messages, a prospect who is undelivered, and a prospect who replied "not now" are all handled by the same 7-day timer. No branching = no intelligence.

3. **🔴 Single channel in practice.** Multi-channel is dark. When WhatsApp is unread, there is no SMS/email/call escalation — the one lever that most reliably recovers non-responders in the TZ market (SMS + UCN payment context is a strong local pattern) is unused.

4. **🟠 No experimentation or attribution.** No A/B of angles/subject lines/channels; `ChannelMetricsService` tracks channel performance but nothing tracks **which message angle** or **which module pitch** converts. The system cannot learn.

5. **🟠 Brittle personalization.** Keyword lists are English-first; a Swahili "bei ghali" (too expensive) won't trip the `price` branch. Intent detection is lexical, not semantic.

6. **🟠 No lead prioritization economics.** `lead_score` exists but drives only ordering. No tiering of *effort* (human handoff for hot, cheap bulk-SMS nurture for cold), so credits and attention are spread evenly instead of concentrated where they convert.

7. **🟡 No frequency/reputation governance across channels.** Per-channel dedupe exists, but no global contact-level pressure cap (total touches/week across all channels) → WhatsApp-ban and spam-complaint risk as volume grows.

8. **🟡 Human-in-the-loop is thin.** Escalation triggers exist on the agent model but there's no "hot lead → notify rep → book the demo" conversion bridge; the AI is expected to close alone.

9. **🟡 No objection/outcome capture loop.** Replies aren't classified into a reusable taxonomy (price / timing / competitor / not-decision-maker / already-have-system) that would feed both the next angle and the product team.

---

## Part C — The plan: a Next-Best-Action sales engine

Reframe the goal: stop asking *"what's the next message?"* and start asking **"for THIS prospect, what is the next best (module × angle × channel × timing × who-sends-it)?"** Five building blocks.

### C1. Module & Value-Proposition Catalog (fixes angle blindness)

**Where these features/modules are DEFINED — this is not a new table, it is the existing `products` table.**
The `Product` model is already a full "offer" entity, not just shop inventory. Each ShuleSoft
module (Exam, Fee Collection, UCN Payments, Bank Reconciliation, Budgeting, Payroll, HR,
Recruitment, Accounting Pro) is modeled as **one `Product`/offer row**. Columns already present:

| Concept | Existing `products` column |
|---------|----------------------------|
| Module identity | `name`, `category` |
| Opening angle / hook | `campaign_hook_text` |
| Pain the module removes | `campaign_pain_point` |
| What to pitch | `key_features[]`, `selling_points[]`, `ai_description` |
| Pre-loaded rebuttals | `common_objections[]` |
| Persona / vertical targeting | `target_industry` |
| **Next module to rotate to** | `upsell_products[]` |
| CTA type | `requires_demo`, `has_trial`, `trial_days` |
| "Launch of the day" | `is_active_campaign` + `setAsActiveCampaign()` |
| Deep knowledge for replies | `documentVectors()` (RAG) |
| Value nuggets per module | `nurtureMessages()` → `NurtureLibrary` |
| Per-module performance | `getConversionRate()` |

**Launching a new module/feature tomorrow = a data operation, no code deploy:** create/enable a
`Product` row, fill hook + pain + `key_features` + `common_objections` + `target_industry`, attach a
doc for RAG, and optionally `setAsActiveCampaign()`. The AI reads these fields at send time.

**What is actually missing (the real cause of the repetition) — three bindings, not the catalog:**

1. **Per-lead offer ledger.** `lead_products` currently links a lead to products but is NOT an
   attempt log. Angle-rotation state must be defined here: each `(lead, offer)` needs
   `status` (`pitched / no_reply / interested / rejected`), `touch_count`, `last_pitched_at`.
   Without this the engine cannot know "Exam module was tried and ignored → pitch Accounting Pro next."
2. **Rotation policy** = persona/industry → ordered module sequence. Ingredients already exist:
   `target_industry` (who) + `upsell_products[]` (what comes next — reuse this as the chain).
3. **Selector step.** `DailyOutreachCommand` / `SmartFollowupService` take ONE `primary_product`
   today. They must call a `NextBestOfferService` that reads *catalog + ledger + policy* and returns
   the next **untried** module, then feeds THAT module's hook/pain/features into the prompt.

**Launch-tomorrow mechanics:**
- *Global push:* create the module as a `Product` → `setAsActiveCampaign()` → all matching leads get it next.
- *Rotation:* add the module into the `upsell_products` sequence for the relevant persona → leads who
  ignored earlier modules pick it up on their next scheduled touch.

*This converts your screenshot from "3× the same pitch" into "Exam → Fee-collection/UCN → Payroll/HR",
each a fresh, locally-credible reason to reply.*

### C2. Prospect Lifecycle State Machine (fixes disengagement handling)

Replace the flat timer with explicit states and **different treatment per state**:

```
NEW → ENGAGED → NURTURING → RE_ENGAGE → DORMANT → RECYCLED
                    │            │           │
              (positive reply)  (angle+     (channel
               → HANDOFF        channel      switch)
                                rotation)
```

- **Cadence budget:** e.g. max N touches per module-angle, then rotate angle; after M angles with no reply, switch channel; after the channel matrix is exhausted, drop to low-cost long-interval nurture (bulk SMS quarterly) or mark cold.
- **Branch on engagement signal, not just time:** delivered-unread vs. read-unreplied vs. undelivered vs. replied-negative each get a different next action (retry channel / change angle / verify number / stop & tag reason).
- Wire the existing `WinBack` / `ConvertUnengaged` / `Nurture` commands as **states in this machine** rather than independent crons.

### C3. Turn on Multi-Channel as a *cascade*, not a picker (fixes single-channel)

The plumbing is 80% built. What's missing is the **escalation policy**:

- **Engagement-driven fallback:** define the trigger as *"channel attempted + cooldown elapsed + no positive engagement"* → advance the `fallback_chain`. Today fallback only advances on a missing address; extend it to advance on **non-response**. The `channel_attempt` / `cooldown_minutes` / `max_cross_channel_attempts` fields already exist to support this.
- **Recommended default cascade for the TZ market:** WhatsApp (rich, cheap) → **phone-SMS** (near-100% delivery, great for UCN/payment nudges) → **email** (formal, for bursars/finance with documents & pricing) → **bulk-SMS** (cheapest, wide nurture) → **human call task** (hot leads only).
- **Channel × angle pairing:** formal modules (payroll, reconciliation, budgeting) lean email; urgency/payment nudges (UCN, fee deadlines) lean SMS; discovery leans WhatsApp. `ChannelSelectionService` already has `requires_formal` and per-product policy hooks — feed the module's formality into it.
- **Governance:** global per-contact touch cap across all channels + quiet hours + opt-out honored everywhere (respect flags already exist) to protect sender reputation.

### C4. Learning loop: experimentation + attribution (fixes "can't learn")

- **Classify every inbound reply** semantically into an outcome taxonomy (interested / price / timing / competitor / wrong-person / already-have / stop). Use this to (a) pick the next angle, (b) route hot ones to humans, (c) report top objections to product/marketing.
- **Attribute conversions** to `(module, angle, channel, touch#, language, persona)`. Extend the existing channel-metrics idea to **message-angle metrics**.
- **Bandit-style selection:** let the Next-Best-Action ranker prefer the module/angle/channel combos that historically get replies **for that persona/industry**, with exploration so new modules (HR, recruitment) still get airtime. Start as simple win-rate ranking; graduate to contextual bandit.
- **A/B harness** for opening lines, subject lines, and CTA style, measured on reply-rate and positive-sentiment-rate, not send count.

### C5. Human-in-the-loop conversion bridge (fixes "AI closes alone")

- **Hot-lead handoff:** on a positive/high-intent reply, create a rep task with full context (which module resonated, transcript, suggested next step) and notify via the agent's existing `notification_methods`. AI books/holds; human closes.
- **Rep-assist, not just autopilot:** suggested replies a human can approve/edit for high-value accounts — concentrates human effort where `lead_score` says it pays.

---

## Part D — Sequenced roadmap (build order by ROI, not by ease)

**Phase 1 — Stop the bleeding (angle rotation).** Module/value-prop catalog (C1) + per-lead angle history + persona→module mapping. Rewrite the follow-up selector to choose the **next untried, most-relevant module angle** instead of the keyword ladder. *Biggest conversion lift, mostly content + logic, no new infrastructure.*

**Phase 2 — Lifecycle brain.** Prospect state machine (C2) with engagement-signal branching (delivered/read/replied) and cadence budgets. Fold win-back/nurture crons into it.

**Phase 3 — Channel cascade.** Flip on multi-channel for a pilot cohort (the rollout allow-list already exists), and implement engagement-driven fallback + governance (C3). Start WhatsApp→SMS since SMS delivery is the fastest non-response recovery locally.

**Phase 4 — Learning loop.** Reply classification + angle/channel attribution + win-rate-ranked Next-Best-Action (C4).

**Phase 5 — Human bridge + optimization.** Hot-lead handoff, rep-assist, then bandit selection and A/B harness (C5 + C4 maturation).

---

## Part E — How to know it's working (metrics)

Stop counting **messages sent**. Track:

- **Reply rate** and **positive-reply rate** per module-angle, per channel, per touch number.
- **Non-response recovery rate** after a channel switch (the core justification for multi-channel).
- **Angle-diversity index** — average distinct modules pitched before reply-or-drop (today ≈1; target 3–4).
- **Cost per positive reply** and **per demo booked** (AI credits + channel cost) — lets you shift budget from broad WhatsApp blasts to targeted SMS/human touches.
- **Human-handoff conversion rate** and **time-to-handoff** for hot leads.
- **Complaint/opt-out/WhatsApp-block rate** as a reputation guardrail.

---

## Part F — Quick reference: current-state file map

| Concern | File |
|---------|------|
| First-touch outreach | `app/Console/Commands/DailyOutreachCommand.php` |
| Follow-up template ladder (the repetition source) | `app/Services/SmartFollowupService.php` |
| Contact→lead conversion | `app/Console/Commands/ConvertUnengagedContactsCommand.php` |
| Win-back / nurture | `app/Console/Commands/WinBackOutreachCommand.php`, `app/Services/NurtureMessageService.php` |
| Inbound AI reply (RAG) | `app/Services/AiWhatsAppService.php`, `app/Services/OpenAiService.php` |
| Agent config/personality | `app/Models/AiSalesAgent.php` |
| Multi-channel selection (built, OFF) | `app/Services/MultiChannel/ChannelSelectionService.php` |
| Multi-channel orchestration | `app/Services/MultiChannel/OutboundOrchestratorService.php` |
| Channel feature flag | `config/multi_channel.php` (`enabled = false`) |
| Rollout gating | `app/Services/MultiChannel/RolloutGateService.php` |
| Channel performance telemetry | `app/Services/MultiChannel/ChannelMetricsService.php` |
