# SafariChat — A Standard Way to Add Products (Catalog Sources)

_Goal: stop treating "add a product" as one painful form. Treat it as **picking a source** that fills one catalog, which the AI answers from._

---

## 1. The core model — one catalog, many sources

Every business populates the **same `products` catalog** in SafariChat. The AI always answers from that catalog (fast, reliable, works even when the original source is slow/offline). A business picks the **source** that matches how they already keep their products:

| # | Source | What it is | Freshness |
|---|--------|-----------|-----------|
| S1 | **Manual** | Type products one-by-one | n/a |
| S2 | **Excel / CSV import** | Upload a file | Snapshot (re-upload to refresh) |
| S3 | **LineShop connect** | API sync + stock webhook | Near real-time (built) |
| S4 | **Generic API connector** | Their stock software exposes a read API | Scheduled + live-stock check |
| S5 | **Website / marketplace import** | Paste a store/profile URL (Shopify, Woo, Jiji, Alibaba) | Scheduled sync |
| S6 | **Facebook/Instagram Catalog feed** | Import their Meta catalog feed (NOT scraping) | Scheduled sync |

**Rules of the model (important):**
- **Answer from the catalog, never live-fetch per message.** Live scraping/calling the source on every customer question is slow, fragile and gets you blocked. Sync on a schedule + "Sync now".
- **Live stock truth only where the source supports it** (S3/S4 via API/webhook). For file/scrape/feed sources, show "synced X ago" and treat stock as approximate.
- **One primary source per business** (they pick their mode). Manual + Excel stay available as top-ups. When a sync source is connected, the Products page becomes a **read-only summary** (as already built for LineShop).

This is the "standard way": **choose a source → it flows into one catalog → AI answers from the catalog.**

---

## 2. The five business scenarios

### Scenario 1 — Stock-management business (4,000+ SKUs)
**Method:** Excel import (S2) or LineShop/generic API (S3/S4). **Status: built** (Excel + LineShop).
- One-by-one is a non-starter at this scale; Excel loads thousands in minutes; LineShop/API keeps them in sync with live stock.
- **Verdict: solved.** For non-LineShop stock systems, see Scenario 5.

### Scenario 2 — E-commerce platform (add a website link)
**Method:** Website importer (S5) — **structured-data-first**, scrape as fallback.
- Feasibility depends on the platform. Try the most reliable source in this order:
  1. **Product feed** (Google Merchant / Facebook catalog XML or CSV) — if they have one, this is gold.
  2. **Platform API / well-known endpoints** — Shopify `/products.json` + `/sitemap.xml`; WooCommerce REST API.
  3. **Sitemap + JSON-LD** (`schema.org/Product`) on product pages — most modern stores embed this.
  4. **HTML scraping** — last resort, fragile.
- **Verdict: feasible, quality varies.** Great for Shopify/Woo/structured sites; best-effort for bespoke sites. Always **preview before import**.

### Scenario 3 — E-commerce vendors with only a page (Jiji, Alibaba profile)
**Method:** same Website importer (S5), "marketplace profile" sub-type.
- **Jiji: confirmed workable** — robots.txt permits `/shop/`, and product data (name, price, description) is in the page HTML. Caveats: pagination (a shop can have 1,000+ ads across many pages), occasional **cross-posted sellers** on a shop page (needs filtering), and it's **scraping** — fragile to layout changes.
- **Alibaba: possible but harder** — strong anti-bot; expect more breakage. Treat as best-effort.
- **Caveats (both):** the marketplace's **Terms of Service** may restrict automated reuse (robots.txt ≠ ToS — a business/legal call), and any scraper needs **ongoing maintenance**.
- **Verdict: supported via S5, flagged "best-effort, periodic re-sync."** Jiji good; Alibaba fragile.

### Scenario 4 — Social media pages (Facebook, Instagram)
**Advice: do NOT scrape Instagram/Facebook.** Login walls, aggressive anti-bot, and ToS/legal risk make it unreliable. Instead:
- **If they sell on IG/FB, they already have a Facebook Catalog.** Import that catalog's **data feed** (Meta lets them generate a feed URL) → treat as S6 (a feed import). This is the **legitimate, structured** path.
- **If they only post product photos with captions (no catalog):** there is no reliable structured source. Options: Excel/manual (S1/S2), and lean on the **customer-screenshot → vision → match-against-catalog** flow (already designed) so the AI can still answer "do you have this?" — but note it still needs a catalog to quote exact price/stock.
- **Verdict:** support **Meta Catalog feed import**; **never raw IG/FB scraping**. No catalog → Excel + screenshot-vision.

