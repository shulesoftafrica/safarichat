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
* Any customer engagement, just recorded and owner get notified for the loss

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
* Full features enabled with these limitations
      a) only 10 contacts will be allowed
      b) only 1 product is allowed
      c) only 50 outgoing messages 
* Credits available
* Automations active
* No payment required
* business validation before approval (we shall use llm, user will upload a valid business license and tax then if confidence is above 80% we automatically approve the business)

**Trial Limits Enforced:**
* Contact limit enforced on every WhatsApp message received
* Product limit enforced on product creation attempts
* Outgoing message limit enforced on every outbound message

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

Neutral. Professional. No blame. but fixed, never loaded from AI

---

#### Owner receives (instant notification):

> “⚠ A customer named *John* tried to buy a product,
> but SafariChat could not respond because your subscription is inactive.”

This is one of the **strongest anti-churn triggers**.

---

## 4. TRIAL LIMIT ENFORCEMENT (Critical for Revenue Protection)

### 4.1 Contact Limit Control (10 Contacts Max)

#### On WhatsApp Message Received:

**System Process:**
1. Message arrives from customer
2. System checks if contact exists in leads/events_guest table
3. If new contact AND trial user has ≥10 contacts:
   - **DO NOT** execute AI response
   - **DO NOT** store new contact
   - Send blocking notification to owner

#### Owner Notification (Immediate):

> **⚠ Contact Limit Reached**
> 
> A new customer tried to reach you, but you've hit your 10-contact trial limit.
> 
> **Customer Details:**
> Name: [Customer Name]
> Number: [WhatsApp Number]
> Message: [First 50 characters...]
> 
> **Upgrade now to:**
> • Accept unlimited contacts
> • Never miss a customer again
> • Keep your business growing
> 
> [UPGRADE TO STARTER - 69,000 TZS/month]

This creates **immediate urgency** and **fear of lost opportunity**.

---

### 4.2 Product Limit Control (1 Product Max)

#### On Product Creation Attempt:

**Frontend Validation:**

When user clicks "Add Product" and already has 1 product:

**Modal appears:**

> **⚠ Product Limit Reached**
> 
> You've reached your 1-product trial limit.
> 
> **Upgrade to add more products:**
> • Starter: Up to 5 products
> • Pro: Up to 50 products  
> • Premium: Up to 200 products
> 
> [UPGRADE NOW]

**"Add Product" button becomes disabled** with tooltip:

> "Upgrade to add more products"

---

### 4.3 Outgoing Message Limit Control (50 Messages Max)

#### On Outgoing Message Attempt:

**System Process:**
1. User/AI tries to send outgoing message
2. System checks message count for current billing period
3. If count ≥ 50 messages:
   - **Block message sending**
   - Log as BLOCKED_MESSAGE
   - Notify owner immediately

#### Owner Notification (Immediate):

> **⚠ Message Limit Reached**
> 
> You've sent 50 messages this month (trial limit reached).
> 
> **Last message blocked:**
> To: [Customer Name]
> Message: [First 50 characters...]
> 
> **Upgrade now to send unlimited messages:**
> • Never miss a sale again
> • Keep conversations flowing
> • Grow your business without limits
> 
> [UPGRADE TO STARTER - 69,000 TZS/month]

#### Customer Experience:

If customer is waiting for a response and message is blocked:

**Customer receives:**
> "Thank you for your message. We'll get back to you shortly! 🙏"

**Owner gets additional context:**
> "Customer [Name] is waiting for your response, but your trial message limit is reached."

---

### 4.4 Limit Reset Rules

**Contact Limit:**
- Resets immediately upon subscription upgrade
- Historical contacts remain accessible

**Product Limit:**
- Increases immediately upon subscription upgrade
- Existing products remain active

**Message Limit:**
- Resets on subscription start date each month
- Blocked messages can be viewed in dashboard

---

### 4.5 Dashboard Limit Visibility

**Trial Dashboard Header:**
```
🟡 TRIAL ACCOUNT
Contacts: 8/10  |  Products: 1/1  |  Messages: 47/50
[UPGRADE NOW]
```

