# SafariChat × LineShop — Catalog & Sales Integration Plan

_Making SafariChat viable for large-catalog sellers (e.g. a pharmacy with ~4,000 SKUs) by sourcing products & stock from LineShop instead of manual entry._

---

## 1. The three questions, answered directly

**Q1. "4,000 products, added one-by-one — will this work?"**
**No, not as it stands.** SafariChat today has **no bulk product import** (products are added one at a time, and each tangible product requires name, category, description, a **unique SKU**, retail price, wholesale price, status). Re-keying 4,000 SKUs is a non-starter, and keeping them updated by hand is worse. This must be solved by **bulk import** and, better, by **syncing from the inventory system the shop already uses (LineShop)**.

**Q2. "Customers screenshot my Instagram products and ask on WhatsApp — will it work?"**
**Yes, achievable** — but it's a *visual product-lookup* problem, not just "read an image." SafariChat already has a **vision path** (premium-only, gated by the agent's `vision_enabled` flag + an active premium plan). To actually answer "do you have this, how much?", the AI must: read the screenshot → extract product/brand text → **match it to a specific product in a searchable catalog** → return **price + live stock**. That requires the catalog to be present and searchable in SafariChat (→ reinforces the sync plan) and a matching layer. Expect "best match + confirm" UX, not perfect 1-shot SKU identification.

