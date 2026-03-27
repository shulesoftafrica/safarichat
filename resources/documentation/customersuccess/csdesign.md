# SafariChat — Customer Success (CS) Design Document

> **Purpose:** Define every automated touchpoint that drives new-business onboarding, value realization, retention, and expansion within the SafariChat platform.
> **Last updated:** 2026-03-27
> **Audience:** Engineering, Product, and Customer Success teams.

---

## 1. Philosophy

Customer success in SafariChat is proactive, conversational, and data-driven. Every notification is delivered through the same channel the business uses every day — **WhatsApp** — so onboarding, reporting, billing, and engagement advice arrive natively, without requiring the user to log in to a dashboard.

Three principles guide all CS messages:

1. **One next action** — each message tells the user exactly one thing to do next.
2. **Evidence-backed** — recommendations cite what works for similar businesses on the platform.
3. **Light touch after day 7** — after a user is active, daily messages must be short (≤ 3 sentences of advice) to avoid notification fatigue.

---

## 2. CS Journey Map

```
Account Created
      │
      ▼
┌─────────────────────────────┐
│  MILESTONE 1: QR Connected  │  → Welcome + Step 1 guidance
└─────────────────────────────┘
      │
      ▼
┌─────────────────────────────┐
│  MILESTONE 2: First Product │  → Congrats + promotion best practice
└─────────────────────────────┘
      │                              ┌──────────────────────────┐
      ▼                              │ (parallel, every evening)│
┌─────────────────────────────┐      │   DAILY SUMMARY REPORT   │
│  TRIAL ACTIVE               │ ──── │   + light recommendation │
│  (daily countdown reminders)│      └──────────────────────────┘
└─────────────────────────────┘
      │
      ▼
┌─────────────────────────────┐
│  TRIAL ENDING (T-3h / T=0) │  → Upgrade CTA + conversational billing
└─────────────────────────────┘
      │
      ▼
┌─────────────────────────────┐
│  ACTIVE SUBSCRIPTION        │  → Daily reports, usage alerts, credit alerts
└─────────────────────────────┘
      │                              ┌──────────────────────────┐
      ▼                              │  EXPANSION TRIGGERS       │
┌─────────────────────────────┐      │  - Upgrade nudge          │
│  UPGRADE / CREDIT TOP-UP    │ ──── │  - Credit low alert       │
└─────────────────────────────┘      └──────────────────────────┘
      │
      │  (if no engagement detected)
      ▼
┌─────────────────────────────┐
│  INACTIVITY / CHURN RISK    │  → Re-engagement flow (§3.10)
│  Day 3: soft nudge          │
│  Day 10: win-back + rescue  │
└─────────────────────────────┘
      │  (if engagement resumes)
      ▼
   ACTIVE (re-enters normal journey)
```

---

## 3. Trigger Events & Message Flows

### 3.1 MILESTONE 1 — WhatsApp Connected (QR Scanned)

**Trigger:** `WhatsappInstance` status changes to `connected` for the first time (event: `WhatsappInstanceConnected`).

**Message content:**

```
🎉 *Welcome to SafariChat, [Business Name]!*

Your WhatsApp is now live and your AI sales agent is ready.

Here is your first step ✅

👉 *Create your first product or service*
Go to: [Dashboard Link] → Products → Add Product

*Why this matters:*
Your AI agent uses your product list to answer customer questions, recommend items, and qualify leads automatically. Without products, the AI has nothing to sell.

Businesses that add their first product within 24 hours of connecting see *3× more leads captured in their first week.*

Reply *HELP* at any time to get support.
```

**Implementation notes:**
- Dispatched by `WhatsappInstanceConnectedListener` (or inside `CheckWhatsappInstancesCommand` on first `connected` status).
- Store a flag `cs_welcome_sent_at` on `whatsapp_instances` (nullable timestamp) to prevent re-sending on reconnect.
- Deliver via the **system number** (the same number used to send OTPs) as the **sender**. The **recipient** is the business owner's registered phone number. This keeps CS messages in a dedicated system thread, separate from the business's own AI sales conversations.
- Language resolution: `CsMessageRenderer` reads `users.locale` for this user. Swahili users receive the Swahili welcome template; other locales follow the Tier 1/2/3 rules in §11.

---

### 3.2 MILESTONE 2 — First Product/Service Created

**Trigger:** `Product` (or `Service`) model `created` event, filtered to first product only (`where user has no prior products`).

**Message content:**

```
✅ *Product added successfully!*

"[Product Name]" is now live. Your AI agent can start selling it.

*Here is what the top businesses on SafariChat do next:*

📣 Share [Product Name] on your WhatsApp Status, Facebook, or Instagram with a caption like:
_"DM this number for [Product Name] — and we will help you instantly: +[YourNumber]"_ or promote this product and specify this number  +[YourNumber] as action

📤 Or use *Compose Message* inside SafariChat to send to your saved contacts.

⚠️ *Important:* Never broadcast to unknown numbers — WhatsApp may ban the account. Start with warm contacts only.

Once people start messaging you, your AI agent handles everything from there. 🚀
```

**Implementation notes:**
- Listen on `Product::created` (via `ProductObserver` or `ProductCreatedListener`).
- Condition: `Product::where('user_id', $userId)->count() === 1`.
- Deliver once per user (flag `cs_first_product_message_sent_at` on `users` table).

---

### 3.3 DAILY EVENING SUMMARY REPORT

**Trigger:** Scheduled job runs at **20:00 local time** per business timezone (fallback: `Africa/Nairobi`). Applies to all users with an active WhatsApp session regardless of subscription status (trial or paid).

**Report content:**

```
📊 *Daily Report — [Business Name]*
📅 [Today's Date]

━━━━━━━━━━━━━━━━━━━━━
*Today's Activity*
━━━━━━━━━━━━━━━━━━━━━
💬 Total conversations:    [N]   ([+/-N vs yesterday])
🆕 New prospects today:    [N]   ([+/-N vs yesterday])
🔥 Active leads:           [N]
✅ Closed / Converted:     [N]
🔄 Lead stage changes:     [N]

*Lead Breakdown*
  🟣 New:          [N]
  🟡 Interested:   [N]
  🟠 Engaged:      [N]
  🟢 Converted:    [N]
  🔴 Churned:      [N]

━━━━━━━━━━━━━━━━━━━━━
💡 *Today's Recommendation*
━━━━━━━━━━━━━━━━━━━━━
[Single contextual recommendation — see §3.3.1]

Reply *REPORT* for a full PDF report link.
```

