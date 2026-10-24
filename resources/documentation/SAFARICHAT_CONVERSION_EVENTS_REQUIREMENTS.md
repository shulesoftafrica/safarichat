# SafariChat — Conversion Events & Commitments (Requirement)

_How SafariChat learns that a lead **actually did something** (bought, signed up, submitted a form, paid) in an external system, acts on it, and chases the promises that never happen._

---

## 1. The gap (problem statement)

SafariChat tracks a lead's **interest** — scoring and pipeline state (`NEW → OUTREACHED → … → NEGOTIATING → CONVERTED/CLOSED`). But it has **no awareness of outcomes** that happen outside the WhatsApp conversation. So a won deal silently dies in the pipeline:

- **ShuleSoft:** a lead gets interested and fills the **application form on the website**. SafariChat never learns this → it keeps selling, never congratulates/onboards, never hands the client to SafariSuccess or tells the owner "we closed this one." The lead stays `NEGOTIATING` until someone edits it by hand.
- **The forgotten promise:** a lead says *"I'll fill the form"* (or *"I'll pay tomorrow"*) and forgets. There is **no record of that promise**, so SafariChat never reminds them, never nudges, never escalates.
- **LineShop:** a customer **buys a product**. SafariChat has no way to know → the lead sits in the same state forever.

Today the win-states (`CONVERTED`, `CLOSED`) exist but are **only ever set manually**. There is no event pipeline, no commitment tracking, no automatic post-win handoff / stop-selling. **This requirement fixes that.**

---

## 2. Goals / non-goals

