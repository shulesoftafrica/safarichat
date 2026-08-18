# Offer Rotation (Next-Best-Offer) — Implementation & Operator Guide

Implements **Part C1** of `SALES_EFFICIENCY_ANALYSIS_AND_PLAN.md`: the AI now rotates
through *different modules/angles* per prospect instead of repeating the same pitch.

**Status:** built, migrated, tested. **Ships OFF** — zero behavior change until enabled.

---

## 1. What was built

| Piece | File | Role |
|-------|------|------|
| Feature config | `config/sales_rotation.php` | Master flag, touch budget, ranking weights. |
| Ledger table | `database/migrations/2026_08_17_000001_create_lead_offer_progress_table.php` | `lead_offer_progress` — per-lead, per-offer rotation state (applied ✅). |
| Ledger model | `app/Models/LeadOfferProgress.php` | Tracks status / touch_count / outcomes per (lead, offer). |
| **The engine** | `app/Services/Sales/NextBestOfferService.php` | Picks the next untried module, records pitches & engagement. |
| First-touch wiring | `app/Console/Commands/DailyOutreachCommand.php` | Pitches the rotated offer; logs it on send. |
| Follow-up wiring | `app/Services/SmartFollowupService.php` | Replaces the generic template ladder with an offer-anchored message. |
| AI product injection | `app/Services/OpenAiService.php` | `generateResponse(..., $forcedOffer)` pitches the chosen module. |
| Engagement feedback | `app/Services/AiWhatsAppService.php` | On a reply, marks the in-flight offer as *engaged*. |
| Offer columns | `database/migrations/2026_08_17_000002_add_offer_columns_to_products_table.php` | Adds `target_industry`, `key_features`, `common_objections`, `upsell_products` (applied ✅). |
| ShuleSoft catalog | `database/seeders/ShuleSoftModuleCatalogSeeder.php` | Seeds ShuleSoft's 12 real modules as pitchable offers (seeded ✅). |

### Seeded ShuleSoft catalog (user_id 45 / business_id 4)

12 modules, each with an authored hook + pain point + features + rotation chain:
Platform overview, Online Admission, Fee Collection, UCN Payments, Bank Integration
(Mkombozi & CRDB), Bank Reconciliation, Budgeting, Accounting, Examination & Results,
Payroll, HR (newly launched), Recruitment (newly launched).

- **Idempotent:** re-run with `php artisan db:seed --class="Database\Seeders\ShuleSoftModuleCatalogSeeder"` — it enriches in place (matches by name aliases), never duplicates. The two pre-existing generic rows (`shulesoft`, `universal control number`) were repurposed into the Platform and UCN offers.
- **To edit copy or chains:** update the `modules()` array in the seeder and re-run, or edit the `products` rows directly (hooks live in `campaign_hook_text`, sequence in `upsell_products`).
- **Verified rotation** (max_touches=1): Platform → Admission → Fee Collection → Exam → Bank Integration → Reconciliation → Budgeting → Accounting → Payroll → HR → Recruitment — each a distinct, value-first message.

**Design guarantees**
- **Fail-open:** every engine call is wrapped — any error logs a warning and reverts to legacy behavior. Rotation can never break outreach.
- **Non-breaking:** with the flag off, `resolveForLead()` returns `null` and all callers behave exactly as before.
- **No extra AI spend on follow-ups:** offer-anchored follow-up messages are composed deterministically from the module's authored copy.

---

## 2. How to enable

Add to `.env` (pilot on a small cohort first):

```bash
SALES_OFFER_ROTATION=true
SALES_ROTATION_MAX_TOUCHES_PER_OFFER=2   # touches per module before rotating
```

Then `php artisan config:clear`. No deploy or code change needed to flip it.

> Recommended rollout: enable in staging → enable for ShuleSoft's own tenant → widen.

---

## 3. How to make a module "pitchable" (this is the catalog)

Each module is a row in `products`. To put a module into rotation, ensure it is
`status = active` and fill the offer fields (all already exist on `products`):

| Field | Use |
|-------|-----|
| `name` | Module name (e.g. "Exam Module", "Fee Collection", "Payroll", "Accounting Pro"). |
| `campaign_pain_point` | The pain it removes — the follow-up leads with this. |
| `campaign_hook_text` | Your authored opening hook (used verbatim-ish; `{name}`/`{module}` supported). |
| `ai_description` / `key_features` | Feeds the AI outreach pitch. |
| `common_objections` | Pre-loaded rebuttals for inbound replies. |
| `target_industry` | Ranks higher for leads whose `industry` matches. |
| `upsell_products` | The **next module to rotate to** after this one. |
| `is_active_campaign` | The "launch of the day" — always pitched first. |

A module is eligible for rotation if it has **any** of: `is_active_campaign`,
`campaign_hook_text`, `ai_description`, or is a service. (If a tenant has authored
none, the engine falls back to all active products so it never goes silent.)

### Launching a new module/feature "tomorrow"
1. Create/enable the `Product` row with `campaign_pain_point` + `campaign_hook_text` + `target_industry`.
2. Either `setAsActiveCampaign()` (global push — every matching lead gets it next),
   or add its id to the relevant modules' `upsell_products` (rotation sequence).
3. Done — the AI pitches it on the next scheduled touch. No deploy.

---

## 4. How the rotation behaves (verified)

For a lead with catalog `[shulesoft, universal_control_number]`, `max_touches = 2`:

```
touch 1: pitch "shulesoft"
touch 2: pitch "shulesoft"          (touch budget spent)
touch 3: pitch "universal control number"   ← ROTATES to a fresh angle
touch 4: pitch "universal control number"
touch 5: null  → cadence exhausted → caller hands off to nurture/win-back
```

- **Prospect replies mid-flight** → the in-flight offer is marked *engaged*; the engine
  keeps the conversation on that module instead of rotating away from a working angle.
- **`on_exhausted`** (config) decides what a `null` means: `null` = caller keeps its
  own fallback; `stop` = signal callers to drop to low-cost nurture.

---

## 5. What this does NOT yet do (next phases)

This slice delivers **angle rotation** (Plan C1). Still open, in priority order:

- **C2 Lifecycle state machine** — branch on delivered/read/replied, cadence budgets across the whole journey.
- **C3 Channel cascade** — switch WhatsApp→SMS→email on *non-response* (multi-channel plumbing exists but is OFF).
- **C4 Learning loop** — classify replies into an objection taxonomy; attribute conversions to `(module, angle, channel)`; rank next-best-offer by what actually converts (replace the static weights in `config/sales_rotation.php`).
- **C5 Human handoff** — route engaged/hot leads to a rep.

The engine is intentionally structured so C4 can swap the ranking function in
`NextBestOfferService::rankAndPick()` without touching any caller.

---

## 6. Rollback

- Disable instantly: `SALES_OFFER_ROTATION=false` + `php artisan config:clear`.
- Full removal: `php artisan migrate:rollback --path=database/migrations/2026_08_17_000001_create_lead_offer_progress_table.php` (drops `lead_offer_progress` only).
