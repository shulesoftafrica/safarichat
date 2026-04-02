# Referral Program Plan: "Invite & Earn Free Days"

## Overview
A lightweight referral loop: user submits a WhatsApp number via a gift icon in the top nav bar.
Each invited number is logged as a CRM lead under the SafariChat Platform system account for CS follow-up.
When the invited number completes registration, the inviter's subscription is automatically extended by **5 free days**.

---

## Steps

### 1. New config keys — `config/safarichat_billing.php`
Add:
```php
'referral_reward_days' => 5,
'referral_system_user_id' => env('SAFARICHAT_SYSTEM_USER_ID', 1),
```
`SAFARICHAT_SYSTEM_USER_ID` points to the seeded "SafariChat Platform" admin user that owns system CRM leads. Set once in `.env` on the server.

---

### 2. Migration — `referral_invites` table
File: `database/migrations/2026_04_03_000001_create_referral_invites_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `inviter_user_id` | FK → users | Who made the referral |
| `invited_phone` | string(25) | E.164 normalized e.g. `+255712345678` |
| `status` | enum `pending,joined,rewarded` | default `pending` |
| `crm_lead_id` | nullable FK → leads | Platform CRM lead record |
| `joined_user_id` | nullable FK → users | Set when invited number registers |
| `joined_at` | nullable timestamp | |
| `rewarded_at` | nullable timestamp | |
| `reward_days` | tinyint default 5 | Copied from config at insert time |
| `timestamps` | | |
| **Unique constraint** | `(inviter_user_id, invited_phone)` | Prevents duplicate invites |
| **Index** | `invited_phone` | Fast lookup at registration time |

---

### 3. New model — `app/Models/ReferralInvite.php`
Standard Eloquent model with:
- `inviter()` → `belongsTo(User::class, 'inviter_user_id')`
- `joinedUser()` → `belongsTo(User::class, 'joined_user_id')`
- `lead()` → `belongsTo(Lead::class, 'crm_lead_id')`
- `static pendingForPhone(string $phone): ?self` — lookup at registration time
- `static checkAndClaimReferral(User $user): void` — called from `Setup::registerBusiness()` after account creation; normalizes phone, finds pending invite, sets `joined_user_id`, dispatches reward job

---

### 4. New controller — `app/Http/Controllers/ReferralController.php`

**`status(Request $request)`** → `GET /referral/status`
- Returns JSON: `{total_invited, total_joined, total_days_earned, recent_invites[]}`
- Filters by `inviter_user_id = Auth::id()`

**`invite(Request $request)`** → `POST /api/referral/invite`
- Validates phone (required, E.164, not inviter's own phone)
- Rejects if phone is already a registered user
- Rejects duplicate `(inviter_user_id, invited_phone)`
- Creates `ReferralInvite` with `status = pending`
- Creates system CRM `Lead`:
  - `user_id` = `config('safarichat_billing.referral_system_user_id')`
  - `source` = `'referral'`
  - `phone_number` = normalized phone
  - `name` = `"Referral Lead – via {business_name}"`
  - `notes` = `"Invited by {business_name} (User #{id}) on {date}"`
  - `metadata` = `['referred_by_user_id' => X, 'invite_id' => Y]`
  - `status` = `Lead::STATUS_NEW`
- Sets `crm_lead_id` on the invite
- Returns JSON success + updated stats

---

### 5. New job — `app/Jobs/ProcessReferralRewardJob.php`
Constructor: `__construct(int $inviteId)` — dispatched on `cs` queue.

In `handle()`:
1. Load `ReferralInvite`; bail if already `rewarded`
2. Load `inviter->billingAccount`; bail if not found
3. Extend expiry:
   - Trial user (`subscription_plan = 'trial'`): extend `trial_ends_at` by `$invite->reward_days` days
   - Paid user: extend `subscription_expires_at` by `$invite->reward_days` days
4. Mark `status = 'rewarded'`, set `rewarded_at = now()`
5. Log the reward

No external Shulesoft billing API call — local expiry columns are the access gate checked by `BillingAccount::isExpired()`.

---

### 6. Registration hook — `app/Http/Controllers/Setup.php`
After `$this->createTrialBillingAccount($user, $business)` (~line 642), add:
```php
\App\Models\ReferralInvite::checkAndClaimReferral($user);
```

---

### 7. Routes — `routes/web.php`
Add after existing billing block (~line 347):
```php
// Referral program
Route::get('/referral/status', [ReferralController::class, 'status'])->name('referral.status');
Route::post('/api/referral/invite', [ReferralController::class, 'invite'])->name('referral.invite');
```

---

### 8. Nav icon — `resources/views/layouts/nav.blade.php`
Insert immediately **before** the language selector `<li class="hidden-sm">` (~line 424):
```html
<li class="nav-item">
    <a href="javascript:void(0);" class="nav-link" data-toggle="modal" data-target="#referralModal"
       title="{{ __('Invite & Earn') }}" style="padding: 0 10px;">
        <i class="fas fa-gift" style="color: #25d366; font-size: 18px;"></i>
    </a>
</li>
```

---

### 9. Modal — `resources/views/layouts/referral-modal.blade.php`
Bootstrap 4 `modal fade` matching the compliance modal pattern. Sections:
- **Header**: "Invite a Business & Earn 5 Free Days"
- **Stats card**: invited / joined / days earned — loaded via `GET /referral/status` on modal open
- **Form**: phone input with country-code prefix + "Send Invite" button
- **Recent invites**: last 5 invites mini-table

Include in `resources/views/layouts/app.blade.php` (~line 187):
```php
@include('layouts.referral-modal')
```

JS pattern (inside the partial `<script>` block):
- On modal shown → `GET /referral/status` → fill stats
- On form submit → `POST /api/referral/invite` → show success/error toast + refresh stats

---

## Verification Checklist
- [ ] `php artisan migrate`
- [ ] Register a test user whose phone was previously submitted as a referral → confirm `pending → joined → rewarded`
- [ ] Confirm `trial_ends_at` / `subscription_expires_at` extends by 5 days
- [ ] Confirm CRM lead appears under system user's lead list
- [ ] Duplicate invite submission rejected (unique constraint)
- [ ] Own phone submission rejected

---

## Decisions
- **Days not credits**: Extends expiry uniformly for trial and paid users — no plan-limit complexity
- **No external billing API call for reward**: Local expiry columns are the access gate; Shulesoft billing API has no "add free days" endpoint
- **System user**: Config-driven `SAFARICHAT_SYSTEM_USER_ID` env var — operator sets once pointing to a seeded "SafariChat Platform" user
- **E.164 normalization**: Reuse `formatPhoneNumber()` logic from `Setup.php`, extracted into `ReferralInvite` static helper
- **Queue**: `cs` queue — consistent with all other CS background work
