<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable audit log for every credit balance adjustment.
 *
 * Written by the PostgreSQL trigger (billing_credits_manager) automatically
 * on renewal, plan change, and top-up events.
 * Also written by EnforceSubscriptionExpiry command for expiry events.
 *
 * This table is append-only — records are never updated or deleted.
 */
class CreditAdjustment extends Model
{
    // No updated_at — this is an append-only log
    public $timestamps = false;

    protected $fillable = [
        'billing_account_id',
        'adjustment_type',
        'plan_before',
        'plan_after',
        'base_credits_before',
        'base_credits_after',
        'topup_credits_before',
        'topup_credits_after',
        'ai_credits_used_before',
        'ai_credits_used_after',
        'billing_cycle_id',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'base_credits_before'    => 'integer',
        'base_credits_after'     => 'integer',
        'topup_credits_before'   => 'integer',
        'topup_credits_after'    => 'integer',
        'ai_credits_used_before' => 'integer',
        'ai_credits_used_after'  => 'integer',
        'created_at'             => 'datetime',
    ];

    /**
     * The billing account this adjustment belongs to.
     */
    public function billingAccount()
    {
        return $this->belongsTo(BillingAccount::class);
    }

    /**
     * Credits gained in this adjustment (base_credits increase).
     */
    public function creditsGranted(): int
    {
        return max(0, ($this->base_credits_after ?? 0) - ($this->base_credits_before ?? 0));
    }

    /**
     * Credits used that were cleared by this adjustment (reset on renewal).
     */
    public function usageClearedOnReset(): int
    {
        return max(0, ($this->ai_credits_used_before ?? 0) - ($this->ai_credits_used_after ?? 0));
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('adjustment_type', $type);
    }

    /**
     * Scope to filter to renewal and upgrade/downgrade events only.
     */
    public function scopeSubscriptionEvents($query)
    {
        return $query->whereIn('adjustment_type', ['renewal', 'upgrade', 'downgrade', 'expiry']);
    }
}
