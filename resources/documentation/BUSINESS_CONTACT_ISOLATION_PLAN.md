# Business Contact Isolation Implementation Plan

## Problem Summary

The `business_contacts` table is architecturally correct — it has both `business_id` and `user_id`
columns, clearly intended to represent **a contact as known by a specific business**. However, the
**implementation breaks this isolation** in at least 11 places across the codebase by querying
`guest_phone` without any `business_id` filter.

### What happens today (broken behaviour)

```
Customer A (+255712345678) messages Business X (user_id=10) → contact created correctly for business X
                                                                  (business_id=5, guest_phone=+255712345678)

Same Customer A (+255712345678) messages Business Y (user_id=20) →
  AiWhatsAppService line 141:
    BusinessContact::where('guest_phone', '+255712345678')->first()
                           ↑ NO business_id filter
    Returns the Business X record (id=1, business_id=5) ← WRONG!
    Creates a Lead attributed to Business X's contact, not Business Y's.
    AI agent for Business Y never gets the right lead / conversation context.
    BillingService counts contacts against Business X's quota, not Business Y's.
```

### Why this causes "AI processing failed / Unknown error"

`findBestAgent()` queries `AiSalesAgent::where('user_id', $message->user_id)` (user_id=20 for
Business Y), but the `$lead->ai_sales_agent_id` that was set previously belongs to Business X's
agent — the one attached to the wrong contact. The lookup finds no matching active agent for
`user_id=20`, returns null, and the no-agent path returns `['success' => false, 'requires_human' =>
true]` — logged as "Unknown error".

---

## Root Cause Locations

### 1. `AiWhatsAppService::findOrCreateLead()` — **Critical**

```php
// File: app/Services/AiWhatsAppService.php, line 141
// BUG: No business_id filter — returns first contact with this phone across ALL businesses
$businessContact = BusinessContact::where('guest_phone', $message->phone_number)->first();
```

**Also at line 174** — the `Lead::where('business_contact_id', ...)` lookup is secondarily wrong
because it was already given the wrong contact.

---

### 2. `UserResolutionService::findContactByMultipleMethods()` — **Critical**

```php
// File: app/Services/UserResolutionService.php, lines 107–118
// BUG: All three lookup strategies (phone LIKE, email =, fuzzy name) have NO business_id scope
foreach ($queries as $query) {
    $contact = BusinessContact::where($query[0], $query[1], $query[2])->first();
```

This is the shared lookup used by ALL callers of `resolveOrCreateContact()`.

---

### 3. `UserResolutionService::findContactByFuzzyName()` — **Critical**

```php
// Lines 201–212
// BUG: Name-based fuzzy matching searches ALL businesses
$contact = BusinessContact::where('guest_name', $name)->first();
$contact = BusinessContact::whereRaw('LOWER(guest_name) = LOWER(?)', [$name])->first();
$contact = BusinessContact::where('guest_name', 'LIKE', '%' . $word . '%')->first();
```

---

### 4. `UserResolutionService::createNewContact()` — **Half-broken**

```php
// Line 324 — this DOES correctly set business_id on creation:
$userBusiness = \App\Models\Business::where('user_id', $userId)->first();
// ...
'business_id' => $userBusiness->id,
```

BUT it is only reached when the lookup above (point 2) fails to find any match. Because the lookup
has no `business_id` filter it **finds another business's contact and returns it** — so
`createNewContact()` is never called when a genuinely new contact for this business should be made.

---

### 5. `UserResolutionService::calculateContactPriority()` — Medium

```php
// Line 297
$existingContact = BusinessContact::where('guest_phone', 'LIKE', '%' . substr($contactData['phone'], -8))
                                   ->where('contacted_for_sales', true)
                                   ->first();
```

No business scope — boosts priority based on another business's engagement history.

---

### 6. `ProcessWebhookNotification` Job — **Critical**

```php
// app/Jobs/ProcessWebhookNotification.php, line 234
$contact = UserResolutionService::resolveOrCreateContact([
    'phone' => $normalizedPhone,
    'name' => $this->webhookData['sender_name'] ?? 'Unknown'
    // ← NO user_id, NO business_id passed
]);
```

