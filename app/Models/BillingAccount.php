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
        'available_credits',
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
        'ai_credits' => 'integer',
        'ai_credits_used' => 'integer',
        'available_credits' => 'integer',
        'max_contacts' => 'integer',
        'max_products' => 'integer',
        'whatsapp_channels' => 'integer',
        'customer_followups' => 'boolean',
        'customer_categorization' => 'boolean',
        'booking_calendars' => 'boolean',
        'sales_reports' => 'boolean',
        'unlimited_messages' => 'boolean',
        'credits_rollover' => 'boolean',
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
     * Check if account has sufficient credits
     */
    public function hasCredits(int $amount = 1): bool
    {
        return $this->ai_credits >= $amount;
    }

    /**
     * Deduct credits from account
     */
    public function deductCredits(int $amount, string $reason = null): bool
    {
        if (!$this->hasCredits($amount)) {
            return false;
        }

        $this->ai_credits -= $amount;
        $this->ai_credits_used += $amount;
        $this->save();

        // Log the transaction if needed
        if ($reason) {
            \Log::info("Credits deducted from billing account {$this->id}: {$amount} credits. Reason: {$reason}");
        }

        return true;
    }

    /**
     * Add credits to account
     */
    public function addCredits(int $amount, string $reason = null): void
    {
        $this->ai_credits += $amount;
        $this->save();

        if ($reason) {
            \Log::info("Credits added to billing account {$this->id}: {$amount} credits. Reason: {$reason}");
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
     * Upgrade/downgrade subscription plan
     */
    public function changePlan(string $newPlan, bool $addCredits = true): void
    {
        $oldPlan = $this->subscription_plan;
        $this->subscription_plan = $newPlan;
        
        // Sync limits from new plan
        $this->syncLimitsFromPlan();
        
        // Add credits based on new plan
        if ($addCredits) {
            $limits = $this->getPlanLimits();
            if (isset($limits['ai_credits'])) {
                $this->ai_credits += $limits['ai_credits'];
            }
        }
        
        $this->subscription_started_at = now();
        $this->next_billing_date = now()->addMonth();
        
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
     * Get remaining credits percentage
     */
    public function getCreditsPercentage(): float
    {
        $total = $this->ai_credits + $this->ai_credits_used;
        if ($total === 0) {
            return 0;
        }
        return ($this->ai_credits / $total) * 100;
    }
}