**§3.3.1 Contextual Recommendation Logic (one per day, rotated):**

| Condition | Recommendation shown |
|---|---|
| New prospects today = 0 | "No new conversations today. Share your WhatsApp number on one social post tonight — even a single story can generate 5–10 new leads." |
| Churn count > 2 | "You had [N] leads go cold today. Consider having your AI follow up with them — go to Leads → Churned and trigger a re-engagement sequence." |
| Converted > 0 | "Great — [N] sale(s) closed today! Ask your buyers for a quick referral: one happy customer can bring 3 more." |
| Active leads > 50 and no follow-up action | "You have [N] warm leads waiting. Your AI will continue following up — make sure your product info is up to date so responses are accurate." |
| Default (no condition triggered) | "Your AI worked [N] conversations today. Keep your WhatsApp session connected overnight so no leads are missed." |

**Implementation notes:**
- Job class: `SendDailyBusinessSummaryJob` dispatched by `DailyBusinessReportCommand` (scheduled in `Kernel.php`).
- Query `BusinessContact` aggregates grouped by `business_id` for today vs. yesterday.
- Compare `lead_status` transitions within the day (needs a `lead_status_history` table or event log, or daily snapshot).
- Recommendation selected by a `DailyRecommendationResolver` service class checking conditions in priority order.
- If the WhatsApp instance is **disconnected**, skip the daily summary and instead send an urgent reconnection alert via the **system number** to the business owner's registered phone number:

  ```
  🔴 *Your WhatsApp is disconnected — SafariChat*

  Your AI sales agent is currently offline. Customers messaging you right now are receiving no response and leads are being lost.

  👉 Reconnect now: https://safarichat.ai → Settings → WhatsApp → Scan QR Code

  Takes less than 1 minute. Don't let leads go cold.
  ```

  This alert is sent **once per disconnection event** (deduplicated via `cs_message_log` — type `whatsapp_disconnected_alert`) and is not counted against the 4 CS messages/day cap.

---

### 3.4 TRIAL DAILY COUNTDOWN REMINDER

**Trigger:** Runs each morning at **09:00** for every user whose `trial_ends_at` is in the future and `subscription_status = trial`.

**Message template (sent once per day):**

```
⏳ *[N] day(s) left on your SafariChat trial*

Your AI sales agent is working hard — don't let it stop.

[If N > 3]
You still have time. Explore everything before your trial ends:
• Add more products
• Check your lead pipeline
• Try the Broadcast feature

[If N <= 3]
⚠️ Your trial ends on [Date]. After that, your AI agent will pause and all incoming leads will be unanswered.

To keep going, reply *UPGRADE* or visit: [Billing Link]
```

**Implementation notes:**
- Job: `SendTrialReminderJob`, dispatched by `TrialReminderCommand` (scheduled daily 09:00).
- Only send once per day per user — use `cs_trial_reminder_last_sent_at` on `users` (or a `cs_messages_log` table).
- Skip if user already has an active paid subscription.

---

### 3.5 TRIAL ENDING — T-3 HOURS AND T=0

#### 3.5.1 T-3 Hours Warning

**Trigger:** `TrialEndingWarningJob` dispatched for users where `trial_ends_at BETWEEN NOW() AND NOW() + 3 hours` (run every 15 minutes via scheduler to catch the window).

```
🚨 *Your trial ends in 3 hours*

After that, your AI sales agent will stop responding to customers.

Every hour you're offline, you risk losing leads that will never come back.

Reply *UPGRADE* right now and choose your plan — takes 2 minutes.
```

#### 3.5.2 Trial Expired

**Trigger:** `TrialExpiredJob` dispatched when `trial_ends_at <= NOW()` and `subscription_status` still `= trial`.

```
⛔ *Your SafariChat trial has ended*

Your AI sales agent is now paused. Customers messaging you right now are getting no response.

Here is what you are missing every day without SafariChat:
• Auto-qualification of every lead
• 24/7 AI responses while you sleep
• Daily sales reports

Reply *UPGRADE* to reactivate instantly.
```

---

### 3.6 TRIAL UPGRADE CONVERSATIONAL BILLING FLOW

This is a **reply-driven conversation** triggered whenever a business owner responds to any CS message with an intent to pay, upgrade, or ask about pricing.

**Handled by:** `CsConversationHandler`, integrated into the inbound webhook pipeline before the AI sales agent — CS intents always take routing priority.

---

#### Intent Detection — Two Options

| | Option | Method | Recommended |
|---|---|---|---|
| ✅ | **AI-based intent detection** | Pass the inbound message to the AI agent with a lightweight system prompt: _"Classify this message: does the user intend to upgrade/pay, ask about pricing, buy credits, or none of the above? Return JSON: `{intent: 'upgrade'|'pricing'|'credits'|'none'}`"_ | **Yes — use this** |
| | **Keyword extraction** | Match against a local keyword list (`UPGRADE`, `how much`, `price`, `bei`, `package`, `lipa`, `pay`, etc.) | Fallback if AI is unavailable |

The AI option handles any language naturally (English, Swahili, mixed), without maintaining an exhaustive keyword list. The keyword fallback runs if the AI returns `'none'` or errors out.

---

#### Step 1 — Fetch & Show Plans Dynamically

On intent detected, `CsConversationHandler` calls the billing API to retrieve current plans:

```
GET /api/billing/plans
→ BillingApiController::getProductInfo()
```

Response schema (abbreviated):
```json
{
  "success": true,
  "data": {
    "plans": {
      "starter": { "name": "Starter Plan", "price": 69000, "currency": "TZS", "features": { ... } },
      "pro":     { "name": "Pro Plan",     "price": 149000, "currency": "TZS", "features": { ... } },
      "premium": { "name": "Premium Plan", "price": 299000, "currency": "TZS", "features": { ... } }
    }
  }
}
```

Plans are **never hardcoded** in CS messages. The message is rendered dynamically from the API response each time, so any pricing or feature change on the billing platform is reflected immediately with no code changes.

System message sent to business owner (rendered from API response):