**Q3. "LineShop exists (stock + POS + virtual manager). Enable SafariChat there, or connect SafariChat to LineShop?"**
**Connect them. Do not rebuild either inside the other.**
- **LineShop = system of record** for catalog + stock + POS (it's purpose-built for this; 4,000 SKUs live there already).
- **SafariChat = the conversational commerce layer** (WhatsApp, AI, vision, follow-ups) that **reads** from LineShop.
- Rebuilding WhatsApp/AI inside LineShop would duplicate SafariChat; re-entering/maintaining inventory inside SafariChat would duplicate LineShop. **One integration, two apps.**

---

## 2. Target architecture

```
  Instagram / WhatsApp customer
            │  (text or product screenshot)
            ▼
   ┌───────────────────────────┐        read catalog + live stock
   │        SafariChat         │  ───────────────────────────────►  ┌───────────────┐
   │  WhatsApp AI · vision ·   │                                    │   LineShop    │
   │  conversation · follow-up │  ◄───────────────────────────────  │  (SMA / CI3)  │
   │  cached searchable catalog│     stock-change webhook (push)     │ catalog+stock │
   └───────────────────────────┘        + periodic poll (fallback)   │   + POS       │
                                                                     └───────────────┘
```

- SafariChat keeps a **synced, searchable copy** of the catalog (for fast fuzzy/vision matching and offline resilience) but treats LineShop as truth.
- Before SafariChat tells a customer "yes, in stock", it does a **live stock check** (or trusts a recently-pushed value).
- When stock changes at POS, LineShop **pushes** the change so SafariChat can react in real time (and proactively message customers).

---

## 3. Integration design (grounded in LineShop's actual code)

LineShop facts that make this clean:
- **Per-shop API auth already exists**: `sma_settings.api_key` + `api_enabled` per `client_id`, with an admin UI (`Api_settings.php`) to mint/enable keys. The style-A `API_Controller` validates `X-Client-ID` + `X-API-Key` and pins `$this->client_id`.
- **A product-read query already exists** (`Products_api::getProducts()` → id, code, name, price, `image_url`, unit) — it just isn't exposed as a client-scoped read endpoint yet.
- **Stock is a cached value** (`sma_products.quantity`, `sma_warehouses_products.quantity`) kept current by a **single choke point**: `Site::syncProductQty()` / `syncQuantity()` (called on every sale/purchase/adjustment). `alert_quantity` is the low-stock threshold.
- **No realtime infra** (no websockets/queue/Redis) — so realtime = add a webhook at that choke point, or poll.
- Images are local files served as full URLs (`image_url` already emitted).

### 3.1 LineShop side (small additions)
1. **Add a client-scoped read endpoint** (style-A, reusing the existing auth): e.g.
   - `GET /api/v1/products/catalog` → paginated list: `code, name, price, promo_price, category, brand, image_url, quantity, alert_quantity, unit, updated_at` — **filtered by `client_id`**.
   - `GET /api/v1/products/stock?codes=...` → fast stock lookup for specific SKUs.
   - Reuse `Products_api::getProducts()` SQL + the warehouse-stock join pattern from `Pos_model`, **adding `WHERE client_id = $this->client_id`**.
2. **Add an outbound stock webhook** inside `Site::syncProductQty()` (the one place all stock changes pass through): POST `{client_id, product_code, quantity, alert_quantity}` to a SafariChat URL configured per shop. Fire-and-forget; failures logged, not fatal.

> ⚠️ **Do NOT use the legacy style-B REST endpoints** for reads — they don't enforce `client_id` and would mix shops' data in a shared DB. Only the `client_id`-scoped style-A path is safe.

### 3.2 SafariChat side (new)
1. **"Connect LineShop" settings** (per business): store `lineshop_base_url`, `client_id`, `api_key`. One SafariChat business ↔ one LineShop shop.
2. **`LineShopConnector` service**:
   - **Catalog sync** (scheduled, e.g. nightly + on-demand "Sync now"): pull `/products/catalog`, upsert into SafariChat's `products` table, keyed by an **external ref** (`lineshop_product_code`). Store price, image URL, category, stock snapshot. No manual entry.
   - **Live stock check** at quote time: before the AI confirms availability, call `/products/stock` for that SKU (short-TTL cache).
   - **Webhook receiver**: accept LineShop's stock pushes → update the cached product + optionally trigger proactive customer messages.
3. **Product identity**: keep LineShop's `code` (SKU) as the join key; never let the two catalogs drift.

### 3.3 Deployment reality — LineShop is ONE shared database (confirmed)
LineShop runs on a **single shared MySQL database**, multi-tenant by **`client_id`** — **not** a database-per-shop. Confirmed in code: `MY_Controller` resolves `CLIENT_ID` from the logged-in session, or (for anonymous/subdomain requests) by looking up `sma_settings` via `reference_name`, all on one `$this->db` connection; there is **no per-shop DB switching** anywhere. The "db-per-shop" signals (old `.env.example` `DB_DATABASE=…`, historical `migration_report_*` files) are **legacy artifacts**, not the live model.

**What this means for us:** every shop is one `sma_settings` row carrying `client_id` + `reference_name` (its subdomain) + `api_key` + `api_enabled`. A SafariChat↔LineShop link is simply **one `client_id` + that shop's API key**, and every read we expose must filter `WHERE client_id = <that id>`. Because it's a shared DB, we **must** use the `client_id`-scoped style-A API path (never the legacy unscoped REST endpoints, which would leak other shops' data).

Both apps run on the same host (XAMPP). A **direct read-only DB connection** (like the `admin_crm` / `notification` connections already in SafariChat) is technically possible as a shortcut — but since it's a single shared DB, a direct connection would require SafariChat to apply `client_id` filtering itself and would couple the two schemas. The **API path is the correct, tenancy-safe, future-proof choice**; keep direct-DB only as an optional fast path for a single self-hosted deployment.

### 3.4 Connection workflow — how an owner links the two

**Design goal:** one click, no manual API-key copying, and it must set up **both directions at once** — SafariChat→LineShop (read catalog/stock) *and* LineShop→SafariChat (push stock changes). A **one-time pairing code** does this cleanly.

**Who gets a "Connect" button?** **Both platforms show one**, but only **one side generates** the pairing code and the other **consumes** it. Recommended initiator: **LineShop generates**, **SafariChat consumes** — because LineShop is the data source and its owner must explicitly opt-in to expose a shop's catalog (the key + `client_id` live there).

**Flow (pairing handshake):**
1. In **LineShop → API / Integrations settings**, the owner clicks **"Connect to SafariChat"**. LineShop:
   - ensures this shop (`client_id`) has `api_enabled = 1` and an `api_key` (mints one if missing),
   - generates a **short-lived pairing code** (e.g. 10-min TTL) that encodes `{base_url, client_id, api_key}` (signed, single-use).
2. In **SafariChat → Settings → Connect LineShop**, the owner clicks **"Connect LineShop"**, pastes the code. SafariChat:
   - redeems the code, stores `{lineshop_base_url, client_id, api_key}` for this business,
   - **validates** by calling `GET /api/v1/products/catalog?limit=1` (proves the key works),
   - **registers its webhook back**: sends SafariChat's `{webhook_url, webhook_token}` to a LineShop `…/connect/confirm` endpoint, which saves it on the shop's settings so LineShop knows where to push stock changes.
3. Done — both directions live. SafariChat shows "Connected to <shop name> · Sync now"; LineShop shows "Connected to SafariChat · Disconnect".

**Simpler MVP fallback (if you want to ship faster):** skip the pairing code — SafariChat-only "Connect" form where the owner pastes the **`client_id` + API key** they minted in LineShop's existing `Api_settings`. Works today with LineShop's current auth; add the webhook registration later. The pairing code is the better long-term UX.

**One link = one shop.** A SafariChat business connects to exactly one LineShop `client_id`, and a LineShop shop connects to one SafariChat business. Store the pairing on both sides and refuse a second concurrent link (prevents ambiguous routing).

### 3.5 Phone-number uniqueness — the "one number, one role" rule

**Yes — enforce it. Your instinct is correct; without it you get chaos.** Both systems act on WhatsApp numbers:
- **SafariChat** uses a number as the **customer-facing AI line** (`whatsapp_instances.phone_number`) and for owner/team login (WhatsApp OTP).
- **LineShop** authenticates **users by WhatsApp number (OTP)** and its **Virtual Manager authenticates a WhatsApp *sender* number** per shop.

If the **same number** is an **operator/AI line in both**, both systems receive and act on that number's inbound WhatsApp → **double processing, duplicate replies, lost/mis-routed messages, billing confusion.** (This is the same class of bug as the duplicate-reply issue — two handlers on one stream.)

**The rule:** an **operator number** — a SafariChat WhatsApp AI/instance line, or a SafariChat/LineShop login or Virtual-Manager sender — is **unique across both systems: one number → one role → one system.**
- ✅ **Customer / lead numbers are exempt** — a customer can (and will) exist as a contact in both; they're not operators.
- Normalize every number to **E.164** before comparing.

**How to enforce (two points):**
1. **At link time (handshake):** exchange each side's set of *operator* numbers and **reject/flag any overlap** before the connection completes, with a clear message ("+255… is already a SafariChat AI line; it can't also be a LineShop manager — use a different number or remove it there first").
2. **Going forward (registration-time cross-check):** when either system registers a new operator number (new SafariChat instance, new LineShop manager/user), it calls the linked partner's **`is-number-claimed?`** endpoint first; if claimed, it's refused.

**Why this supports future expansion:** with one number → one role, every inbound message routes **deterministically** (number → system → shop → role). That unlocks a future **unified inbox**, clean analytics, and — importantly — the option to **consolidate both WhatsApp layers into SafariChat** later without untangling ambiguous ownership.

**Advice (strategic):** the cleanest long-term answer is **one WhatsApp layer, owned by SafariChat** (customer-facing sales), with LineShop's Virtual Manager kept to **internal ops on a separate number** (or eventually folded into SafariChat). The uniqueness rule is the guardrail that keeps things sane while both coexist — and the migration-safety net for consolidating later.

---

## 4. The 4,000-product onboarding — two tracks

**Track A — Bulk import (ship first; works with NO LineShop).**
Add CSV/Excel product import to SafariChat: minimal required columns (name, SKU, price; optional category, description, image URL, stock). Validate, dedupe by SKU, report row errors. This alone makes SafariChat usable for *any* large-catalog seller immediately, independent of LineShop.

**Track B — LineShop sync (the real answer for LineShop users).**
Zero manual entry: the catalog + stock flow in from LineShop and stay current. This is where a pharmacy ends up.

---

## 5. Instagram-screenshot → WhatsApp (visual search)

Flow:
1. Customer sends a screenshot on WhatsApp.
2. SafariChat **vision** (premium) extracts product name / brand / visible text.
3. **Match** against the synced catalog (fuzzy name/brand first; semantic/embeddings later for accuracy).
4. Reply with **price + live stock**, and if out of stock, **offer alternatives** (same category/brand).
5. Use **"best match + confirm"**: _"Is this the one? **Panadol Extra 20s** — TZS 3,500, in stock ✅"_ to handle ambiguity.

Prereqs: premium vision (exists, gated), a searchable synced catalog (Track B), and a matching layer (new). Set expectations: screenshot→exact-SKU among thousands is imperfect; design around confirmation + alternatives.

---

## 6. Phasing (each phase is independently shippable)

| Phase | Scope | Outcome |
|------|-------|---------|
| **0 — Quick win** | Bulk CSV/Excel **product import** in SafariChat | Any large catalog loadable in minutes; no LineShop needed |
| **1 — Catalog sync** | LineShop client-scoped **read API** + SafariChat **Connect LineShop** + `LineShopConnector` scheduled sync + live stock check at quote | AI answers price/availability from real inventory; no manual entry |
| **2 — Realtime + vision** | LineShop **stock webhook** at `syncProductQty` → SafariChat; proactive "finished / back in stock"; **screenshot→product** visual search (premium) | AI never promises out-of-stock; handles the Instagram-screenshot behavior |
| **3 — Transactional (future)** | SafariChat **reserves stock / creates a POS sale or order** in LineShop (mirror the existing `products/add` write pattern + a new sale endpoint) | "Support sale" end-to-end — closes the loop |

---

## 7. Risks & decisions to confirm (before building)

1. **LineShop tenancy model — RESOLVED:** it's a **single shared MySQL database, multi-tenant by `client_id`** (confirmed in `MY_Controller`; the db-per-shop signals were legacy artifacts). So: one `client_id` + API key per shop, and every exposed read **must** filter by `client_id`. No per-shop DB connections to manage.
2. **`client_id` is retrofitted** (migrations 317/319), not in the base schema — verify the column exists on `sma_products` / `sma_warehouses_products` in the live DB before filtering on it (it does in current deployments, but confirm).
3. **Avoid the unscoped legacy REST endpoints** — only expose the `client_id`-scoped style-A read path to SafariChat.
4. **LineShop already has a "Virtual Manager" + `Wasender` WhatsApp concept.** Clarify how it relates to SafariChat so you don't run **two WhatsApp layers** against one shop. Decide: SafariChat owns customer-facing WhatsApp sales; LineShop's virtual manager stays internal/ops — or consolidate.
5. **Vision cost & accuracy** (premium gating) — budget per-image token cost; keep it premium.
6. **Don't duplicate stock management** in SafariChat — it only caches; LineShop stays the source of truth.
7. **CORS is `*`** on LineShop's style-A API — fine server-to-server, tighten for production.

---

## 8. Recommendation (TL;DR)

- **Yes, this can work for a 4,000-SKU pharmacy — but not via one-by-one entry.**
- **Extend SafariChat to connect to LineShop.** LineShop = catalog/stock/POS truth; SafariChat = WhatsApp AI sales brain.
- **Ship Phase 0 (bulk import) now** for immediate value, then **Phase 1 (LineShop sync)** to eliminate manual entry, then **Phase 2** for realtime stock + the Instagram-screenshot visual search.
- The integration is low-risk because LineShop **already has per-shop API-key auth, a product-read query, a single stock choke point for a webhook, and an existing HTTP-bridge precedent** — we're extending, not inventing.
- **Tenancy is settled:** LineShop is a **single shared DB keyed by `client_id`**, so a link is just one `client_id` + API key; all reads are `client_id`-scoped.
- **Link via a one-time pairing code** (button in both apps; LineShop generates, SafariChat consumes) so both directions are set up without copying keys by hand.
- **Enforce "one number, one role, one system"** for operator/AI numbers (customers exempt) to avoid two WhatsApp layers fighting over one stream — and to keep the door open to consolidating WhatsApp into SafariChat later.
