<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'subscription_plan',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_ucn',
        'external_subscription_id',
        'last_billing_date',
        'next_billing_date',
        'ai_credits',
        'ai_credits_used',
        // available_credits is a GENERATED ALWAYS AS column — PHP reads it but cannot set it
        // Formula: GREATEST(0, base_credits + topup_credits - ai_credits_used)
        'base_credits',
        'topup_credits',
        'billing_cycle_id',
        'max_contacts',
        'max_products',
        'whatsapp_channels',
        'customer_followups',
        'customer_categorization',
        'booking_calendars',
        'sales_reports',
        'unlimited_messages',
        'status',
        'credits_rollover',
        'notes',
        // Trial tracking
        'trial_ends_at',
        // Webhook-related fields
        'subscription_status',
        'last_transaction_id',
        'last_payment_at',
        'last_payment_amount'
    ];

    protected $casts = [
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'last_billing_date' => 'datetime',
        'next_billing_date' => 'datetime',
        'ai_credits'       => 'integer',
        'ai_credits_used'  => 'integer',
        'available_credits' => 'integer',  // generated column — readable but not writable
        'base_credits'     => 'integer',
        'topup_credits'    => 'integer',
        'max_contacts'     => 'integer',
        'max_products' => 'integer',
        'whatsapp_channels' => 'integer',
        'customer_followups' => 'boolean',
        'customer_categorization' => 'boolean',
        'booking_calendars' => 'boolean',
        'sales_reports' => 'boolean',
        'unlimited_messages' => 'boolean',
        'credits_rollover' => 'boolean',
        // Trial tracking
        'trial_ends_at' => 'datetime',
        // Webhook-related casts
        'last_payment_at' => 'datetime',
        'last_payment_amount' => 'decimal:2'
    ];

    /**
     * Get the business that owns this billing account
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->subscription_expires_at === null || $this->subscription_expires_at->isFuture());
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isPast();
    }

    /**
     * Check if account has sufficient available credits.
     * Reads available_credits (PostgreSQL GENERATED column) which is always accurate.
     */
    public function hasCredits(int $amount = 1): bool
    {
        return ($this->available_credits ?? 0) >= $amount;
    }

    /**
     * Check if AI features can be used:
     *   - subscription must be active
     *   - subscription must not be expired
     *   - available_credits must be > 0
     */
    public function canUseAiFeatures(): bool
    {
        return $this->subscription_status === 'active'
            && ($this->subscription_expires_at === null || $this->subscription_expires_at->isFuture())
            && ($this->available_credits ?? 0) > 0;
    }

    /**
     * Deduct credits by incrementing ai_credits_used.
     *
     * Only ai_credits_used is modified — available_credits recomputes automatically
     * via the GENERATED ALWAYS AS column in PostgreSQL.
     *
     * ai_credits (total granted) is NOT touched here; it is managed exclusively
     * by the billing_credits_manager trigger.
     */
    public function deductCredits(int $amount, string $reason = null): bool
    {
        // Refresh from DB to get current generated available_credits value
        // (avoids stale in-memory values when the model was loaded earlier)
        $this->refresh();

        if (($this->available_credits ?? 0) < $amount) {
            return false;
        }

        // Atomic increment — safe under concurrent requests
        $this->increment('ai_credits_used', $amount);

        if ($reason) {
            \Log::info("Credits deducted from billing account {$this->id}: {$amount} credits. Reason: {$reason}");
        }

        return true;
    }

    /**
     * Add purchased top-up credits to the account.
     *
     * Increments topup_credits (never wiped on renewal) rather than ai_credits.
     * The billing_credits_manager trigger detects topup_credits increasing and logs
     * the event to credit_adjustments automatically.
     * available_credits recomputes via the GENERATED column.
     */
    public function addCredits(int $amount, string $reason = null): void
    {
        $this->increment('topup_credits', $amount);

        if ($reason) {
            \Log::info("Top-up credits added to billing account {$this->id}: {$amount} credits. Reason: {$reason}");
        }
    }

    /**
     * Get plan limits from config
     */
    public function getPlanLimits(): array
    {
        $config = config("safarichat_billing.plans.{$this->subscription_plan}");
        return $config['limits'] ?? [];
    }

    /**
     * Update limits based on plan
     */
    public function syncLimitsFromPlan(): void
    {
        $limits = $this->getPlanLimits();
        
        if (empty($limits)) {
            return;
        }

        $this->max_contacts = $limits['max_contacts'] ?? $this->max_contacts;
        $this->max_products = $limits['max_products'] ?? $this->max_products;
        $this->whatsapp_channels = $limits['whatsapp_channels'] ?? $this->whatsapp_channels;
        $this->customer_followups = $limits['customer_followups'] ?? $this->customer_followups;
        $this->customer_categorization = $limits['customer_categorization'] ?? $this->customer_categorization;
        $this->booking_calendars = $limits['booking_calendars'] ?? $this->booking_calendars;
        $this->sales_reports = $limits['sales_reports'] ?? $this->sales_reports;
        $this->unlimited_messages = $limits['unlimited_messages'] ?? $this->unlimited_messages;
        
        $this->save();
    }

    /**
     * Upgrade/downgrade subscription plan.
     *
     * Credit allocation is handled automatically by the billing_credits_manager
     * PostgreSQL trigger (SCENARIO 2/3) when subscription_plan changes.
     * PHP must NOT manually adjust ai_credits here.
     */
    public function changePlan(string $newPlan): void
    {
        $oldPlan = $this->subscription_plan;
        $this->subscription_plan = $newPlan;

        // Sync contact/product/channel limits from new plan config.
        // syncLimitsFromPlan() calls save() which fires the DB trigger,
        // which sets base_credits = new plan limit and ai_credits_used = 0.
        $this->syncLimitsFromPlan();

        $this->subscription_started_at = now();
        $this->save();

        \Log::info("Plan changed for billing account {$this->id}: {$oldPlan} -> {$newPlan}");
    }

    /**
     * Check if a feature is enabled
     */
    public function hasFeature(string $feature): bool
    {
        return match($feature) {
            'customer_followups' => $this->customer_followups,
            'customer_categorization' => $this->customer_categorization,
            'booking_calendars' => $this->booking_calendars,
            'sales_reports' => $this->sales_reports,
            'unlimited_messages' => $this->unlimited_messages,
            default => false
        };
    }

    /**
     * Get remaining credits percentage for the current billing cycle.
     * total = base_credits + topup_credits  (maintained in sync by trigger via ai_credits)
     * remaining = available_credits         (generated: GREATEST(0, total - ai_credits_used))
     */
    public function getCreditsPercentage(): float
    {
        $total = $this->ai_credits ?? 0;  // trigger keeps ai_credits = base + topup
        if ($total === 0) {
            return 0;
        }
        return (($this->available_credits ?? 0) / $total) * 100;
    }
}