```
📦 *SafariChat Plans*

Here are our current plans:

1️⃣ *[plan.name]* — TZS [plan.price]/mo
   • [feature_1]
   • [feature_2]
   • [feature_3]

2️⃣ *[plan.name]* — TZS [plan.price]/mo
   • [feature_1]
   • [feature_2]
   • ...

3️⃣ *[plan.name]* — TZS [plan.price]/mo
   • [feature_1]
   • ...

Reply with *1*, *2*, or *3* to select your plan.
```

CS session state set to: `awaiting_package_selection`

---

#### Step 2 — Create Invoice via Billing Platform

When the user replies with a package number (1, 2, or 3), `CsConversationHandler` maps the selection to the plan code and calls:

```
POST /api/billing/upgrade
→ BillingApiController::upgradePlan()

Body: { "plan_code": "pro", "amount": 149000 }
```

The billing platform:
1. Creates an invoice against the user's customer record
2. Calls `GET /invoices/{id}/payment-gateways` to get all payment links
3. Returns the full invoice response including `payment_links`

Response schema:
```json
{
  "success": true,
  "invoice_id": "inv_xxxxx",
  "plan": "pro",
  "amount": 149000,
  "payment_links": {
    "ucn": "XXXXXXXX",
    "stripe": "https://checkout.stripe.com/...",
    "flutterwave": "https://checkout.flutterwave.com/..."
  }
}
```

`CsConversationHandler` then renders the payment message directly from this response — **no hardcoded UCN, Stripe, or Flutterwave links**:

```
✅ *[plan.name] selected — TZS [amount]/mo*

Choose your payment method:

🇹🇿 *M-Pesa / Tigo Pesa / Airtel Money (Tanzania)*
   Lipa nambari: *[payment_links.ucn]*
   Jina: SafariChat Ltd
   Amount: TZS [amount]
   Kisha tuma screenshot ya receipt hapa.

💳 *Card Payment (Worldwide)*
   Pay securely via Stripe:
   👉 [payment_links.stripe]

🌍 *Mobile Money (Africa — Other Countries)*
   Pay via Flutterwave:
   👉 [payment_links.flutterwave]

Payment confirmed automatically for Stripe and Flutterwave.
For mobile money, send your payment screenshot and we'll activate within 1 hour.
```

CS session state updated to: `awaiting_payment_confirmation`
`payload.invoice_id` = invoice ID from response

---

**Implementation notes:**
- `CsConversationHandler` must call `BillingApiController::upgradePlan()` internally (not via HTTP self-call) — inject or reuse the service method directly.
- Plans list rendered by `CsMessageRenderer::renderPlanList(array $plans)` which iterates the API response and uses `BillingApiController::convertFeaturesToDescription()` for human-readable feature bullets.
- The entire billing flow is stateless on the CS side — invoice state lives in the billing platform; `cs_conversation_sessions.payload.invoice_id` is the only local reference needed.
- On Stripe/Flutterwave webhook confirmation → auto-activate subscription → send §3.7 message.
- On screenshot upload → route to billing team queue for manual verification.
- If the billing API returns an error during invoice creation, send: _"Sorry, we could not generate your payment details right now. Please visit https://safarichat.ai → Billing to complete your upgrade, or reply again in a few minutes."_

---

### 3.7 SUBSCRIPTION ACTIVATED — SUCCESS MESSAGE

**Trigger:** `SubscriptionActivated` event (fired after payment verified, whether stripe, flutterwave, or manual).

```
🎊 *You are now on SafariChat [Package Name]!*

Your AI sales agent is back online and fully powered.

*What you now have:*
✅ [Feature 1 from package]
✅ [Feature 2 from package]
✅ [Feature 3 from package]
✅ [Conversation limit or "Unlimited"]

*Your subscription renews on:* [Renewal Date]

To get the most from [Package Name], here is your next recommended action:
→ [Context-aware CTA based on what user hasn't done yet, e.g., add more products, enable auto-follow-up]

Thank you for trusting SafariChat. 🙏
```

**Implementation notes:**
- Features list is driven by a `packages` config/table with a `features` JSON column — rendered dynamically.
- CTA is selected by `OnboardingGapResolver` (checks if user has products, has sent a broadcast, has configured follow-up sequences).

---

### 3.8 USAGE EXCEEDS CURRENT PACKAGE — UPGRADE NUDGE

**Trigger:** `UsageLimitApproachingJob` — fires when a user's monthly conversation count hits **80%** and again at **95%** of their package limit.

```
📈 *You are [80%/almost at] your plan limit*

Your AI has had [N] conversations this month — you're using SafariChat heavily (that's great!).

Your current plan: *[Package Name]* — [limit] conversations/month
You've used: *[N] conversations* ([X]% of your limit)

[At 95%]: ⚠️ You have approximately [remaining] conversations left this month. After that, your AI will pause until next billing cycle.

Reply *UPGRADE* to move to a higher plan instantly.
```

#### Step 1 — User Replies "UPGRADE" (from active subscription context):

```
🚀 *Upgrade your SafariChat plan*

You are currently on *[Current Package]*.

Available upgrades:

2️⃣ *Growth* — TZS 60,000/mo  (+TZS [prorated_diff] for remaining [N] days)
3️⃣ *Business* — TZS 150,000/mo  (+TZS [prorated_diff] for remaining [N] days)

Reply *2* or *3* to upgrade.
```

#### Step 2 — Create Invoice & Show Payment Details

`CsConversationHandler` calls `POST /api/billing/upgrade` with the selected plan code and the pro-rated amount calculated from the billing account's current cycle:

```
POST /api/billing/upgrade
Body: { "plan_code": "pro", "amount": [net_amount] }
→ BillingApiController::upgradePlan()
```

The billing platform creates the invoice and returns `payment_links` from the gateway. The CS message is rendered entirely from this response:

```
🧾 *Upgrade Invoice*

From: [Current Package]
To:   [New Package]
─────────────────────
Days remaining in cycle:  [N] days
Credit for current plan:  -TZS [credit_amount]
New plan pro-rated cost:  +TZS [prorated_new]
─────────────────────
*Amount due today:* TZS [net_amount]

Pay via:

🇹🇿 Mobile money → UCN: *[payment_links.ucn]* | Amount: TZS [net_amount]
💳 Stripe → [payment_links.stripe]
🌍 Flutterwave → [payment_links.flutterwave]
```

