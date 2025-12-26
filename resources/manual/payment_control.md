Below is a **clear, end-to-end Subscription & Payment Control Design** for SafariChat, distilled from everything we’ve discussed.
This is written as a **product + system design document**, not theory — you can hand this to your team and implement it.

---

# SAFARICHAT

## Subscription & Payment Control Design

**Goal:**
Protect revenue, grow predictable MRR, minimize churn, and keep payment experience extremely easy for SMEs.

---

## 1. CORE BUSINESS PRINCIPLES (Non-Negotiable)

### 1. Subscription = ACCESS

Without an active subscription:

* AI **cannot work**
* Automations **cannot execute**
* Credits **cannot be consumed**
* Sales & support **pause**

Subscription is the **gatekeeper**.

---

### 2. Credits = CONSUMPTION

Credits:

* Measure AI usage (internally based on tokens)
* Are **not** a replacement for subscription
* Can roll over
* Can be topped up
* Are **frozen** if subscription is inactive

---

### 3. Make Loss Visible, Not Punitive

We don’t punish users.
We **show them what they are losing** (missed customers, missed follow-ups, frozen automations).

This reduces churn more effectively than hard blocks.

---

## 2. SUBSCRIPTION LIFECYCLE

### 2.1 Free Trial

* Every new account gets **3 days free trial**
* Full features enabled
* Credits available
* Automations active
* No payment required

**Goal:** Prove value before charging.

---

### 2.2 Active Subscription

When subscription is active:

* AI sales agent works
* AI support agent works
* Automations (cronjobs) run
* Credits are consumed normally
* User can top-up credits anytime
* Credits roll over month-to-month

---

### 2.3 Subscription Expired (Critical State)

When subscription expires:

#### What happens immediately:

* User can still **log in**
* Dashboard is **visible but blurred**
* Actions are blocked by modal
* Credits are **frozen (not deleted)**
* Automations stop executing
* AI stops responding to customers

This is a **soft lock**, not a hard lock.

---

## 3. PAYMENT ENFORCEMENT MECHANISMS

### 3.1 Login Enforcement (Owner Side)

On login with expired subscription:

**Full-screen blocking modal appears**

* Dashboard visible in background (blurred)
* Clear message:

  > “Your AI Sales Agent is paused because your subscription is inactive.”

Modal includes:

* Lipa Namba (Tanzania) – copyable
* Stripe payment button (international)
* Remaining credits shown (loss aversion)
* “Reactivate Now” CTA
* Small text: “Your data is safe. Credits will resume after payment.”

**Psychology used:**

* Loss aversion
* Visibility of value
* Ease of payment
* Low frustration

---

### 3.2 Customer-Facing Enforcement (External)

If a customer sends a message while subscription is inactive:

#### Customer receives:

> “Hello! The business is temporarily unavailable at the moment.
> Please try again shortly 🙏.”

Neutral. Professional. No blame.

---

#### Owner receives (instant notification):

> “⚠ A customer named *John* tried to buy a product,
> but SafariChat could not respond because your subscription is inactive.”

This is one of the **strongest anti-churn triggers**.

---

## 4. AUTOMATION & CRONJOB CONTROL (Very Important)

SafariChat runs follow-ups, reminders, qualification, and support tasks via cronjobs.

### 4.1 When Subscription is Active

* Cronjobs execute normally
* Follow-ups sent
* Qualifications run
* Reminders delivered

---

### 4.2 When Subscription is Inactive

Cronjobs **still run**, but instead of executing:

* Task is logged as **MISSED_AUTOMATION**
* Task is NOT sent
* Owner is notified

#### Example notification:

> “⚠ Missed Follow-Up
> SafariChat was supposed to follow up with *Mary* today,
> but your subscription is inactive.”

---

### 4.3 Daily Summary (High Impact)

Every morning (e.g. 8am), if subscription inactive:

