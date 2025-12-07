**⚠️ IMPORTANT UPDATE**: This file contains the original requirements. For updated requirements that accurately reflect the advanced AI Sales Agent platform capabilities and correct technology stack, see `landing-updated.md`

---

**Project goal (one sentence)**
Rebuild a high-converting, professional landing page for the SafariChat AI Sales Agent that reads like a top-tier salesperson convincing organizations, SMEs and corporates to hire the AI to “do the smart work” — drive signups, demos, and paid conversions while keeping the existing login flow intact.

---

## High-level requirements

* Audience: small and medium enterprises (SMEs), enterprises/corporates, and individual consultants/solopreneurs (primary: SMEs & corporates in Africa; secondary: global).
* Tone & voice: confident, consultative, outcomes-driven. Speak like a trusted senior salesperson: benefit-first, concise, emotionally persuasive.
* Keep the current login UX exactly as-is (visual placement and flow) — do not change behavior or removal.
* Responsive: desktop-first, mobile optimized (breakpoints: 1440, 1024, 768, 375).
* Performance & accessibility: Lighthouse score >= 90 (desktop). WCAG AA accessible.
* SEO: semantic markup, meta tags, OG tags, schema for Product and FAQ.
* Analytics & events: track hero CTA clicks, pricing-toggle interactions, currency toggle, demo requests, signup completions, and outbound clicks.

---

## Visual & brand guidance

* Clean, professional, modern. Use SafariChat brand colors (if available) — otherwise: primary deep-teal/navy, accent warm amber, neutrals (white, 10–60% greys).
* Typography: modern sans (e.g., Inter or Poppins). Clear weight hierarchy: H1 48–64px (desktop), H2 32–40px, body 16px.
* Use subtle motion: entrance fades for sections, micro-interactions on CTA and pricing toggle, but avoid heavy animations that hurt performance.
* Imagery: professional photos of teams in office + stylized illustrations of chat/automation. Use 1 hero image/illustration and 3 small icons for features.
* Microcopy: short, directive CTAs (e.g., “Start a Free Demo”, “Request Corporate Pricing”, “Talk to Sales”).

---

## Structure & content (order of sections)

1. Header — logo (left), top nav (Product, Features, Pricing, Case Studies, FAQ, Login). CTA button: “Start Demo”.

   * Keep Login link identical in behavior to current site.
2. Hero (conversion-focused)

   * H1: “Hire an AI Sales Agent that closes deals while you sleep.”
   * Subheadline: “SafariChat automates lead outreach, qualifies prospects, and books meetings — like a top-performing salesperson, for a fraction of the cost.”
   * Primary CTA: “Start Demo (Free)” — opens demo request modal.
   * Secondary CTA: “See Pricing” — scroll to pricing.
   * Short 3-bullet benefit line under CTAs (e.g., “Reduce lead response time”, “Increase qualified meetings by X%”, “Fully integrate with your CRM”).
3. Trust bar / proof row

   * Logos of clients / banks / partners (small).
   * Short metric highlights: “400+ schools onboarded”, “200,000 daily users”, “$40M+ processed yearly” (replace or use current metrics).
4. Problem → Solution block (2-column)

   * Left: Pain points (slow follow-ups, inconsistent outreach, wasted sales time).
   * Right: How SafariChat solves them (automated conversations, 24/7 availability, CRM integration, training & support).
5. Features (icon + short copy)

   * Feature 1: Human-like conversation & multi-channel messaging.
   * Feature 2: Smart qualification & routing to human reps.
   * Feature 3: CRM sync & analytics.
   * Feature 4: Custom training & onboarding (important for corporate).
   * Each feature: 12–15 words benefit + one supporting microcopy.
6. Use cases / Who it’s for (SME packages vs Corporate)

   * Examples: schools, fintechs, telcos, edtech, e-commerce.
7. Pricing card section (centerpiece) — **detailed logic below**
8. Social proof / case studies

   * One short, powerful case study with metrics + 2-3 short testimonials with photos.
9. FAQ (including currency & pricing model clarifications)
10. Footer — contact, links, legal, social.

---

## Pricing card — exact behavior & content (critical)

**Overview:**

* Top of the pricing area: a toggle with two modes:

  * **SME Packages** (default)
  * **Corporate Packages**