**Implementation notes:**
- Pro-rated `net_amount` calculated locally before API call: `(days_remaining / cycle_days) * (new_plan_price - current_plan_price)`. Passed as `amount` in the request body so the invoice reflects the correct charge.
- UCN, Stripe, and Flutterwave links are **never hardcoded** — always extracted from `response.payment_links`.
- `cs_conversation_sessions.payload.invoice_id` stores the returned `invoice_id` for webhook correlation.
- On payment confirmed → `BillingApiController::upgradePlan()` webhook fires `SubscriptionUpgraded` event → send updated §3.7-style confirmation.

---


### 3.9 CREDITS LOW ALERT & PURCHASE FLOW

**Trigger:** `CreditLowAlertJob` — fires when user's AI message credit balance drops to **≤ 20%** of their purchased credits, and again at **≤ 10%**.

```
⚡ *Your AI credits are running low*

Remaining credits: *[N] credits* ([X]% left)

Credits power every AI-generated message. When they run out, your AI agent will stop responding to customers.

To keep your AI running 24/7, reply *BUY CREDITS* or reply with the amount you'd like to top up (e.g., *"50,000"*).
```

#### Step 1 — User Replies with Amount or "BUY CREDITS":

```
💳 *Purchase AI Credits — [TZS Amount Requested]*

You'll receive approximately *[N credits]* for TZS [Amount].

Pay via:

🇹🇿 Mobile money → UCN: *[UCN]* | Amount: TZS [Amount]
💳 Stripe → [Link]
🌍 Flutterwave → [Link]

Credits are added to your account automatically after payment confirmation.
```

**Implementation notes:**
- Credit packages are configurable (e.g., TZS 10,000 = 100 credits, TZS 50,000 = 600 credits with 20% bonus).
- `CsConversationHandler` detects this as a credit purchase intent (keywords: `BUY CREDITS`, `credits`, `nunua credits`, or a numeric amount in the context of a credit alert conversation).
- Stripe/Flutterwave links include `type=credits&user_id=X&amount=Y` metadata.
- On payment confirmed → `CreditService::addCredits($userId, $credits)` → send confirmation:

```
✅ *[N] credits added to your account*

Your AI agent is fully powered again. Current balance: *[New Total] credits*.
```

---

### 3.10 INACTIVE BUSINESS DETECTION & RE-ENGAGEMENT FLOW

#### The Problem

A business that stops engaging — no conversations, no leads coming in, WhatsApp possibly disconnected — is at maximum churn risk. Without proactive intervention, they silently leave and rarely return. This section defines how CS detects, classifies, and recovers inactive businesses.

---

#### Inactivity Definition

"No engagement" means **zero new `BusinessContact` records AND zero inbound messages processed** through the business's WhatsApp instance for a rolling period. This is measured per `business_id` from `cs_daily_snapshots` and `BusinessContact` activity.

| Tier | Signal | Classification |
|---|---|---|
| **At-risk** | 0 new conversations for 3 consecutive days | Early warning — soft nudge |
| **Churned** | 0 new conversations for 10 consecutive days | Churn confirmed — win-back |
| **Abandoned** | 10 days inactive + WhatsApp disconnected | Highest priority — rescue attempt |

---

#### Detection Job: `BusinessInactivityMonitorJob`

Runs **daily at 08:00**, queries `cs_daily_snapshots` per `business_id`:

```sql
-- At-risk: 3 days of zero activity
SELECT business_id
FROM cs_daily_snapshots
WHERE snapshot_date >= CURRENT_DATE - INTERVAL '3 days'
GROUP BY business_id
HAVING SUM(total_conversations) = 0
   AND COUNT(*) = 3;

-- Churned: 10 days of zero activity
SELECT business_id
FROM cs_daily_snapshots
WHERE snapshot_date >= CURRENT_DATE - INTERVAL '10 days'
GROUP BY business_id
HAVING SUM(total_conversations) = 0
   AND COUNT(*) = 10;
```

Results are deduplicated against `cs_message_log` (type `inactivity_day3` / `inactivity_day10`) — alerts are sent **once per inactivity episode**, not every day. An episode resets when engagement resumes (new conversation detected).

---

#### Tier 1 — Day 3 At-Risk Nudge

**Sent via:** system number → business owner's phone

```
👋 *Hey [Business Name] — your AI agent misses you!*

We noticed there have been no new customer conversations in the last 3 days.

Here are 3 quick things you can do right now to bring customers in:

1️⃣ Post your WhatsApp number (+[Number]) on your Instagram/Facebook story
2️⃣ Send a quick message to 5 existing contacts reminding them about your products
3️⃣ Update or add a product — fresh listings attract more AI conversations

Your AI agent is ready and waiting. Let's get it working again. 💪

Visit your dashboard: https://safarichat.ai
```

**Implementation note:** For trial users, append:
```
⏳ Note: You have [N] trial days remaining — make them count.
```

---

#### Tier 2 — Day 10 Churned Win-Back

**Two sub-cases based on subscription status:**

##### 2a — Trial user, 10 days inactive

```
⚠️ *[Business Name], your SafariChat is idle*

It has been 10 days without any customer conversations on your account.

We want to help. Here is what businesses like yours did to turn things around:

✅ They shared their WhatsApp link in their bio → got 5–20 new leads in 48 hours
✅ They sent one broadcast to warm contacts → re-engaged 30% of them
✅ They added a promotional product with a discount → triggered immediate inquiries

Would you like us to walk you through a quick 5-minute setup?
Reply *YES* and a SafariChat advisor will guide you today.

Or reply *UPGRADE* to activate your plan and unlock full AI sales power.
```

##### 2b — Paid subscriber, 10 days inactive

```
🔴 *[Business Name] — your subscription is running but your AI is idle*

You are paying for SafariChat but no customer conversations have happened in 10 days.
That means your AI sales agent has had nothing to work on.

We do not want you paying for something that is not delivering results.

*Let us fix this together — for free:*
Reply *HELP* and a SafariChat advisor will personally review your setup and suggest exactly what to change.

Or if you would like us to pause your subscription while you are away:
Reply *PAUSE* and we will hold your billing until you are ready.

We are here to make sure SafariChat works for you. 🤝
```

---

#### Tier 2 — Abandoned (10 days inactive + WhatsApp disconnected)

This is the highest-urgency state. System sends **both** the win-back message above **and** a separate urgent reconnection alert (§3.3 disconnection alert) in the same message batch, prioritised above the daily cap.

