<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'business_id', // User and business ownership
        'name', 'sku', 'category', 'description', 'retail_price', 'wholesale_price',
        'max_discount', 'quantity', 'tags', 'selling_points', 'status', 'ai_generated_description',
        'minimal_description', 'image_path', 'attachment_path', 'image_original_name',
        'attachment_original_name', 
        // AI Sales Agent fields
        'ai_description', 'base_price', 'max_discount_percentage', 'target_industry',
        'key_features', 'common_objections', 'sales_cycle_days', 'requires_demo',
        'has_trial', 'trial_days', 'setup_fee', 'pricing_model', 'billing_period',
        'upsell_products', 'min_stock_alert',
        // Service-specific fields
        'product_type', 'service_delivery_type', 'service_duration_days',
        'service_deliverables', 'requires_consultation', 'pricing_type',
        'hourly_rate', 'service_tiers', 'prerequisites',
        // Active Campaign fields
        'is_active_campaign', 'campaign_hook_text', 'campaign_pain_point', 'campaign_attachment_path'
    ];

    protected $casts = [
        'tags' => 'array',
        'selling_points' => 'array',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'ai_generated_description' => 'boolean',
        // AI Sales Agent casts
        'base_price' => 'decimal:2',
        'max_discount_percentage' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'key_features' => 'array',
        'common_objections' => 'array',
        'upsell_products' => 'array',
        'requires_demo' => 'boolean',
        'has_trial' => 'boolean',
        'trial_days' => 'integer',
        'sales_cycle_days' => 'integer',
        'min_stock_alert' => 'integer',
        // Service-specific casts
        'service_deliverables' => 'array',
        'service_tiers' => 'array',
        'prerequisites' => 'array',
        'hourly_rate' => 'decimal:2',
        'requires_consultation' => 'boolean',
        'service_duration_days' => 'integer',
        // Active Campaign casts
        'is_active_campaign' => 'boolean'
    ];

    // === RELATIONSHIPS ===
    
    /**
     * Get the user that owns the product.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business that owns the product.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the FAQs for this product
     */
    public function faqs()
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    /**
     * Get lead products associated with this product
     */
    public function leadProducts()
    {
        return $this->hasMany(LeadProduct::class);
    }

    /**
     * Get conversations about this product
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get nurture messages for this product
     */
    public function nurtureMessages()
    {
        return $this->hasMany(NurtureLibrary::class, 'product_id')
            ->orderBy('success_rate', 'DESC');
    }

    /**
     * Get active nurture messages (used recently or high success rate)
     */
    public function activeNurtureMessages()
    {
        return $this->nurtureMessages()
            ->where(function ($query) {
                $query->where('usage_count', '>', 0)
                      ->orWhere('created_at', '>=', now()->subDays(30));
            });
    }

    /**
     * Get leads through lead products
     */
    public function leads()
    {
        return $this->hasManyThrough(Lead::class, LeadProduct::class);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock()
    {
        return $this->quantity === null || $this->quantity > 0;
    }

    /**
     * Get stock status (updated to handle services)
     */
    public function getStockStatusAttribute()
    {
        if ($this->isService()) {
            return 'available'; // Services don't have stock
        }
        
        if ($this->quantity === null) {
            return 'unlimited';
        }
        
        if ($this->quantity == 0) {
            return 'out_of_stock';
        }
        
        if ($this->quantity <= 5) {
            return 'very_low';
        }
        
        if ($this->quantity <= 25) {
            return 'low';
        }
        
        return 'in_stock';
    }

    /**
     * Get stock status color
     */
    public function getStockStatusColorAttribute()
    {
        $status = $this->getStockStatusAttribute();
        
        switch ($status) {
            case 'unlimited':
            case 'in_stock':
                return 'success';
            case 'low':
                return 'warning';
            case 'very_low':
            case 'out_of_stock':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * Get stock status text (updated to handle services)
     */
    public function getStockStatusTextAttribute()
    {
        if ($this->isService()) {
            return 'Available';
        }
        
        $status = $this->stock_status;
        
        switch ($status) {
            case 'unlimited':
                return 'Unlimited';
            case 'in_stock':
                return 'In Stock';
            case 'low':
                return 'Low Stock';
            case 'very_low':
                return 'Very Low';
            case 'out_of_stock':
                return 'Out of Stock';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get formatted retail price
     */
    public function getFormattedRetailPriceAttribute()
    {
        return '$' . number_format($this->retail_price, 2);
    }

    /**
     * Get formatted wholesale price
     */
    public function getFormattedWholesalePriceAttribute()
    {
        return '$' . number_format($this->wholesale_price, 2);
    }

    /**
     * Calculate discounted price
     */
    public function getDiscountedPrice($discountPercent)
    {
        $discount = min($discountPercent, $this->max_discount);
        return $this->retail_price * (1 - $discount / 100);
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for products owned by a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for products owned by a specific business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for active products owned by a specific user
     */
    public function scopeUserActive($query, $userId)
    {
        return $query->where('user_id', $userId)->where('status', 'active');
    }

    /**
     * Scope for active products owned by a specific business
     */
    public function scopeBusinessActive($query, $businessId)
    {
        return $query->where('business_id', $businessId)->where('status', 'active');
    }

    /**
     * Scope for in stock products
     */
    public function scopeInStock($query)
    {
        return $query->where(function($q) {
            $q->whereNull('quantity')->orWhere('quantity', '>', 0);
        });
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Get the full URL for the product image
     */
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return asset('images/default-product.png'); // Default image
    }

    /**
     * Get the full URL for the product attachment
     */
    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment_path) {
            return asset('storage/' . $this->attachment_path);
        }
        return null;
    }

    /**
     * Check if product has image
     */
    public function hasImage()
    {
        return !empty($this->image_path);
    }

    /**
     * Check if product has attachment
     */
    public function hasAttachment()
    {
        return !empty($this->attachment_path);
    }

    public function getImageFile()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return asset('images/default-product.png');
    }

    // AI Sales Agent Methods
    
    /**
     * Get AI optimized description
     */
    public function getAiDescription()
    {
        return $this->ai_description ?: $this->description;
    }

    /**
     * Get base price (fallback to retail_price)
     */
    public function getBasePriceAttribute()
    {
        return $this->attributes['base_price'] ?? $this->retail_price;
    }

    /**
     * Get max discount percentage (fallback to max_discount)
     */
    public function getMaxDiscountPercentageAttribute()
    {
        return $this->attributes['max_discount_percentage'] ?? $this->max_discount;
    }

    /**
     * Calculate conversion rate for this product
     */
    public function getConversionRate()
    {
        $totalLeads = $this->leadProducts()->count();
        $closedLeads = $this->leadProducts()->where('status', 'CLOSED')->count();
        
        return $totalLeads > 0 ? ($closedLeads / $totalLeads) * 100 : 0;
    }

    /**
     * Check if price can be discounted to target
     */
    public function canBeDiscountedTo($targetPrice)
    {
        $basePrice = $this->base_price ?: $this->retail_price;
        $maxDiscountPct = $this->max_discount_percentage ?: $this->max_discount;
        
        $maxDiscountAmount = ($basePrice * $maxDiscountPct) / 100;
        $minimumPrice = $basePrice - $maxDiscountAmount;
        
        return $targetPrice >= $minimumPrice;
    }

    /**
     * Get minimum allowed price
     */
    public function getMinimumPrice()
    {
        $basePrice = $this->base_price ?: $this->retail_price;
        $maxDiscountPct = $this->max_discount_percentage ?: $this->max_discount;
        
        $maxDiscountAmount = ($basePrice * $maxDiscountPct) / 100;
        return $basePrice - $maxDiscountAmount;
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock()
    {
        $threshold = $this->min_stock_alert ?: 5;
        return $this->quantity !== null && $this->quantity <= $threshold;
    }

    /**
     * Get key features as text
     */
    public function getKeyFeaturesText()
    {
        return is_array($this->key_features) ? 
               implode(', ', $this->key_features) : 
               ($this->key_features ?: '');
    }

    /**
     * Get upsell products
     */
    public function getUpsellProducts()
    {
        if (!$this->upsell_products) {
            return collect();
        }

        return static::whereIn('id', $this->upsell_products)
                     ->active()
                     ->get();
    }

    // === NEW SERVICE & RAG METHODS ===

    /**
     * Get all attachments for this product
     */
    public function attachments()
    {
        return $this->hasMany(ProductAttachment::class)->orderBy('display_order');
    }

    /**
     * Get document vectors for RAG
     */
    public function documentVectors()
    {
        return $this->hasMany(DocumentVector::class);
    }

    /**
     * Check if this is a service (non-tangible)
     */
    public function isService(): bool
    {
        return $this->product_type === 'service';
    }

    /**
     * Check if this is a tangible product
     */
    public function isTangible(): bool
    {
        return $this->product_type === 'tangible';
    }

    /**
     * Get attachments by type
     */
    public function getAttachmentsByType(string $type)
    {
        return $this->attachments()->where('attachment_type', $type)->get();
    }

    /**
     * Check if product should track inventory
     */
    public function tracksInventory(): bool
    {
        return $this->isTangible() && $this->quantity !== null;
    }

    /**
     * Get AI-optimized context for services
     */
    public function getAiServiceContext(): ?string
    {
        if (!$this->isService()) {
            return null;
        }

        $context = "Service Details:\n";
        $context .= "- Type: " . ucfirst($this->service_delivery_type ?? 'Not specified') . "\n";
        
        if ($this->service_duration_days) {
            $context .= "- Typical Duration: {$this->service_duration_days} days\n";
        }
        
        if ($this->service_deliverables) {
            $context .= "- Deliverables: " . implode(', ', $this->service_deliverables) . "\n";
        }
        
        if ($this->requires_consultation) {
            $context .= "- Consultation Required: Yes (schedule before engagement)\n";
        }
        
        if ($this->pricing_type) {
            $context .= "- Pricing: " . ucfirst($this->pricing_type);
            if ($this->hourly_rate) {
                $context .= " (\${$this->hourly_rate}/hour)";
            }
            $context .= "\n";
        }
        
        // Available resources
        $attachments = $this->attachments()->where('is_public', true)->processed()->get();
        if ($attachments->count() > 0) {
            $context .= "- Available Resources: ";
            $context .= $attachments->pluck('title')->implode(', ') . "\n";
        }
        
        return $context;
    }

    /**
     * Scope for services only
     */
    public function scopeServices($query)
    {
        return $query->where('product_type', 'service');
    }

    /**
     * Scope for tangible products only
     */
    public function scopeTangible($query)
    {
        return $query->where('product_type', 'tangible');
    }

    /**
     * Get formatted pricing for services
     */
    public function getFormattedPricingAttribute(): string
    {
        if ($this->isService() && $this->pricing_type) {
            switch ($this->pricing_type) {
                case 'hourly':
                    return $this->hourly_rate ? "$" . number_format($this->hourly_rate, 2) . "/hour" : "Hourly rate";
                case 'daily':
                    return "Daily rate";
                case 'project':
                    return "Project-based: $" . number_format($this->retail_price, 2);
                case 'subscription':
                    return "Subscription: $" . number_format($this->retail_price, 2);
                case 'one-time':
                    return "One-time: $" . number_format($this->retail_price, 2);
                default:
                    return "$" . number_format($this->retail_price, 2);
            }
        }
        
        return "$" . number_format($this->retail_price, 2);
    }

    /**
     * Check if product has RAG-processed documents
     */
    public function hasRAGDocuments(): bool
    {
        return $this->attachments()->processed()->exists();
    }

    /**
     * Get count of processed documents
     */
    public function getProcessedDocumentsCountAttribute(): int
    {
        return $this->attachments()->processed()->count();
    }

    /**
     * Get total vector count for this product
     */
    public function getTotalVectorCountAttribute(): int
    {
        return $this->documentVectors()->count();
    }

    // === ACTIVE CAMPAIGN METHODS ===
    
    /**
     * Get the active campaign product for a user
     */
    public static function getActiveCampaign($userId)
    {
        return self::where('user_id', $userId)
                   ->where('is_active_campaign', true)
                   ->first();
    }

    /**
     * Set this product as the active campaign
     * Automatically deactivates all other campaigns for the user
     */
    public function setAsActiveCampaign()
    {
        \DB::transaction(function () {
            // Deactivate all other campaigns for this user
            self::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->update(['is_active_campaign' => false]);
            
            // Activate this campaign
            $this->is_active_campaign = true;
            $this->save();
        });
    }

    /**
     * Deactivate this campaign
     */
    public function deactivateCampaign()
    {
        $this->is_active_campaign = false;
        $this->save();
    }

    /**
     * Check if product has complete campaign data
     */
    public function hasCompleteCampaignData(): bool
    {
        return !empty($this->campaign_hook_text) 
            && !empty($this->campaign_pain_point);
    }

    /**
     * Check if product has a campaign attachment
     */
    public function hasCampaignAttachment(): bool
    {
        return !empty($this->campaign_attachment_path) 
            && file_exists(storage_path('app/public/' . $this->campaign_attachment_path));
    }

    /**
     * Get the full path to the campaign attachment
     */
    public function getCampaignAttachmentFullPath(): ?string
    {
        if ($this->campaign_attachment_path) {
            return storage_path('app/public/' . $this->campaign_attachment_path);
        }
        return null;
    }

    /**
     * Get the public URL for the campaign attachment
     */
    public function getCampaignAttachmentUrl(): ?string
    {
        if ($this->campaign_attachment_path) {
            return asset('storage/' . $this->campaign_attachment_path);
        }
        return null;
    }
}