* Currency toggle: **USD / TSh**

  * TSh option **only** shows if the user is *detected* to be in Tanzania (IP geolocation) **or** the user explicitly selects Tanzania from a country selector. Otherwise default and show USD only.
  * If user selects TSh from Tanzania, convert the USD prices to Tsh using a live exchange rate API (developer to implement with exchangerate API). Also show a small note: “TSh pricing is for Tanzania only; international customers will be charged in USD.”
* Price display: show both currency abbreviation and symbol (USD or TSh). Under each price show billing cadence (monthly) and CTA to start.

**SME Packages (three cards):**

* **Basic** — for solo sellers / micro-SMEs

  * Example features: 1 AI Agent, 2 channels, 2,000 messages/month, email support.
* **Standard** — for growing SMEs

  * Example features: 3 AI Agents, 4 channels, 10,000 messages/month, CRM integration, phone & email support.
* **Premium** — for established SMEs scaling sales

  * Example features: unlimited agents, unlimited channels, 50,000 messages/month, priority support, analytics dashboard.
* Each card must show a clear short benefits list and a CTA:

  * CTA when clicking: If user is logged in → lead to subscription flow; if not logged in → open signup modal (same login preserved).

**Corporate Packages (pricing model described by you):**

* Corporate pricing introduces:

  * **Per-message billing model** (pay per message sent) — present a short explainer: “For large enterprise volumes, we charge a base setup fee + a per-message usage fee. This gives predictable scale pricing and lower fixed cost.”
  * **Setup fees** (one-time): options for Training the AI Agent, Staff Training, CRM Integration, Security Review, SLA, Dedicated Account Manager.

    * Present as selectable add-ons with baseline recommended packages (e.g., Onboarding Lite, Onboarding Plus, Enterprise Integration).
  * Display a starting baseline example:

    * Setup fee (example): $2,500 (Training + Integration) — **developer note:** show example numbers as placeholders and link CTA to “Request Custom Quote”.
    * Per-message example: $0.007 per message (placeholder) — show tiers and volume discounts (e.g., 0.01 USD for 0–500k messages/month, 0.007 for 500k–2M, custom pricing above).
  * Provide a “Build my Corporate Plan” wizard CTA that opens a short form to request a tailored quote (collect company name, size, estimated monthly messages, CRM, country, contact).
  * Ensure that the corporate card has a “Talk to Sales” button that launches a calendly-like scheduler or request form.

**Pricing UI specifics:**

* Toggle animation and immediate content swap (no page reload).
* When switching to Corporate, show a short explainer panel about per-message model, example cost calculator (interactive), and the setup fee checklist.
* The interactive cost calculator must:

  * Let user input estimated messages/month → show estimated monthly cost and recommended setup package.
  * Show both USD and TSh where applicable (TSh only for Tanzania).
* Accessibility: toggle and price chooser are keyboard accessible and readable by screen readers.

---

## Copy — ready-to-use snippets (paste directly)

**Hero H1**: Hire an AI Sales Agent that closes deals while you sleep.
**Hero Sub**: SafariChat automates lead outreach, qualifies prospects, and books meetings—like a top-performing salesperson, for a fraction of the cost.
**Primary CTA**: Start a Free Demo
**Secondary CTA**: See Pricing

**SME Pricing CTA**: Start Free Trial — no credit card required
**Corporate CTA**: Request Custom Quote

**Trust microcopy (under trust bar)**: Trusted by schools, fintechs and enterprises across Africa.

**Corporate per-message explainer (short)**:
“Enterprise customers pay a low per-message rate plus a one-time setup fee for AI training and CRM integration. This model scales with your volume — you only pay for what you send.”

**Setup fee microcopy**:
“Setup includes AI training on your data, staff training, CRM integration, and security review. Typical lead time: 1–4 weeks.”

**Pricing note**:
“TSh pricing is only available to customers billing from Tanzania. All other customers will be billed in USD. Exchange rates are updated daily.”

**FAQ snippets (add these):**

* Q: Can I switch packages later?
  A: Yes — upgrade/downgrade is available from your dashboard; prorated billing applies.
* Q: Do you integrate with our CRM?
  A: Yes — we support major CRMs (Salesforce, HubSpot, Pipedrive) and custom integrations via webhook/API.
* Q: Are messages charged both inbound and outbound?
  A: Only outbound messages (sent by the AI agent) are billed; inbound messages are included.

---

## Forms & flows

