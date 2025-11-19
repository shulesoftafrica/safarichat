<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class LeadProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'product_id', 'status', 'is_primary_product', 'quoted_price',
        'discount_applied', 'sales_notes', 'demo_scheduled_date', 'proposal_sent_date',
        'last_interaction_at', 'negotiation_history', 'follow_up_count',
        'next_followup_at', 'followup_scheduled_by_customer', 'is_active'
    ];

    protected $casts = [
        'is_primary_product' => 'boolean',
        'is_active' => 'boolean',
        'quoted_price' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'demo_scheduled_date' => 'date',
        'proposal_sent_date' => 'date',
        'last_interaction_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'followup_scheduled_by_customer' => 'datetime',
        'negotiation_history' => 'array',
        'follow_up_count' => 'integer'
    ];

    // Status constants
    const STATUS_INTERESTED = 'INTERESTED';
    const STATUS_PITCHED = 'PITCHED';
    const STATUS_DEMO_REQUESTED = 'DEMO_REQUESTED';
    const STATUS_DEMO_COMPLETED = 'DEMO_COMPLETED';
    const STATUS_PROPOSAL_SENT = 'PROPOSAL_SENT';
    const STATUS_NEGOTIATING = 'NEGOTIATING';
    const STATUS_CLOSED = 'CLOSED';
    const STATUS_LOST = 'LOST';

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary_product', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNeedingFollowup($query)
    {
        return $query->where('next_followup_at', '<=', Carbon::now())
                    ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_LOST]);
    }

    // Business Logic Methods
    public function calculateDiscountedPrice()
    {
        if (!$this->quoted_price) {
            return $this->product->base_price ?? $this->product->retail_price;
        }
        
        return $this->quoted_price;
    }

    public function getActualDiscount()
    {
        $basePrice = $this->product->base_price ?? $this->product->retail_price;
        $finalPrice = $this->quoted_price ?? $basePrice;
        
        if ($finalPrice >= $basePrice) {
            return 0;
        }
        
        return (($basePrice - $finalPrice) / $basePrice) * 100;
    }

    public function applyDiscount($discountPercentage, $reason = null)
    {
        $basePrice = $this->product->base_price ?? $this->product->retail_price;
        $maxDiscount = $this->product->max_discount_percentage ?? $this->product->max_discount;
        
        // Cap discount at product maximum
        $appliedDiscount = min($discountPercentage, $maxDiscount);
        $discountedPrice = $basePrice * (1 - $appliedDiscount / 100);
        
        // Record negotiation history
        $history = $this->negotiation_history ?? [];
        $history[] = [
            'date' => Carbon::now()->toISOString(),
            'action' => 'discount_applied',
            'discount_percentage' => $appliedDiscount,
            'old_price' => $this->quoted_price ?? $basePrice,
            'new_price' => $discountedPrice,
            'reason' => $reason
        ];
        
        $this->update([
            'quoted_price' => $discountedPrice,
            'discount_applied' => $appliedDiscount,
            'negotiation_history' => $history
        ]);

        return $this;
    }

    public function scheduleFollowup($delayHours = 24, $reason = null)
    {
        $followupAt = Carbon::now()->addHours($delayHours);
        
        $this->update([
            'next_followup_at' => $followupAt,
            'follow_up_count' => $this->follow_up_count + 1
        ]);

        return $this;
    }

    public function markAsCompleted($finalPrice = null)
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'quoted_price' => $finalPrice ?? $this->quoted_price,
            'is_active' => false,
            'next_followup_at' => null
        ]);

        // Update lead status if this was the primary product
        if ($this->is_primary_product) {
            $this->lead->update([
                'status' => Lead::STATUS_CLOSED,
                'final_price' => $finalPrice ?? $this->quoted_price
            ]);
        }

        return $this;
    }

    public function markAsLost($reason = null)
    {
        $history = $this->negotiation_history ?? [];
        $history[] = [
            'date' => Carbon::now()->toISOString(),
            'action' => 'marked_lost',
            'reason' => $reason
        ];

        $this->update([
            'status' => self::STATUS_LOST,
            'is_active' => false,
            'negotiation_history' => $history,
            'next_followup_at' => null
        ]);

        return $this;
    }

    public function getStatusColor()
    {
        return match($this->status) {
            self::STATUS_INTERESTED => 'info',
            self::STATUS_PITCHED, self::STATUS_DEMO_REQUESTED => 'warning',
            self::STATUS_DEMO_COMPLETED, self::STATUS_PROPOSAL_SENT => 'primary',
            self::STATUS_NEGOTIATING => 'secondary',
            self::STATUS_CLOSED => 'success',
            self::STATUS_LOST => 'danger',
            default => 'light'
        };
    }

    public function getFormattedPrice()
    {
        $price = $this->quoted_price ?? ($this->product->base_price ?? $this->product->retail_price);
        return '$' . number_format($price, 2);
    }

    public function getDaysInStatus()
    {
        $lastUpdate = $this->updated_at ?? $this->created_at;
        return $lastUpdate->diffInDays(Carbon::now());
    }

    public function isOverdue()
    {
        return $this->next_followup_at && $this->next_followup_at->lt(Carbon::now());
    }
}