```
🚨 *[Business Name] — your WhatsApp is disconnected AND your AI is idle*

Customers who message you are getting no response and leads are being lost every hour.

*Fix this in 2 steps:*

1️⃣ Reconnect WhatsApp: https://safarichat.ai → Settings → Scan QR Code
2️⃣ Share your number on one post to bring in new conversations

Reply *HELP* if you need hands-on assistance from our team.
```

---

#### Re-Engagement Tracking

When a business's `cs_daily_snapshots.total_conversations` goes from 0 → positive after an inactivity episode:

1. Fire `BusinessReEngaged` event
2. Record episode end in `cs_inactivity_episodes` (see §5.5)
3. Send a brief positive reinforcement message (only once per episode recovery):

```
🎉 *Welcome back, [Business Name]!*

Your AI just handled its first conversation after a few quiet days — great to see you active again.

Keep the momentum going: one social post today can bring in 5–10 more conversations this week.
```

---

#### Human Escalation for High-Value Churned Accounts

For paid subscribers still showing no engagement after the Day 10 win-back message AND no reply within 48 hours:

- Create an internal customer success task in `cs_escalations` table (status: `needs_human_followup`)
- Notify the CS team via internal Slack/email alert
- CS team manually calls or contacts the business — the WhatsApp conversation history and last active date are attached to the escalation record

This is not automated — it is a human handoff triggered by the system.

---

#### `cs_inactivity_episodes` Table

```sql
CREATE TABLE cs_inactivity_episodes (
    id                  BIGSERIAL PRIMARY KEY,
    business_id         BIGINT NOT NULL REFERENCES businesses(id),
    started_at          DATE NOT NULL,       -- first day of zero activity
    ended_at            DATE,                -- NULL if still inactive
    tier_reached        VARCHAR(20),         -- 'at_risk' | 'churned' | 'abandoned'
    day3_alert_sent_at  TIMESTAMP,
    day10_alert_sent_at TIMESTAMP,
    recovery_message_sent_at TIMESTAMP,
    escalated_at        TIMESTAMP,           -- NULL if not escalated
    created_at          TIMESTAMP DEFAULT NOW()
);
```

#### `cs_escalations` Table

```sql
CREATE TABLE cs_escalations (
    id              BIGSERIAL PRIMARY KEY,
    business_id     BIGINT NOT NULL REFERENCES businesses(id),
    episode_id      BIGINT REFERENCES cs_inactivity_episodes(id),
    reason          VARCHAR(100) NOT NULL,  -- 'paid_churned_10d', 'no_reply_winback'
    status          VARCHAR(30) DEFAULT 'needs_human_followup',
    assigned_to     BIGINT REFERENCES users(id),  -- CS team member
    notes           TEXT,
    resolved_at     TIMESTAMP,
    created_at      TIMESTAMP DEFAULT NOW()
);
```

---

## 4. CS Conversation State Machine

All reply-driven billing and package selection flows are managed by a single state machine.

### 4.1 `cs_conversation_sessions` Table

```sql
CREATE TABLE cs_conversation_sessions (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id),
    context         VARCHAR(50) NOT NULL,  -- 'trial_upgrade' | 'subscription_upgrade' | 'credit_purchase'
    state           VARCHAR(50) NOT NULL,  -- 'awaiting_package' | 'awaiting_payment' | 'completed' | 'expired'
    payload         JSONB DEFAULT '{}',    -- selected_package_id, invoice_id, amount, etc.
    expires_at      TIMESTAMP NOT NULL,    -- sessions expire after 30 minutes of inactivity
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);
```

### 4.2 State Transitions

```
[No session] → user replies to CS trigger message
      │
      ▼
context = 'trial_upgrade' | 'subscription_upgrade' | 'credit_purchase'
state   = 'awaiting_package' (or 'awaiting_amount' for credits)
      │
      ▼ (user sends package number / amount)
state = 'awaiting_payment'
payload.invoice_id = newly generated invoice
      │
      ▼ (payment webhook arrives OR manual confirmation)
state = 'completed'
      → fire SubscriptionActivated / SubscriptionUpgraded / CreditsAdded event
      │
      ▼ (if >30 min with no activity)
state = 'expired'
      → send: "Your session timed out. Reply UPGRADE to start again."
```

### 4.3 Inbound Message Routing — Instance Type Flag

> **Core principle:** The routing decision is made at the **instance level**, not the sender level. A `whatsapp_instances.instance_type` column declares what each instance is for. The webhook handler reads this flag and dispatches accordingly — no sender identity inspection required.

---

#### 4.3.1 Why Instance-Type Routing is the Right Architecture

All WebhooK calls from WaSender — regardless of which number received the message — arrive at the same controller method: `WaSenderController::handleWebhook($instanceId)`. By the time the code needs to decide what to do with an inbound message, `$instance` is already loaded from the database. Adding a type column means the routing decision is:

```php
if ($instance->instance_type === 'customer_success') {
    return $this->handleCsIncomingMessage($webhookData, $instance);
}
return $this->handleIncomingMessage($webhookData, $instance);   // existing sales AI path
```

This is a **zero-extra-query** branch — `$instance` is already in memory. It requires no inspection of the sender's phone number, no lookup in `users` table, no `SystemNumberWebhookRouter` class. The architecture stays in one place and is completely data-driven.

**Comparison against alternatives:**

| Approach | Extra DB queries | Complexity | Scales to new types | Single code path |
|---|---|---|---|---|
| Separate controller per instance type | 0 | High — duplicate event-switch logic | Poor | No |
| Sender identity inspection (old §4.3) | 1+ (users lookup) | High — conditional chains | Poor | No |
| **`instance_type` flag (this design)** | **0** | **Minimal — one if-branch** | **Yes — add enum value + handler** | **Yes** |

---

#### 4.3.2 The `instance_type` Column

```sql
-- Migration: add_instance_type_to_whatsapp_instances
ALTER TABLE whatsapp_instances
    ADD COLUMN instance_type VARCHAR(30) NOT NULL DEFAULT 'sales';

COMMENT ON COLUMN whatsapp_instances.instance_type IS
    '''sales'' = business AI agent instance
     ''customer_success'' = SafariChat system CS channel
     Future: ''support'', ''broadcast_only'', etc.';

-- Seed: mark the system OTP/CS instance
UPDATE whatsapp_instances
SET instance_type = 'customer_success'
WHERE user_id = (SELECT id FROM users WHERE is_system = TRUE LIMIT 1);
```

