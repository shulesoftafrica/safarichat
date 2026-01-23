# Implementation Steps

## Workflow Design: AI-to-Job Responsibility Mapping

This section explains how AI systems perform key customer success roles, clarifying the link between job descriptions and technical implementation.

## Job Description -customer success

a)-Onboarding : define what "success" looks like for that specific customer. You create a Success Plan that maps their business goals (e.g., "reduce churn by 10%") to your product's features.
b) -upsell
c) -cros-sell
d) -Usage & Adoption Tracking
e) -Support & Issue Resolution
f) -Feedback Collection
g) -Retention
h) -Health Scoring
---

### 1.1 Incoming Requests Operations (Reactive Customer Support)

**Simulated Human Role:** Senior Customer Support Specialist  
**Objective:** Efficiently resolve real-time, user-initiated support requests. This ideally cover job description (e)

#### Technical Implementation

- All inbound messages are processed by a Unified Message Ingestion Service.
- Each request is enriched with:
    - Tenant (school) context
    - User role and permissions
    - Historical interactions
    - Product usage data

#### AI Responsibilities

- Understand user intent using NLP
- Classify request type (support, how-to, complaint, billing, upgrade, bug, password reset) 
- if request is BUG related, forward this to technical team
- if request is out of Bug and not related to password reset but require knowledge based, proceed
- Retrieve relevant knowledge (RAG: docs, FAQs, past tickets)
- Generate contextual, role-appropriate responses 
- Decide on resolution or escalation based on confidence score
- Save a request to database server
- Respond to end user and encourage user to respond if well satisfied or not
- Once user close the ticked, share a feedback link for user to give us a review

#### Endpoints

- **Message Endpoint:**  
    - Channels: WhatsApp, Web Chat, Email  
    - Response: Conversational, detailed, step-by-step  
    - Tasks: Troubleshooting, feature explanation, workflow guidance, ticket management

- **Phone–SMS Endpoint:**  
    - Channel: SMS  
    - Response: Short, precise, action-oriented  
    - Tasks: Quick queries, confirmations, alerts, links

---

### 1.2 Background Cron Operations (Proactive Customer Success)

**Simulated Human Role:** Customer Success Manager / Analyst  
**Objective:** Proactively monitor customers, identify risks/opportunities, and act without user prompts.
**tasks handled** (upsell,cros-sell,Usage & Adoption Tracking,-Feedback Collection, Retention
Health Scoring)

#### Technical Implementation

**A. Data Sources**

- Cron jobs aggregate data from:
    - `usage_events` (feature usage)
    - `sessions` (login activity)
    - `billing` (plan, payments)
    - `support_tickets` (volume, sentiment)
    - `feedback` (NPS, comments)
    - `school_profile` (size, modules)

**B. Aggregate Usage Analysis** -TASK -1

**B. Aggregate Usage Analysis** -TASK -1

To determine usage, the system compares each enabled module against actual user activity:

- For every module enabled for a school, count the number of unique active users and total events (actions) within the analysis period (e.g., last 30 days).
- Normalize usage by dividing the number of active users per module by the total number of potential users, providing a usage rate for each module.
- Identify underutilized modules (e.g., enabled but low or zero active users/events).
- This analysis highlights adoption gaps and helps prioritize follow-up actions.

```json
{
    "school_id": "SCH_1023",
    "period": "last_30_days",
    "enabled_modules": ["academics", "finance", "attendance", "payroll"],
    "usage_summary": {
        "academics": {"active_users": 12, "events": 340},
        "finance": {"active_users": 2, "events": 18},
        "attendance": {"active_users": 10, "events": 210},
        "payroll": {"active_users": 0, "events": 0}
    }
}
```

**C. Behavior Trend Analysis**

- Detect changes over time (7/30/90 days)
- Identify growth, decline, spikes, drops

```json
{
    "school_id": "SCH_1023",
    "login_trend": {
        "last_30_days": 120,
        "previous_30_days": 210,
        "trend": "declining",
        "change_pct": -42
    },
    "feature_trends": {
        "finance": "flat",
        "attendance": "stable",
        "academics": "declining"
    }
}
```

**D. Historical Data Analysis**

- Learn from past churn/upgrades

```json
{
    "school_id": "SCH_1023",
    "history": {
        "was_churned": false,
        "last_upgrade_date": "2024-11-12",
        "usage_before_upgrade": {
            "finance_events": 150,
            "attendance_events": 300
        }
    }
}
```

#### AI Responsibilities

**1. Customer Health Score Calculation**

- Weighted by:
    - Usage consistency (40%)
    - Feature coverage (20%)
    - Support sentiment (15%)
    - Payment behavior (15%)
    - Engagement trend (10%)

```json
{
    "school_id": "SCH_1023",
    "health_score": 58,
    "status": "at_risk",
    "drivers": ["low finance usage", "declining logins"]
}
```

- Used in dashboards, churn prediction, escalation, upsell filters

**2. Adoption Gap & Churn Risk Actions**

- AI follows a decision matrix:

| Condition                        | AI Action                        |
|-----------------------------------|----------------------------------|
| Low usage + healthy sentiment     | Trigger in-app guidance          |
| Low usage + confusion signals     | Auto-schedule training           |
| Declining usage + negative sentiment | Escalate to human CS         |
| Inactivity > threshold            | Send re-engagement message       |

```json
{
    "school_id": "SCH_1023",
    "risk": "churn",
    "action": "escalate_to_human",
    "reason": "declining usage + negative feedback"
}
```

**3. Upsell & Cross-Sell Handling**

- AI routes opportunities:

| Opportunity Type      | AI Action                |
|----------------------|--------------------------|
| Simple upgrade       | Hand to AI Sales Agent   |
| Complex/high value   | Notify human sales       |
| Not ready            | Store & monitor          |

```json
{
    "school_id": "SCH_1023",
    "opportunity": "upgrade_to_premium",
    "confidence": 0.82,
    "action": "route_to_ai_sales_agent"
}
```

**Outputs:**

- Real-time health dashboards
- Task queues for AI agents
- Escalation tickets for humans
- Stored intelligence for future use
- Pre-generated, context-aware messages

---

### 1.3 Background Queue Operations (Follow-Through & Automation)

**Simulated Human Role:** Customer Success Operations Officer  
**Objective:** Execute follow-up actions triggered by events or AI decisions.

#### Technical Implementation

- Event-driven architecture (message queues, job workers)
- Triggers: user actions, system events, AI decisions

#### AI Responsibilities

- Execute workflows
- Send targeted messages
- Update CRM/ticket states
- Log outcomes for learning/auditing

**Examples:**

- Send onboarding reminders after inactivity
- Trigger upgrade prompts after feature milestones
- Follow up on negative feedback
- Notify human CS for escalations
- Map requirements to API contracts
- Convert workflows into technical designs and user stories