As limits approach, colors change:
- Green (0-70%)
- Yellow (71-90%) 
- Red (91-100%)

---

## 5. AUTOMATION & CRONJOB CONTROL (Very Important)

SafariChat runs follow-ups, reminders, qualification, and support tasks via cronjobs.

### 5.1 When Subscription is Active

* Cronjobs execute normally
* Follow-ups sent
* Qualifications run
* Reminders delivered

---

### 5.2 When Subscription is Inactive

Cronjobs **still run**, but instead of executing:

* Task is logged as **MISSED_AUTOMATION**
* Task is NOT sent
* Owner is notified

#### Example notification:

> “⚠ Missed Follow-Up
> SafariChat was supposed to follow up with *Mary* today,
> but your subscription is inactive.”

---

### 5.3 Daily Summary (High Impact)

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

### 5.4 After Reactivation

Once payment is made:

* Automations resume immediately
* Optionally show:

  > “SafariChat resumed and processed pending tasks.”

This creates a **relief moment** and reinforces value.

---

## 6. CREDIT SYSTEM RULES (VERY IMPORTANT)

### 6.1 Credit Basics

* 1 Credit = 1 TZS (base currency)
* Internally: credits are deducted based on tokens used
* Customers never see tokens

---

### 6.2 Credit Rules

* Credits are included in subscription plans
* Credits can be topped up anytime
* Credits **roll over**
* Credits **do NOT expire**
* Credits are **frozen if subscription is inactive**

---

### 6.3 Why Credits Freeze (Not Expire)

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

## 7. PACKAGE DIFFERENTIATION (So Subscriptions Matter)

Subscriptions must unlock **capability**, not just credits.

### Starter : Starter — 69,000 / month

* 50 contacts
* 5 products
* 1 AI agent WhatsApp channel
* No Customer Followups
* No customer categorization
* 60,000 credits
* Credits can roll over ONLY if subscription renews


### Pro: Pro — 149,000 / month

* 150 contacts
* 50 products
* 3 Ai Agent WhatsApp channels
* Customer Followups
* Customer Categorization
* 150,000 credits
* Sales insights Reports
* Credits rollover on renewal


### Premium : Premium — 299,000 / month

* 500 contacts
* 200 products
* 10 AI agents WhatsApp channels
* Customer Followups
* Customer Categorization
* 350,000 credits
* Customer Bookings Calenders
* Credits rollover on renewal
* Sales insights Reports



Credits alone **cannot unlock these features**.

---

## 8. PAYMENT METHODS & CURRENCY STRATEGY

### 8.1 Base Currency

* Canonical pricing stored in **TZS**

---

### 8.2 Tanzania Payments

* Lipa Namba (Control Number)


---

### 8.3 International Payments

* Stripe (USD only initially)
* flutterwave
* Price converted at checkout
* Exchange rate snapshot stored per order
* Price locked for limited time (e.g. 15 minutes)

This avoids FX volatility disputes.

---

## 9. CHURN REDUCTION MECHANISMS (BUILT-IN)

### 9.1 Loss Visibility

* Show missed customers
* Show missed automations
* Show frozen credits

---

### 9.2 Switching Cost (Soft)

* Conversation history retained
* AI trained on their data
* Automations configured
* Re-setup elsewhere feels painful

---

### 9.3 Incentives

* Annual plans with discount

---

## 10. WHAT USERS CAN DO WHEN EXPIRED

| Action                 | Allowed     |
| ---------------------- | ----------- |
| Login                  | ✅           |
| View dashboard         | ✅ (blurred) |
| View contacts/products | ✅ (blurred) |
| Export data            | ❌           |
| AI respond             | ❌           |
| Automations            | ❌           |
| Use credits            | ❌           |
| Pay & reactivate       | ✅           |

This balance minimizes rage-churn.

---

## 11. FINAL SYSTEM RULE (VERY CLEAR)

> **No subscription = No AI work.
> Credits enhance value, but subscription unlocks the system.**

This is the backbone that protects SafariChat revenue while remaining fair and SME-friendly.

---







