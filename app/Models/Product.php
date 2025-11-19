<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'sku', 'category', 'description', 'retail_price', 'wholesale_price',
        'max_discount', 'quantity', 'tags', 'status', 'ai_generated_description',
        'minimal_description', 'image_path', 'attachment_path', 'image_original_name',
        'attachment_original_name', 
        // AI Sales Agent fields
        'ai_description', 'base_price', 'max_discount_percentage', 'target_industry',
        'key_features', 'common_objections', 'sales_cycle_days', 'requires_demo',
        'has_trial', 'trial_days', 'setup_fee', 'pricing_model', 'billing_period',
        'upsell_products', 'min_stock_alert'
    ];

    protected $casts = [
        'tags' => 'array',
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
        'min_stock_alert' => 'integer'
    ];

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
     * Get stock status
     */
    public function getStockStatusAttribute()
    {
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
     * Get stock status text
     */
    public function getStockStatusTextAttribute()
    {
        $status = $this->getStockStatusAttribute();
        
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
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, storage_path('app/public/' . $this->image_path));
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
}