Has `$instance` resolved just above (line 241) but **never passes its `user_id` to the contact
resolver**. Contact is created without business association, or returns first match with no business
filter.

---

### 7. `SendWhatsAppMessage` Job — Medium

```php
// app/Jobs/SendWhatsAppMessage.php, line 224
$businessContact = UserResolutionService::resolveOrCreateContact([
    'phone' => $this->phoneNumber,
    'name' => 'Auto-created from job',
    'user_id' => $this->userId  // ← user_id IS passed, but findContactByMultipleMethods() ignores it
]);
```

`user_id` passed but `findContactByMultipleMethods()` never uses it for scoping — still returns
first global match.

---

### 8. Controllers with unscoped `guest_phone` queries — Secondary

| File | Line(s) | Pattern |
|------|---------|---------|
| `app/Http/Controllers/Message.php` | 2565–2566 | `where('guest_phone', ...) orWhere(...)` no business |
| `app/Http/Controllers/Setup.php` | 246, 1232–1234, 1359 | Multiple `guest_phone` lookups |
| `app/Http/Controllers/Api/ContactApiController.php` | 81, 198 | Contact CRUD |
| `app/Http/Controllers/Api/CrmImportContactsController.php` | 91 | Deduplicate check |
| `app/Http/Controllers/Api/CrmSyncApiController.php` | 66 | Sync |
| `app/Http/Controllers/Guest.php` | 121, 271, 323, 987, 1111 | Legacy guest controller |

---

## Implementation Plan

### Phase 1 — Enforce business scope in the shared lookup layer (Highest Priority)

**Goal:** All contact resolution must be scoped by `business_id`. The schema already supports this.

#### 1.1 — Add `business_id` as a required parameter to the core methods

**`UserResolutionService::resolveOrCreateContact()`** — signature change:

```php
// BEFORE (current)
public static function resolveOrCreateContact(array $contactData): BusinessContact

// AFTER
// contactData must include 'user_id' or 'business_id'.
// business_id takes priority; if only user_id is given, it is resolved to business_id internally.
public static function resolveOrCreateContact(array $contactData): BusinessContact
```

The method body resolves `business_id` early:

```php
public static function resolveOrCreateContact(array $contactData): BusinessContact
{
    $normalizedPhone = self::normalizePhoneNumber($contactData['phone'] ?? '');

    // Resolve business_id — required for proper isolation
    $businessId = $contactData['business_id'] ?? null;
    if (!$businessId && !empty($contactData['user_id'])) {
        $business = \App\Models\Business::where('user_id', $contactData['user_id'])->first();
        if ($business) $businessId = $business->id;
    }

    if (!$businessId) {
        // Log loudly — callers should always provide business context
        Log::error('resolveOrCreateContact called without business_id or user_id', [
            'phone' => $normalizedPhone, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        // Fall through with null — will create a contact without isolation (legacy safety net)
    }

    $contact = self::findContactByMultipleMethods([
        'phone' => $normalizedPhone,
        'email' => $contactData['email'] ?? null,
        'name'  => $contactData['name'] ?? null,
    ], $businessId);  // ← pass business_id
    
    // ... rest unchanged
}
```

#### 1.2 — Scope `findContactByMultipleMethods()`

```php
// BEFORE
public static function findContactByMultipleMethods(array $identifiers): ?BusinessContact

// AFTER
public static function findContactByMultipleMethods(array $identifiers, ?int $businessId = null): ?BusinessContact
{
    $queries = [];
    if (!empty($identifiers['phone'])) {
        $queries[] = ['guest_phone', 'LIKE', '%' . self::extractPhoneDigits($identifiers['phone']) . '%'];
    }
    if (!empty($identifiers['email'])) {
        $queries[] = ['guest_email', '=', $identifiers['email']];
    }

    foreach ($queries as $query) {
        $builder = BusinessContact::where($query[0], $query[1], $query[2]);
        if ($businessId) {
            $builder->where('business_id', $businessId);  // ← THE KEY FIX
        }
        $contact = $builder->first();
        if ($contact) return $contact;
    }

    if (!empty($identifiers['name'])) {
        return self::findContactByFuzzyName($identifiers['name'], $businessId);
    }

    return null;
}
```