### Scenario 5 — Custom stock-management software
**Method:** Generic API connector (S4) — the LineShop pattern, generalized.
- **Prefer API key/token auth, not username/password.** If a legacy system only does basic auth, support it but store it **encrypted** and warn; better, ask them to mint an API key.
- **Publish a simple "SafariChat Catalog API" contract** (a documented JSON shape: `code, name, price, stock, category, image`, + an auth header, + optional stock webhook). Any vendor can implement it — exactly how LineShop did. Offer a lightweight **field-mapping** step for APIs that return a different shape.
- **Verdict: feasible and high-value.** Low effort because the LineShop connector already proves the pattern.

---

## 3. Standardized "Add Products" UX

Replace the current scattered buttons with **one question**: *"How do you manage your products?"*

- First-time (no products, no source): a **source chooser** — cards for Manual, Excel, Website/Marketplace, Stock software (API), LineShop, Facebook Catalog.
- After choosing a **sync source** (S3/S4/S5/S6): Products page = **read-only summary** (count + "synced X ago" + **Sync now** + Manage connection). No Add button.
- **Manual/Excel mode:** the **Add New Product** button opens tabs — **Write product** / **Import Excel** (already built).
- Every sync source shares the same plumbing: **scheduled refresh**, **Sync now**, **freshness indicator**, idempotent upsert keyed by `(business, external_source, external_ref)`.

---

## 4. Pricing — which source on which plan

Monetize on **(a) the product-count cap** (the real lever for big catalogs) and **(b) methods that cost us infra** (crawling, scheduled sync, real-time webhooks, vision tokens).

| Capability | Trial | Starter | Pro | Premium |
|---|:--:|:--:|:--:|:--:|
| Manual add (S1) | ✅ (tiny cap) | ✅ | ✅ | ✅ |
| Excel / CSV import (S2) | — | ✅ | ✅ | ✅ |
| Website / marketplace import — Jiji/Shopify/Woo (S5) | — | — | ✅ | ✅ |
| Facebook Catalog feed (S6) | — | — | ✅ | ✅ |
| Scheduled auto-refresh | — | — | Daily | Hourly |
| LineShop / Generic API connector + stock webhook (S3/S4) | — | — | read-only* | ✅ real-time |
| Screenshot → product (vision) | — | — | — | ✅ (already premium) |
| **Product cap (indicative)** | ~10 | ~150 | ~1,000 | High / unlimited |

\* Pro could get a read-only daily API sync; **real-time stock (webhooks + live checks) is Premium.**

**Why this split:**
- **Excel on Starter** drives adoption (anyone can bulk-load), but the **product cap** still gates big catalogs upward.
- **URL/feed imports on Pro** — they need crawling + scheduled jobs (infra cost) and deliver clear value.
- **Real-time API/webhooks + vision on Premium** — highest infra/token cost, highest value; this is where a **4,000-SKU pharmacy with live stock** naturally lands (cap + LineShop + real-time).

---

## 5. Final roadmap (merged with what's already built)

**Phase 1 — Foundations (done / in review):**
- Manual add, **Excel/CSV import**, **LineShop connect** (API sync), mode-aware Products UX (SafariChat vs LineShop), read-only summary when connected. _Shipped locally; needs deploy._

**Phase 2 — Generalize the connector (low effort, high value):**
- Turn LineShop into a documented **"SafariChat Catalog API"** + a **Generic API connector** (base URL + API key + optional field mapping + stock webhook). Covers Scenario 5 and any future stock system.

**Phase 3 — Website / marketplace importer (Scenarios 2 & 3):**
- A **URL importer** with **structured-first** detection (feed → platform API → sitemap+JSON-LD → scrape), a **preview step** (show what we'd import before saving), pagination + seller filtering, scheduled sync. Launch with **Jiji + Shopify/Woo**; add others over time.

**Phase 4 — Meta Catalog + vision (Scenario 4):**
- **Facebook Catalog feed** import (legit, structured). Wire the **screenshot → catalog match** vision flow for Instagram-style "do you have this?" questions.

**Cross-cutting (all phases):**
- One **Catalog Sources** framework: scheduled refresh, Sync now, freshness badges, idempotent upsert, read-only summary on sync sources.
- **Live stock** only where the source supports it (S3/S4); everywhere else, honest "synced X ago".
- **Never live-fetch per message.**

---

## 6. The one-paragraph recommendation

Stop designing "add product" as a form; design it as **"connect a product source."** Offer six standard sources that all feed one catalog — Manual, Excel, LineShop, Generic API, Website/Marketplace URL, Facebook Catalog — and have the AI answer from the catalog with scheduled syncs (real-time only where an API/webhook exists). Price on **product-count caps + infra-heavy methods**: Excel on Starter, URL/feed imports on Pro, real-time API/webhooks + vision on Premium. Build order: **generalize the connector (Phase 2) → URL importer with Jiji/Shopify (Phase 3) → Meta catalog + vision (Phase 4)** — reusing the LineShop plumbing already in place. **Do not scrape Instagram/Facebook; use their catalog feed instead.**