**Goals**
1. **Ingest outcome events** from any external system (ShuleSoft website, LineShop, payment gateways, custom apps) and match them to the right lead.
2. **Auto-advance lead state** on an event (e.g. → `CONVERTED`) via a configurable rule, and **fire the right actions**: congratulate, stop selling, hand off to SafariSuccess / notify the owner, record revenue.
3. **Track commitments** a lead makes ("I'll fill the form"), and **chase them** if the matching event never arrives.
4. Make it **configurable per business** (which events matter, what state + actions they trigger).
5. Be **reliable** (idempotent, retry-safe, auth'd) and **auditable**.

**Non-goals (for now)**
- Being a full CRM workflow engine. We ship a focused event → state → action model.
- Deep attribution/analytics beyond recording the event + revenue.

---

## 3. Core concepts

Two new primitives:

1. **Conversion Event** — a fact that happened in an external system about a contact: `purchase`, `payment`, `form_submitted`, `signup`, `subscription_started`, `registration`, or a custom type. Carries who (phone/email), what, when, amount, and metadata.
2. **Commitment (expected event)** — a promise a lead made in conversation that we are *waiting* on: "fill the application form by tomorrow", "pay this week". It has a `due_by` and a `status` (pending/fulfilled/missed). A Conversion Event can **fulfil** a Commitment.

```
  External system (ShuleSoft / LineShop / payment / custom)
        │  webhook: "this phone just submitted the form / bought X / paid"
        ▼
  SafariChat  ── Event ingest ──► match to lead ──► Event Rule ──► actions
        ▲                                                   │
        │  AI detects a promise in chat                     ├─ set lead status (CONVERTED/CLOSED/…)
        └── Commitment ("I'll fill the form by Fri") ◄──────┤─ congratulate + stop selling
                       │ not fulfilled by due_by            ├─ hand off to SafariSuccess / notify owner
                       └──────► nudge + escalate            └─ record revenue + sales activity
```

---

## 4. Where & how owners configure this (self-service)

Owners configure events themselves — no dev involvement on our side per customer. Because **many different systems** will connect, the model is: the owner **defines an event**, we **generate its webhook URL**, and we **show the exact JSON** their system should POST. Their developer wires their system to that URL once.

### 4.1 Location: an **"Events & Automations"** tab on the AI Sales Agent page
On **Sales Agent → Events & Automations** (the same screen where the agent is configured):
- The feature is **gated from the Pro plan** (Starter/Trial see an upgrade prompt). `plan >= pro` ⇒ can create/enable events.
- **After-sale support agent number** (agent-level field, set once): the WhatsApp number of the person/team that takes over **once a lead is WON** (`CONVERTED` or `CLOSED`). Every WON transition hands the conversation to this number (see §8). If it's blank, WON still stops the selling and notifies the owner — it just doesn't route to a dedicated after-sale agent.
- The owner clicks **"Add event"** and fills a short form (plain language):
  - **Event name** — e.g. "Application submitted", "Purchase completed", "Payment received".
  - **Event type** — pick a standard type (`purchase`, `payment`, `form_submitted`, `signup`, `registration`) or **Custom**.
  - **When this happens, do:** choose actions — _set lead status to…_, _congratulate the customer_ (with an editable message), _stop selling_, _hand off to SafariSuccess / notify owner_, _record revenue_ (see the action vocabulary in §6).
  - **Match the customer by:** phone or email (which JSON field carries it — defaults provided).
- The owner clicks **Enable**. Only then is the event **live**.

### 4.2 On enable: we show the webhook URL + the expected JSON
Once enabled, the event card reveals everything the owner hands to their developer:

- **A unique webhook URL** (one per event — the URL itself identifies the business + event, so the third party sends nothing else to authenticate):
  ```
  POST https://safarichat.ai/api/events/hook/{event_token}
  Content-Type: application/json
  ```
  `{event_token}` is a long random, per-event secret (rotatable/revocable). No header auth needed — the token is the credential. (A stricter mode can also require an `X-Signature` HMAC.)

- **The exact JSON we expect** (copyable, with a one-line explanation per field):
  ```json
  {
    "phone": "+255714000000",          // the customer's phone (or "email")
    "reference": "SS-APP-12345",       // your order/form id (used to de-duplicate)
    "amount": 69000,                    // optional (purchases/payments)
    "currency": "TZS",                  // optional
    "occurred_at": "2026-10-25T10:30:00Z", // optional; defaults to now
    "note": "Application form submitted"   // optional, shown to the team
  }
  ```
- **A ready cURL example** and a **"Send test event"** button (fires a sample through the pipeline so the owner sees the lead move + the message fire).
- **Status/health:** last received, count, and last error — so the owner can see it's working.

**Flexibility for systems that can't change their payload:** an optional **field-mapping** advanced section lets the owner say "my JSON calls the phone `msisdn` and the id `order_no`", so we read those instead of the defaults. Default path needs no mapping — the system just sends the shape above.

### 4.3 Internal events (same pipeline)
SafariChat also raises its own events (appointment `completed`, owner clicks "Mark converted", proposal accepted in chat) through the **same rule engine**, so configuration and behavior are uniform whether the trigger is external or internal.

### 4.4 Sources (what the owner points at these URLs)
- **ShuleSoft website** — on application-form submit, POST to the "Application submitted" event's URL (phone from the form). The canonical example.
- **LineShop** — point LineShop's **sale hook** (`Sales_model::addSale` / `Site::syncQuantity`, where we already plan the stock webhook) at the "Purchase completed" event URL with the buyer's phone + total.
- **Payment gateways** (Flutterwave / Stripe / UCN) — their success webhook → the "Payment received" event URL.
- **Any custom app** — same URL + JSON contract; nothing bespoke on our side.

---

## 5. Identity resolution

On each event, normalize `identifier` (phone → E.164) and match **within the business**:
1. `BusinessContact` by `guest_phone` / `guest_email` → its latest `Lead`.
2. If no lead exists but a contact does → create a lead (source `event`).
3. If neither → store as an **orphan event**; re-attempt matching when that contact next messages (or the owner links it).

Matching never blocks the 200 response.

---

## 6. Event Rules (the configuration)

Per business, an **Event Rule** maps an event type to a target state + actions. Sensible **defaults** ship out of the box; businesses can tune them.

**Won is TWO milestones** (decided):
- **`CONVERTED`** = the customer has started but not yet paid — e.g. ShuleSoft **application form submitted → 30-day trial**. The relationship **leaves the sales person** and moves to service delivery / customer success.
- **`CLOSED`** = the customer **paid** (e.g. after the 30-day trial) — fully won.

Both are **WON states** → the AI stops selling and hands off (see §8). `CONVERTED` and `CLOSED` differ only in what the after-sale team does next (onboard-the-trial vs paid-customer success).

| Event type | Default lead status | Default actions |
|---|---|---|
| `form_submitted` / `registration` / `trial_started` (e.g. ShuleSoft application) | **`CONVERTED`** | congratulate · **stop selling** · **hand off to the after-sale agent** · notify owner · (optional) push to SafariSuccess |
| `payment` / `purchase` (paid) | **`CLOSED`** | congratulate · **stop selling** · **hand off to the after-sale agent** · record revenue · notify owner · (optional) push to SafariSuccess |
| `signup` (not yet trial/paid) | `QUALIFIED`/`ENGAGED` | thank + guide next step; keep nurturing toward the trial/purchase |
| `subscription_cancelled` / `refund` | `CHURNED` | notify owner · trigger win-back |

**Action vocabulary** (composable per rule):
- `set_status: <LeadStatus>`
- `congratulate` (AI-generated contextual message to the customer)
- `stop_selling` (suspend nurture/chase/offers — the deal is won)
- `handoff_to: safarisuccess | owner | sales_agent` (reuse `requestHandoff()`/`assignToAgent()`)
- `notify_owner` (WhatsApp/email: "✅ We closed <name> — <amount>")
- `record_revenue` (store amount+currency for reporting)
- `start_onboarding` (kick a post-sale nudge sequence)

This reuses **existing primitives**: Lead statuses (`CONVERTED`/`CLOSED`/`CHURNED`), the handoff flow (`BusinessContact::requestHandoff/assignToAgent`, `handoff_status`), SafariSuccess/CS, and the nurture engine (which must now **honor a "stop_selling" flag** so it doesn't keep chasing a won customer).

---

## 7. Commitments (promises) & non-fulfillment

The other half of the gap: a lead says they'll do something and forgets.

1. **Capture:** the AI detects a commitment in conversation ("I'll fill the form", "I'll pay tomorrow", "send it, I'll buy") and records a **Commitment** with an inferred `due_by` (reuse the same natural-language time parsing we built for appointments/activities). A rep can also log one manually.
2. **Fulfilment:** when a matching Conversion Event arrives before `due_by`, mark the commitment `fulfilled` and run the conversion flow.
3. **Non-fulfilment:** a scheduled checker finds commitments past `due_by` with no matching event and:
   - sends the lead a gentle **nudge** ("Hi — were you able to complete the application? Here's the link…"),
   - after a second miss, **escalates** to the sales person + owner ("<name> promised to apply by Fri, hasn't — follow up").
4. Commitments appear on the customer's CRM timeline alongside the activity log we already built.

This turns "said they would, then silence" into a tracked, chased step.

---

## 8. What happens on conversion (the win flow)

When an event (or fulfilled commitment) means **won** (`CONVERTED` or `CLOSED`):
1. **Set status** → `CONVERTED` (trial/form) or `CLOSED` (paid).
2. **Congratulate** the customer (AI, contextual, in their language) — a warm "welcome aboard", not a pitch.
3. **Stop selling** — suspend nurture/chase/offer rotation, and the **AI stops auto-replying with sales** for this contact. The relationship is no longer the sales person's.
4. **Hand off to the after-sale agent** — route the conversation to the configured **after-sale support number**: set `handoff_status = handed_off`, notify that number on WhatsApp with the customer's details, and let the human take over (reuse the existing `requestHandoff`/`assignToAgent` flow). If no after-sale number is set, skip routing but still do the rest.
5. **Notify the owner** ("✅ <name> is now <Converted/Closed>" + amount if paid) — so nothing is missed even if the after-sale rep is unavailable.
6. **Record revenue** (for `CLOSED`/paid) + a sales activity on the CRM timeline (win-rate reporting).
7. **(Optional) push to SafariSuccess** — since customer success is managed in the separate SafariSuccess platform, fire an outbound event/webhook to it so the won client is onboarded there (future connector; not required for the core flow).

**The smart rule (decided):** once a contact is in a WON state, SafariChat must **never resume selling** to them — any later inbound message is treated as after-sale and kept with the after-sale agent (not the sales AI), unless the owner explicitly re-opens the lead. So a won deal is *celebrated, protected from further sales spam, handed to the right person, reported, and (optionally) pushed to SafariSuccess* — automatically.

---

## 9. Data model (new)

- **`event_configs`** — the owner-defined events (one row per configured event; this IS the thing created on the Events & Automations tab):
  `id, business_id, ai_sales_agent_id?, name, event_type, token (unique, the webhook secret in the URL), is_enabled, set_status?, actions(json), message_template?, match_by (phone|email), field_map(json, optional), last_received_at?, received_count, last_error?, created_at`.
- **`conversion_events`** — the actual events received (or raised internally), each tied to the config that caught them:
  `id, business_id, event_config_id?, lead_id?, business_contact_id?, event_type, source, identifier, identifier_type, amount?, currency?, reference?, metadata(json), idempotency_key (unique per business; derived from reference when present), occurred_at, status (received|matched|processed|orphan), processed_at, created_at`.
- **`lead_commitments`** — promises to chase:
  `id, business_id, lead_id, expected_event_type, promised_text, due_by, status (pending|fulfilled|missed), fulfilled_event_id?, created_by (ai|user_id), nudged_at?, escalated_at?, created_at`.

The per-event `token` in the URL replaces a shared business key (each event is independently rotatable/revocable). `event_rules` from the earlier draft is **merged into `event_configs`** — the owner's event config *is* the rule.

---

## 10. The AI's role

- **Detect commitments** from conversation and set `due_by` (same parser as appointments/activities).
- **Generate** the congratulations/onboarding message and the nudge copy.
- **Respect state:** once a lead is `CONVERTED`/`CLOSED` or flagged `stop_selling`, the AI switches from selling to **onboarding/support tone** and the nurture engine leaves them alone.

---

## 11. Reliability & security

- **Per-business key** auth on ingest; keys rotatable; **HMAC signature** optional for stricter partners.
- **Idempotency key** dedupes retries; store raw payloads for audit/replay.
- **Fast 200**, async processing; **orphan events** retried on next contact touch.
- All actions wrapped so a failure (e.g. owner has no phone) never drops the event.

---

## 12. Pricing (suggested)

- **Manual "Mark converted"** (fires the win flow): all paid plans.
- **Events & Automations tab — configure events, get webhook URLs, auto-convert + commitments/chasing:** **Pro+** (Starter/Trial see an upgrade prompt). This is the paid line the owner suggested.
- **SafariSuccess auto-handoff + onboarding sequences:** **Premium**.

---

## 13. Phased roadmap

- **Phase A — Event spine + config UI:** `event_configs` + `conversion_events`; the **Events & Automations tab** on the AI Sales Agent (Pro-gated) to add/enable an event and reveal its **webhook URL + expected JSON + test button**; the per-event `POST /api/events/hook/{token}` endpoint (idempotent, fast 200); identity resolution; and the **win flow** (status, congratulate, stop-selling, notify owner, handoff, record revenue). Manual "Mark converted" routes through the same flow.
- **Phase B — Source connectors:** ShuleSoft form → `form_submitted`; **LineShop purchase webhook** → `purchase`; payment-gateway → `payment`.
- **Phase C — Commitments:** AI commitment capture + due dates, fulfilment matching, the nudge/escalation scheduler, CRM-timeline display.
- **Phase D — Onboarding sequences** post-conversion (optional).

Reuses heavily what already exists: lead statuses, the handoff flow, the nurture engine (add a stop flag), the NL time parser, the per-shop key pattern, and the LineShop webhook seam.

---

## 14. Decisions (resolved)

1. **Won = two milestones.** `form_submitted`/trial → **`CONVERTED`** (relationship leaves sales, moves to service delivery / customer success); `payment` → **`CLOSED`** (paid). Both are WON → stop selling + hand off.
2. **After-sale handoff is smart + configurable.** Once a contact is WON, SafariChat stops selling and **hands the conversation to the configured after-sale support number** (an agent-level field on the AI Sales Agent). Customer success is managed in the separate **SafariSuccess** platform; routing to the after-sale number is the core behavior, with an optional outbound push to SafariSuccess later.
3. **ShuleSoft → webhook (preferred).** The ShuleSoft website POSTs to the "Application submitted" event's URL on form submit. (Polling ShuleSoft's DB is a fallback only if a webhook truly isn't possible.)
4. **Best-practice defaults** (tunable later):
   - Commitment windows: "today/this evening" = today; "tomorrow" = +1 day 10:00; "this week" = Friday 10:00; "end of month" = last business day 10:00.
   - Chasing: **1 nudge** to the customer at `due_by`; if still unfulfilled after ~24–48h, **escalate** to the sales rep + owner.
5. **Stop-selling is full.** Once WON, the sales AI yields entirely: nurture/chase paused, no pitching in replies, later messages stay with the after-sale agent — unless the owner explicitly re-opens the lead.

**Model note:** the after-sale number lives on the **AI Sales Agent** (add `after_sale_agent_phone`), not per-event, since one after-sale team handles all of an agent's won customers.