#### 1.3 — Scope `findContactByFuzzyName()`

```php
private static function findContactByFuzzyName(string $name, ?int $businessId = null): ?BusinessContact
{
    $scope = fn($q) => $businessId ? $q->where('business_id', $businessId) : $q;

    $contact = $scope(BusinessContact::where('guest_name', $name))->first();
    if ($contact) return $contact;

    $contact = $scope(BusinessContact::whereRaw('LOWER(guest_name) = LOWER(?)', [$name]))->first();
    if ($contact) return $contact;

    $words = explode(' ', $name);
    foreach ($words as $word) {
        if (strlen($word) >= 3) {
            $contact = $scope(BusinessContact::where('guest_name', 'LIKE', '%' . $word . '%'))->first();
            if ($contact) return $contact;
        }
    }

    return null;
}
```

#### 1.4 — Scope `calculateContactPriority()`

```php
// Line ~297 — add business_id to the returning-customer check
$existingContact = BusinessContact::where('guest_phone', 'LIKE', '%' . substr($contactData['phone'], -8))
                                   ->where('contacted_for_sales', true)
                                   ->when(!empty($businessId), fn($q) => $q->where('business_id', $businessId))
                                   ->first();
```

---

### Phase 2 — Fix the AI pipeline callers

#### 2.1 — `AiWhatsAppService::findOrCreateLead()` (line 141)

```php
private function findOrCreateLead(IncomingMessage $message): Lead
{
    // Resolve the business that owns this WhatsApp instance / user_id
    $business = \App\Models\Business::where('user_id', $message->user_id)->first();
    if (!$business) {
        throw new \Exception("No business found for user_id={$message->user_id}");
    }

    // FIXED: scope by business_id — a contact belongs to ONE business's CRM
    $businessContact = BusinessContact::where('guest_phone', $message->phone_number)
                                      ->where('business_id', $business->id)  // ← Fix
                                      ->first();

    if (!$businessContact) {
        // Check contact limit
        $limitCheck = BillingService::canAddContact($message->user_id);
        if (!$limitCheck['can_add']) {
            // ... existing limit handling
        }

        $businessContact = UserResolutionService::resolveOrCreateContact([
            'phone'       => $message->phone_number,
            'name'        => $message->sender_name ?? 'WhatsApp Contact',
            'user_id'     => $message->user_id,
            'business_id' => $business->id,  // ← Pass explicit business_id
        ]);
    }

    // FIXED: scope Lead lookup by the correct business contact
    $lead = Lead::where('business_contact_id', $businessContact->id)->first();
    // ... rest unchanged
}
```

#### 2.2 — `ProcessWebhookNotification` Job (line 234)

```php
// After instance is resolved (line ~241)
$instance = WhatsappInstance::where(...)->first();

// Pass user_id so the contact is created under the right business
$contact = UserResolutionService::resolveOrCreateContact([
    'phone'   => $normalizedPhone,
    'name'    => $this->webhookData['sender_name'] ?? 'Unknown',
    'user_id' => $instance?->user_id,  // ← Add this
]);
```

#### 2.3 — `SendWhatsAppMessage` Job (line 224)

No change needed to the call site — `user_id` is already passed. The fix in Phase 1.2 makes
`findContactByMultipleMethods()` respect it automatically.

#### 2.4 — `UnifiedNotificationService` (line 542)

Review and add `user_id` / `business_id` to the `resolveOrCreateContact()` call.

---

### Phase 3 — Database unique constraint

Once contact isolation is enforced in code, add a database-level guarantee.

#### 3.1 — Migration: unique index on `(business_id, guest_phone)`

```php
// New migration file
Schema::table('business_contacts', function (Blueprint $table) {
    // First deduplicate (see 3.2) then add constraint
    $table->unique(['business_id', 'guest_phone'], 'business_contacts_business_phone_unique');
});
```

This makes it **impossible** for a phone number to appear twice under the same business, and
**correct** for the same phone to appear under different businesses (separate rows, separate leads).

#### 3.2 — Deduplication script (run before migration)

For any existing duplicate `(business_id, guest_phone)` pairs (same phone, same business, created
before this fix), merge them:

```php
// artisan command: php artisan contacts:deduplicate-within-business
// Logic:
//   1. Find all (business_id, guest_phone) groups with COUNT > 1
//   2. Keep the oldest record (canonical)
//   3. Re-point all leads, messages, outgoing_messages to the canonical record
//   4. Delete duplicates
```

---

### Phase 4 — Controller cleanup (Secondary)

Update the controllers listed in "Root Cause Location #8" to consistently scope by `business_id`.
These are dashboard/admin views — lower urgency than the AI pipeline, but they should show each
business only their own contacts.

Pattern to apply everywhere:

```php
// BEFORE (broken)
BusinessContact::where('guest_phone', $phone)->first()

// AFTER (correct)
BusinessContact::where('guest_phone', $phone)
               ->where('business_id', auth()->user()->business->id)
               ->first()
```

For controllers used by business owners (not super-admin), gate every query on:
```php
->where('business_id', $this->getAuthenticatedBusiness()->id)
```

Add a **`scopeForBusiness()`** on the `BusinessContact` model as a convenience:

```php
// In BusinessContact model
public function scopeForBusiness($query, int $businessId): Builder
{
    return $query->where('business_id', $businessId);
}

// Usage everywhere becomes:
BusinessContact::forBusiness($businessId)->where('guest_phone', $phone)->first();
```

---

### Phase 5 — Add `scopeActive()` guard to Business model lookup

A subtle secondary risk: `Business::where('user_id', $userId)->first()` could return the wrong
business if a user ever owns more than one business (future feature). Explicitly document that
SafariChat currently assumes **one business per user** and add an assertion:

```php
// UserResolutionService::createNewContact()
$userBusinesses = \App\Models\Business::where('user_id', $userId)->get();
if ($userBusinesses->count() > 1) {
    Log::warning('User owns multiple businesses — using first one', ['user_id' => $userId]);
}
$userBusiness = $userBusinesses->first();
```

If multi-business-per-user is a future feature, an explicit `businessId` parameter will be needed
at every entry point.

---

## Execution Order (Recommended)

| Step | File | Change | Risk |
|------|------|--------|------|
| 1 | `UserResolutionService.php` | Add `$businessId` param to `findContactByMultipleMethods()` + `findContactByFuzzyName()` + `calculateContactPriority()` + resolve early in `resolveOrCreateContact()` | Low — purely additive param |
| 2 | `AiWhatsAppService.php` | Fix `findOrCreateLead()` lines 141 + 165 | **High impact — fixes the AI bug** |
| 3 | `ProcessWebhookNotification.php` | Pass `user_id` to `resolveOrCreateContact()` | Medium — fixes contact creation at webhook entry |
| 4 | Write + run deduplication artisan command | — | Medium — DB data change |
| 5 | Migration: add UNIQUE(business_id, guest_phone) | — | Low after dedup |
| 6 | Add `scopeForBusiness()` to `BusinessContact` model | — | None — additive |
| 7 | Controllers: scope all `guest_phone` queries | All 4 controllers | Low — display only |

---

## What Does NOT Need to Change

- **Database schema** — `business_contacts.business_id` already exists and is set on creation.
  This is entirely a **query-time scoping fix**.
- **`createNewContact()` in `UserResolutionService`** — already correctly sets `business_id` by
  looking up the user's business. It just needs to be reached (which steps 1–3 guarantee).
- **`Lead` model** — leads are already scoped by `user_id` and `business_id` in most queries.
- **`AiSalesAgent`** — `findBestAgent()` already scopes by `user_id`. Once the lead is correctly
  set to the right business's contact, this works correctly.

---

## Testing Checklist

After implementation:

1. Send a message from phone `+255712345678` to **Business A** → confirm `business_contacts`
   has a row with `business_id = A.id`.
2. Send a message from the **same phone** to **Business B** → confirm a **new separate row** is
   created with `business_id = B.id`. The Business A row is untouched.
3. AI agent for Business B receives the second message and processes it correctly.
4. Lead for Business A and Lead for Business B are separate records, each attached to their own
   contact row.
5. BillingService contact counts are per-user (already uses `user_id` — verify it matches the
   per-business contact count).
6. Webhook job creates contacts with the correct `business_id`.