All existing instances keep `instance_type = 'sales'` (the default). Only the one system-owned instance is marked `customer_success`. This is a backfill-safe, non-breaking migration.

---

#### 4.3.3 Branch Point in `WaSenderController`

The single change to the existing controller — inside `handleWebhookByUuid` and `handleWebhook`, within the `messages.received` case:

```php
// BEFORE
case 'message':
case 'messages.received':
    return $this->handleIncomingMessage($webhookData, $instance);

// AFTER
case 'message':
case 'messages.received':
    if ($instance->instance_type === 'customer_success') {
        return $this->handleCsIncomingMessage($webhookData, $instance);
    }
    return $this->handleIncomingMessage($webhookData, $instance);
```

All other events (`status.update`, `qr.update`, `connection.ready`, `disconnected`) are instance-type agnostic — they continue using the existing handlers unchanged.

---

#### 4.3.4 The CS Inbound Handler — `handleCsIncomingMessage`

New private method added to `WaSenderController` (or delegated to `CsConversationHandler` directly):

```php
private function handleCsIncomingMessage(array $webhookData, WhatsappInstance $instance): JsonResponse
{
    // Skip self-sent messages (same guard as sales path)
    if (!empty($webhookData['fromMe'])) {
        return response()->json(['success' => true, 'message' => 'Self message ignored']);
    }

    $senderPhone = $webhookData['from'] ?? null;
    $messageBody = $webhookData['body'] ?? $webhookData['message']['body'] ?? '';

    if (!$senderPhone) {
        return response()->json(['success' => false, 'message' => 'No sender phone'], 400);
    }

    // Resolve to a registered SafariChat user by phone
    $user = User::where('phone', $senderPhone)
                ->orWhere('phone', ltrim($senderPhone, '+'))
                ->first();

    if (!$user) {
        // Unknown sender — not a SafariChat business owner
        Log::info('CS channel received message from unknown sender', ['phone' => $senderPhone]);
        $this->waSenderService->sendTextMessage(
            $instance,
            $senderPhone,
            "This is the SafariChat system channel. For support, visit https://safarichat.ai"
        );
        return response()->json(['success' => true]);
    }

    // Delegate entirely to the CS conversation handler
    app(CsConversationHandler::class)->handleInbound(
        user:        $user,
        message:     $messageBody,
        rawWebhook:  $webhookData,
        instance:    $instance,
    );

    return response()->json(['success' => true]);
}
```

---

#### 4.3.5 Inside `CsConversationHandler::handleInbound()`

Once the sender is confirmed as a registered `User`, `CsConversationHandler` applies the reply routing waterfall — **entirely within the CS context, never touching the sales AI path**:

```
Step 1: Active CS session for this user?
  cs_conversation_sessions WHERE user_id = $user->id
    AND state IN ('awaiting_package_selection', 'awaiting_payment_confirmation')
    AND expires_at > NOW()
  ──► YES:  continueSession($session, $message)
  ──► NO:   Step 2

Step 2: CS keyword in message?
  (UPGRADE / HELP / REPORT / PAUSE / BUY CREDITS / YES / NO)
  ──► YES:  startOrResume($user, $message)
  ──► NO:   Step 3

Step 3: Recent CS message sent to this user (last 24h)?
  cs_message_log WHERE user_id = $user->id AND sent_at > NOW() - INTERVAL '24 hours'
  ──► YES:  handleContextualReply($user, $message)  -- they're replying to something we sent
  ──► NO:   Step 4

Step 4: Default — send CS help menu
  "Reply UPGRADE to manage your plan, REPORT for your daily summary,
   HELP to speak with our team, or PAUSE to pause your subscription."
```

---

#### 4.3.6 Dual-Role Identity — Canonical Position

A person's SafariChat role depends **entirely on which instance received their message** — not who they are:

| Person messages... | Treated as | Handler |
|---|---|---|
| Business B's instance (`type = 'sales'`) | A customer lead of Business B | `AiWhatsAppService` scoped to Business B |
| System instance (`type = 'customer_success'`) | A SafariChat business owner | `CsConversationHandler` |
| System instance, not a registered user | Unknown / misdirected | Log + rejection notice |

The `business_contacts` record for a person in Business B's context is **never consulted** on the system instance. The `users` record for a person's own SafariChat account is **never consulted** on a `sales` instance. The two channels are fully isolated by design.

---

## 5. Implementation Components

### 5.1 New Service Classes

| Class | Responsibility |
|---|---|
| `App\Services\CustomerSuccess\CsConversationHandler` | State machine for all reply-driven CS flows |
| `App\Services\CustomerSuccess\DailyRecommendationResolver` | Picks the right daily recommendation based on business data |
| `App\Services\CustomerSuccess\OnboardingGapResolver` | Identifies what a user hasn't done yet for CTAs |
| `App\ Services\CustomerSuccess\CsMessageRenderer` | Renders message templates with dynamic variables; **locale-aware** — resolves `users.locale` → selects static template (en/sw) or calls AI translation for other supported locales; enforces English for unsupported locales |
| `App\Services\BillingService` (extend existing) | Pro-ration calculation helper; delegates invoice creation and payment link retrieval to `BillingApiController::upgradePlan()` |

### 5.2 New Jobs

| Job | Schedule |
|---|---|
| `SendDailyBusinessSummaryJob` | Daily 20:00 per user timezone |
| `SendTrialReminderJob` | Daily 09:00 for trial users |
| `TrialEndingWarningJob` | Every 15 min (checks if T-3h window) |
| `TrialExpiredJob` | Every 15 min (checks if trial_ends_at <= NOW()) |
| `UsageLimitApproachingJob` | Hourly (checks 80%/95% thresholds) |
| `CreditLowAlertJob` | Hourly (checks 20%/10% credit thresholds) |
| `BusinessInactivityMonitorJob` | Daily 08:00 (checks 3-day and 10-day inactivity tiers) |

### 5.3 New Events / Listeners

