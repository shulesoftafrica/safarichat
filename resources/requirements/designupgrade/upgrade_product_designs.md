# SafariChat Reassessed Requirements (Product-Centric Growth)

Date: 2026-07-06

## 1. Purpose

Define the next business-critical feature set for SafariChat based on what is already implemented in this project, with no duplicate recommendations and no low-impact "nice-to-have" scope.

## 2. Current Project Reality (Already Implemented)

The following capabilities already exist and should NOT be re-specified as new features:

1. Product management module exists (CRUD, pricing/service fields, FAQs, attachments, status, ownership).
2. Contact uniqueness already exists at business level using phone identity constraints.
3. Lead-to-product relationship exists through lead_products (multi-product association per lead).
4. Campaign module exists (create/list/pause/resume/report/clone/delete with message analytics).
5. Appointment module exists (book/confirm/cancel/complete/no-show/reschedule).
6. AI Sales Agent module exists (single agent per user with configuration and lifecycle).
7. WhatsApp delivery orchestration exists (Meta/WaSender routing and queue architecture).

## 3. Business Gap To Solve

The main gap is not missing modules. The main gap is missing product-level execution consistency across the sales lifecycle.

Today, product context exists in products and lead-product mappings, but key revenue flows are still partially generic:

1. Campaigns are not enforced as product-bound at schema/workflow level.
2. Appointment outcomes are not explicitly tied to product context for pipeline attribution.
3. Management reporting is not centered on product funnel performance from outreach to conversion.
4. Proposal flow exists as statuses, but not as a structured, stored proposal artifact workflow.

## 4. What Will Be Built

Build a Product-Centric Revenue Execution Layer on top of existing modules. This is an upgrade project, not a rewrite.

Scope is limited to 4 capabilities:

1. Product-Bound Campaign Execution
2. Product-Attributable Appointment & Pipeline Tracking
3. Product Performance Command Dashboard
4. Structured Proposal Workflow

## 5. Functional Requirements (Non-Duplicate)

### FR-A: Product-Bound Campaign Execution

1. Every campaign must reference one product_id at creation time.
2. Recipient selection for a campaign must include only leads/contacts mapped to that product.
3. Campaign report must expose product-level outcomes: sent, delivered, read, replied, failed, meetings booked, deals progressed.
4. Cloning a campaign must preserve product context unless user explicitly changes it.
5. Guardrail: no campaign launch if product has zero eligible recipients.

Business value: prevents cross-product mis-targeting and increases relevance/conversion.

### FR-B: Product-Attributable Appointment & Pipeline Tracking

1. Each appointment must resolve to exactly one product context (directly or through the lead-product primary mapping).
2. Appointment events (scheduled, completed, no-show, cancelled) must update product-level funnel counters.
3. Product-level meeting metrics must be queryable by date range.
4. Pipeline stage progression must remain status-driven, but rollups must be product-segmented.

Business value: links meetings to revenue pipeline by product, enabling better sales focus.

### FR-C: Product Performance Command Dashboard

1. Dashboard must show, per product:
   - New leads
   - Engaged leads
   - Qualified leads
   - Proposal-sent leads
   - Closed deals
   - Meetings scheduled
   - Meeting completion rate
   - Campaign response rate
2. Dashboard must support filters: date range and product.
3. Dashboard must expose trend comparison (current period vs previous equivalent period).
4. Dashboard must provide actionable ranking: top and bottom products by conversion to closed deals.

Business value: drives management decisions on which products to push, fix, or pause.

### FR-D: Structured Proposal Workflow

1. When lead stage reaches proposal intent, user/AI can generate a proposal record tied to lead + product.
2. Proposal record must store: summary, quoted price, key terms, status, version, sent_at.
3. Proposal status transitions must be explicit: draft -> sent -> viewed -> accepted/rejected/expired.
4. Accepted proposals must update lead-product deal fields consistently.
5. Proposal metrics must be available for dashboard rollups (sent count, acceptance rate, time-to-decision).

Business value: shortens deal cycles and improves visibility into pricing-stage leakage.

## 6. Out Of Scope (To Prevent Scope Creep)

1. Rebuilding product CRUD or AI agent setup screens.
2. Building new multi-agent orchestration framework.
3. Generic "engagement score" formulas without direct sales actionability.
4. Broad omnichannel expansion beyond current messaging foundations unless required by above FRs.
5. Cosmetic-only redesign with no measurable sales impact.

## 7. Success Criteria

1. 100% of new campaigns are product-bound.
2. 0 cross-product recipient leakage in campaign sends.
3. Product dashboard can explain conversion drop-off between major stages.
4. Proposal acceptance rate and cycle time become measurable in-app.
5. Sales managers can identify top 3 and bottom 3 products by conversion in under 1 minute.

## 8. Delivery Sequence

1. FR-A Product-Bound Campaign Execution
2. FR-B Product-Attributable Appointment & Pipeline Tracking
3. FR-D Structured Proposal Workflow
4. FR-C Product Performance Command Dashboard

Reason for order: enforce clean product attribution first, then build reporting on trustworthy data.

## 9. Build Prompt (For AI Developer)

Use this prompt when implementation starts:

> You are upgrading an existing Laravel-based SafariChat codebase. Do not rebuild modules that already exist (products, campaigns, appointments, AI sales agent, WhatsApp orchestration). Implement only a Product-Centric Revenue Execution Layer with four capabilities: 
(1) make campaigns strictly product-bound with recipient guardrails and product-level reporting,
(2) ensure appointment outcomes are attributable to one product and update product funnel counters, 
(3) add a structured proposal workflow tied to lead + product with status transitions and metrics, and 
(4) build a product performance dashboard with stage rollups, conversion trends, and ranking by closed-deal conversion. Keep changes backward compatible where possible, avoid speculative features, and prioritize measurable sales impact over UI cosmetics.