* Demo request form: name, company, email, phone, country, short use-case, estimated messages/month. (short)
* Corporate quote form: company name, company size, primary contact, CRM, estimated messages/month, preferred onboarding timeline.
* All forms to include consent checkbox for contacting and privacy notice link.

---

## Technical & integration notes (developer instructions)

* Frontend: React or Next.js recommended (SSR for SEO), Tailwind CSS for styling.
* Integrations:

  * Exchange rates: use a reliable exchange-rate API (fixer.io / exchangerate-api). Show conversion rates disclaimer.
  * CRM integration: implement webhooks and a secure backend endpoint to connect (OAuth for major CRMs).
  * Analytics: Google Analytics 4, Mixpanel or Amplitude for events.
  * Forms: store submissions in a database + send to CRM / email notifications.
* Server: API will handle quote calculations, currency conversion, and create leads.
* Security: CSRF protection on forms, input validation/sanitization.
* Tests: cross-browser, responsive checks, accessibility audit.

---

## Deliverables

1. Production-ready HTML/CSS/JS components or a Next.js repo with pages and components.
2. Design assets (SVGs, icons, hero illustration) and a style guide (colors, fonts, spacing).
3. Implemented pricing toggle + working currency toggle logic + interactive cost calculator (stubbed API calls OK with clear TODOs).
4. Demo & Quote form endpoints and simple storage (e.g., sends to email + stores in DB).
5. Documentation: README describing how to change prices, rates, and toggle rules.
6. Unit tests for the pricing calculator and integration tests for forms.

---

## Acceptance criteria (what success looks like)

* The landing page matches the content structure and tone above.
* Pricing toggle swaps between SME and Corporate instantly; Corporate shows per-message model + setup fees + cost calculator.
* Currency toggle respects Tanzania-only rule and performs conversion via API.
* Hero CTA, pricing CTAs and “Talk to Sales” are all tracked and fire analytics events.
* Visual design is professional, mobile-friendly, and performance scores meet targets.
* The Login flow is untouched visually and functionally.

---

## UX edge-cases & developer rules (do not deviate)

* If the user location is Tanzania (IP or selected), show TSh option; else hide TSh. If user manually selects TSh but their billing country is not Tanzania, show a tooltip: “TSh is available only for customers billed in Tanzania.”
* If user is logged in, clicking pricing CTA should start subscription flow; if not, show modal with two options: “Sign up” or “Request Demo”.
* All numbers shown on pricing are flagged as “example/estimate” in small text if they’re placeholders. Real values must be configurable in an admin file or CMS.

---

## Example JSON payload (for developer agent to use)

```json
{
  "hero": {
    "title": "Hire an AI Sales Agent that closes deals while you sleep.",
    "subtitle": "SafariChat automates lead outreach, qualifies prospects, and books meetings — like a top-performing salesperson, for a fraction of the cost.",
    "ctaPrimary": "Start a Free Demo",
    "ctaSecondary": "See Pricing"
  },
  "pricing": {
    "modes": ["SME", "Corporate"],
    "sme": [
      {"name":"Basic","priceUSD":29,"features":["1 Agent","2 channels","2,000 messages/mo"]},
      {"name":"Standard","priceUSD":99,"features":["3 Agents","4 channels","10,000 messages/mo","CRM integration"]},
      {"name":"Premium","priceUSD":299,"features":["Unlimited Agents","Priority support","50,000 messages/mo","Advanced analytics"] }
    ],
    "corporate": {
      "setupExamples":[{"name":"Onboarding Lite","priceUSD":1500},{"name":"Onboarding Plus","priceUSD":2500}],
      "perMessage":"0.007",
      "volumeDiscounts":[{"min":500000,"unitPrice":"0.007"},{"min":2000000,"unitPrice":"0.005"}]
    },
    "currencyRules": {"showTshOnlyFor":"Tanzania"}
  }
}
```

---

## Final notes for the developer AI agent

* Be persuasive: every headline and feature should sell an outcome (more meetings, lower cost, predictability).
* Make the pricing transparent and self-explanatory. For corporate, emphasize custom quoting and onboarding security.
* Keep the login flow unchanged.
* Provide clear admin-configurable values for all prices and conversion sources.

---

If you want, I can also produce:

* 1. **Exact hero & feature A/B copy variants** for quick testing, or
* 2. **A simple HTML/CSS prototype** for the pricing card (SME vs Corporate toggle) you can drop into the repo.

Which of those should I deliver next?