| Event | Listener |
|---|---|
| `WhatsappInstanceConnected` | `SendWelcomeMessageListener` |
| `ProductCreated` | `SendFirstProductGuideListener` |
| `SubscriptionActivated` | `SendSubscriptionSuccessMessageListener` |
| `SubscriptionUpgraded` | `SendUpgradeConfirmationListener` |
| `CreditsAdded` | `SendCreditConfirmationListener` |
| `BusinessReEngaged` | `SendReEngagementCelebrationListener` |
| `BusinessInactivityEscalated` | `CreateCsEscalationRecordListener`, `NotifyCsTeamListener` |

### 5.4 New Artisan Commands

| Command | Purpose |
|---|---|
| `cs:daily-summary` | Dispatch daily summary jobs for all eligible users |
| `cs:trial-reminders` | Dispatch trial reminder jobs |
| `cs:trial-monitor` | Monitor trial expiry (T-3h and T=0 windows) |
| `cs:usage-monitor` | Monitor usage thresholds and credit levels |
| `cs:inactivity-monitor` | Detect inactive/churned businesses and dispatch re-engagement alerts |

### 5.5 Database Changes Required

| Table / Column | Purpose |
|---|---|
| **`whatsapp_instances.instance_type`** | `'sales'` (default) or `'customer_success'` — determines routing at the webhook level. This is the **primary routing key** for the entire CS channel. |
| `users.cs_welcome_sent_at` | Prevent duplicate welcome messages |
| `users.cs_first_product_message_sent_at` | Prevent duplicate first-product messages |
| `users.cs_trial_reminder_last_sent_at` | Deduplicate daily trial reminders |
| `cs_conversation_sessions` | CS billing conversation state (see §4.1) |
| `cs_daily_snapshots` | Daily snapshot of lead counts per business for delta comparison |
| `cs_inactivity_episodes` | Tracks each inactivity episode per business (start, tier, alerts sent, recovery) |
| `cs_escalations` | Human handoff queue for high-value churned accounts |
| `billing_invoices` (if not exists) | Store generated invoices for upgrade/credit purchases |

**Migration for `instance_type`:**

```php
// database/migrations/YYYY_MM_DD_add_instance_type_to_whatsapp_instances.php
public function up(): void
{
    Schema::table('whatsapp_instances', function (Blueprint $table) {
        $table->string('instance_type', 30)
              ->default('sales')
              ->after('status')
              ->comment('sales | customer_success');
    });

    // Mark the system-owned instance as the CS channel
    $systemUserId = config('safarichat.system_user_id');
    if ($systemUserId) {
        DB::table('whatsapp_instances')
          ->where('user_id', $systemUserId)
          ->update(['instance_type' => 'customer_success']);
    }
}

public function down(): void
{
    Schema::table('whatsapp_instances', function (Blueprint $table) {
        $table->dropColumn('instance_type');
    });
}
```

### 5.6 `cs_daily_snapshots` Table

```sql
CREATE TABLE cs_daily_snapshots (
    id              BIGSERIAL PRIMARY KEY,
    business_id     BIGINT NOT NULL REFERENCES businesses(id),
    snapshot_date   DATE NOT NULL,
    total_conversations INT DEFAULT 0,
    new_prospects   INT DEFAULT 0,
    active_leads    INT DEFAULT 0,
    converted       INT DEFAULT 0,
    churned         INT DEFAULT 0,
    interested      INT DEFAULT 0,
    engaged         INT DEFAULT 0,
    created_at      TIMESTAMP DEFAULT NOW(),
    UNIQUE (business_id, snapshot_date)
);
```

Data written by `SendDailyBusinessSummaryJob` before sending the report, then used as `yesterday` baseline the following day.

---

## 6. Payment Provider Integration Matrix

| Provider | Trigger | Confirmation method | Applicable to |
|---|---|---|---|
| **UCN (Tanzania mobile money)** | Manual — user sends screenshot | Billing team confirms manually via admin panel → fires event | All Tanzanian users |
| **Stripe** | Payment Intent created per invoice | `stripe/webhook` endpoint → `StripeWebhookController` | All users (card payments) |
| **Flutterwave** | Hosted payment link per invoice | `flutterwave/webhook` endpoint → `FlutterwaveWebhookController` | Africa (non-TZ mobile) |

All three methods must call the same internal event (`SubscriptionActivated` / `SubscriptionUpgraded` / `CreditsAdded`) on successful confirmation so downstream CS messages are provider-agnostic.

---

## 7. Message Delivery Rules

| Rule | Detail |
|---|---|
| **Delivery channel** | **Sender:** system number (OTP number) → **Recipient:** business owner's registered phone number. Never use the business's own connected WhatsApp as the sender for CS messages. |
| **Message language** | Resolved from `users.locale`. Tier 1 (en/sw): static templates. Tier 2 (ar/es/fr/hi/pt-br): AI-translated from English source. Unsupported locales: enforce English. See §11. |
| **Schema name** | `users.uuid` — resolved by `WaSenderService::resolveSchemaName()` |
| **Retry on failure** | 3 retries with exponential backoff; after 3 failures log to `cs_message_failures` and skip |
| **Deduplication** | All CS messages check a sent-flag or `cs_daily_snapshots` before dispatching |
| **Do Not Disturb** | No CS messages sent between 22:00 and 07:00 local time; defer to 07:30 |
| **Disconnected instance** | Queue message with `send_after = reconnect_time`; check on `WhatsappInstanceConnected` event |
| **Max messages/day** | Hard cap of 4 CS messages per business per day to prevent spam perception |

---

## 8. CS Message Log

All CS-originated messages must be recorded in a `cs_message_log` table for auditability and deduplication:

```sql
CREATE TABLE cs_message_log (
    id          BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL REFERENCES businesses(id),
    user_id     BIGINT NOT NULL REFERENCES users(id),  -- owner of the business
    type        VARCHAR(100) NOT NULL,  -- 'welcome', 'first_product', 'daily_summary',
                                        --  'trial_reminder', 'trial_warning', 'trial_expired',
                                        --  'subscription_success', 'upgrade_nudge', 'credit_low'
    sent_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    delivered   BOOLEAN DEFAULT FALSE,
    metadata    JSONB DEFAULT '{}'
);
```

---

## 9. Success Metrics (KPIs to Track)