> **Daily Missed Automations Report**
>
> * 3 follow-ups not sent
> * 1 customer qualification missed
> * 2 reminders not delivered
>
> Total customers at risk: **6**

This creates **accumulated pain**, which drives renewal.

---

### 4.4 After Reactivation

Once payment is made:

* Automations resume immediately
* Optionally show:

  > “SafariChat resumed and processed pending tasks.”

This creates a **relief moment** and reinforces value.

---

## 5. CREDIT SYSTEM RULES (VERY IMPORTANT)

### 5.1 Credit Basics

* 1 Credit = 1 TZS (base currency)
* Internally: credits are deducted based on tokens used
* Customers never see tokens

---

### 5.2 Credit Rules

* Credits are included in subscription plans
* Credits can be topped up anytime
* Credits **roll over**
* Credits **do NOT expire**
* Credits are **frozen if subscription is inactive**

---

### 5.3 Why Credits Freeze (Not Expire)

If subscription expires:

* Credits remain in account
* But cannot be used

Message shown:

> “You have 24,800 credits waiting.
> Reactivate your subscription to use them.”

This:

* Prevents abuse
* Protects MRR
* Avoids anger
* Encourages fast renewal

---

## 6. PACKAGE DIFFERENTIATION (So Subscriptions Matter)

Subscriptions must unlock **capability**, not just credits.

### Starter

* Limited contacts
* Limited products
* 1 AI agent
* Sales only
* WhatsApp channel

### Pro

* More contacts & products
* Multiple AI agents
* Sales + Support
* Follow-ups & automation
* Multiple channels

### Premium

* High limits
* Multi-agent (Sales + Support + Collections)
* Advanced automations
* Priority AI processing
* Team inbox

Credits alone **cannot unlock these features**.

---

## 7. PAYMENT METHODS & CURRENCY STRATEGY

### 7.1 Base Currency

* Canonical pricing stored in **TZS**

---

### 7.2 Tanzania Payments

* Lipa Namba (Control Number)
* Mobile Money
* Bank channels

---

### 7.3 International Payments

* Stripe (USD only initially)
* Price converted at checkout
* Exchange rate snapshot stored per order
* Price locked for limited time (e.g. 15 minutes)

This avoids FX volatility disputes.

---

## 8. CHURN REDUCTION MECHANISMS (BUILT-IN)

### 8.1 Loss Visibility

* Show missed customers
* Show missed automations
* Show frozen credits

---

### 8.2 Switching Cost (Soft)

* Conversation history retained
* AI trained on their data
* Automations configured
* Re-setup elsewhere feels painful

---

### 8.3 Incentives

* “Renew within 24 hours and get bonus credits”
* Annual plans with discount
* Occasional grace window (very limited)

---

## 9. WHAT USERS CAN DO WHEN EXPIRED

| Action                 | Allowed     |
| ---------------------- | ----------- |
| Login                  | ✅           |
| View dashboard         | ✅ (blurred) |
| View contacts/products | ✅           |
| Export data            | ❌           |
| AI respond             | ❌           |
| Automations            | ❌           |
| Use credits            | ❌           |
| Pay & reactivate       | ✅           |

This balance minimizes rage-churn.

---

## 10. FINAL SYSTEM RULE (VERY CLEAR)

> **No subscription = No AI work.
> Credits enhance value, but subscription unlocks the system.**

This is the backbone that protects SafariChat revenue while remaining fair and SME-friendly.

---

## 11. WHY THIS DESIGN WORKS

* Predictable MRR
* High renewal pressure without anger
* Strong psychological switching costs
* Clear mental model for SMEs
* Simple to explain
* Simple to enforce technically
* Scales globally

This is the **same pattern used by top SaaS companies**, adapted perfectly for African SMEs.

---

If you want, next I can:

* Convert this into a **technical flow diagram**
* Write **exact modal copy & notification templates**
* Draft **backend state machine logic**
* Prepare a **Terms & Fair Usage section**

Tell me which one you want next.