| Metric | Target | Measurement |
|---|---|---|
| Time to first product created | < 24h from QR connect | `products.created_at - whatsapp_instances.first_connected_at` |
| Day-7 retention (still connected) | > 70% | % users with `connected_at` > 7 days ago still connected |
| Trial → Paid conversion | > 25% | `subscription_status` changed from `trial` to `active` |
| Daily report open/reply rate | > 30% reply engagement | Track replies to report messages |
| Upgrade via CS conversation | > 15% of upgrade events | `billing_invoices.source = 'cs_conversation'` |
| Credit top-up initiated via CS | > 40% of top-ups | `credits_purchases.source = 'cs_alert'` |

---

## 10. Rollout Phases

### Phase 1 (Weeks 1–2) — Core Onboarding
- §3.1 Welcome message on QR connect
- §3.2 First product created message
- Database columns: `cs_welcome_sent_at`, `cs_first_product_message_sent_at`

### Phase 2 (Weeks 3–4) — Daily Reports
- §3.3 Daily summary report job + recommendation engine
- `cs_daily_snapshots` table
- `DailyRecommendationResolver`

### Phase 3 (Weeks 5–6) — Trial Lifecycle
- §3.4 Daily countdown reminder
- §3.5 T-3h and T=0 expiry warnings
- §3.6 Full conversational billing flow (packages → payment methods)
- `cs_conversation_sessions` table + `CsConversationHandler`

### Phase 4 (Weeks 7–8) — Expansion & Retention
- §3.7 Subscription success message
- §3.8 Usage overage upgrade flow with pro-rated invoice
- §3.9 Credit low alerts and purchase flow
- Full payment provider webhook integration (Stripe, Flutterwave, manual UCN)

### Phase 5 (Weeks 9–10) — Churn Prevention
- §3.10 `BusinessInactivityMonitorJob` + Day 3 at-risk nudge
- §3.10 Day 10 churned win-back (trial and paid variants)
- §3.10 Abandoned state combined alert
- §3.10 `BusinessReEngaged` event + recovery celebration message
- §3.10 `cs_inactivity_episodes` and `cs_escalations` tables
- Human escalation pipeline for paid churned accounts (CS team notifications)

---

*End of Customer Success Design Document*

---

## 11. CS Language Strategy

### 11.1 Platform Scope

SafariChat is used **worldwide**. The `resources/lang/` directory confirms active translations for:

| Locale code | Language | Translation files |
|---|---|---|
| `en` | English | Full coverage |
| `sw` | Swahili | Full coverage (billing, alerts, upgrade, auth, etc.) |
| `ar` | Arabic | Present |
| `es` | Spanish | Present |
| `fr` | French | Present |
| `hi` | Hindi | Present |
| `pt-br` | Brazilian Portuguese | Present |

The platform also stores `users.locale` (default `'en'`), AI agent `primary_language`, `conversations.language_detected`, and `business_contacts.preferred_language` — making full per-user language resolution possible.

---

### 11.2 Language Resolution for CS Messages

`CsMessageRenderer` resolves the language for every CS message using this priority chain:

```
Priority 1: users.locale          (explicit preference set by user)
Priority 2: ai_sales_agents.primary_language  (the language the business configured for their agent)
Priority 3: most recent conversations.language_detected  (inferred from their customer conversations)
Priority 4: default → 'en'
```

Resolution is performed once per message dispatch and cached in `cs_message_log.metadata.locale_used`.

---

### 11.3 Template Coverage Tiers

| Tier | Locales | How CS messages are rendered |
|---|---|---|
| **Tier 1 — Static templates** | `en`, `sw` | Full pre-written CS message templates exist for both languages. All §3.x message bodies must have a Swahili variant alongside the English. |
| **Tier 2 — AI-translated** | `ar`, `es`, `fr`, `hi`, `pt-br` | No static CS templates. `CsMessageRenderer` calls the AI (same LLM used for sales agent) to translate the English template into the resolved locale before sending. The source English message is always the translation input. |
| **Tier 3 — Unsupported locale** | anything else (e.g., `zh`, `de`) | Enforce English. Log `cs_message_log.metadata.locale_fallback = true`. |

---

### 11.4 Enforcing Language — Rules

1. **Never send an English-only message to a user with `locale = 'sw'`** — Swahili templates are mandatory for all Tier 1 locales before a CS feature ships.

2. **AI translation must include a quality guard**: the translate prompt instructs the model to preserve WhatsApp bold/italic formatting (`*text*`, `_text_`), emojis, and payment amounts verbatim. Numbers and links are never translated.

3. **CS template registration**: every new CS message type added in future must register templates for `en` AND `sw` before it can be dispatched. If only an `en` template exists and user locale is `sw`, `CsMessageRenderer` falls back to AI translation with a warning log — it does NOT silently send English.

4. **Billing-sensitive messages** (invoice amounts, payment links, package names) are always rendered from the billing API response, not from translation files — amounts are locale-formatted (TZS X,XXX,XXX) regardless of user language.

5. **`users.locale` as the enforced source of truth**: if `users.locale` is `null` or unrecognised, set it to `'en'` at resolution time and persist the update so future messages are consistent.

---

### 11.5 Swahili Template Requirement

For every CS message in §3.x, a Swahili variant is required. Example for §3.1 Welcome:

```
🎉 *Karibu SafariChat, [Jina la Biashara]!*

WhatsApp yako ipo hai na wakala wako wa mauzo wa AI yuko tayari.

Hapa ndipo uanzie ✅

👉 *Unda bidhaa au huduma yako ya kwanza*
Nenda: [Dashboard Link] → Bidhaa → Ongeza Bidhaa

*Kwa nini hii ni muhimu:*
Wakala wako wa AI anatumia orodha yako ya bidhaa kujibu maswali ya wateja, kupendekeza vitu, na kutambua wateja wanaoweza kununua. Bila bidhaa, AI hana kitu cha kuuza.

Biashara zinazoweka bidhaa yao ya kwanza ndani ya masaa 24 ya kuunganisha zinaona *wateja 3× zaidi waliokusanywa katika wiki yao ya kwanza.*

Jibu *MSAADA* wakati wowote kupata usaidizi.
```

Swahili templates for all other §3.x messages follow the same pattern and are stored in `resources/lang/sw/cs_messages.php` (new file to be created as part of Phase 1).

---

### 11.6 Database — No New Migration Required

`users.locale` already exists (migration `2026_03_09_102210_add_locale_to_users_table.php`, default `'en'`). No additional schema change is needed for language support. The `locale_used` and `locale_fallback` fields are stored in the existing `cs_message_log.metadata` JSONB column.
