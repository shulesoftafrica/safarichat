<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'description',
        'retail_price',
        'wholesale_price',
        'max_discount',
        'quantity',
        'tags',
        'status',
        'ai_generated_description',
        'minimal_description',
        'image_path',
        'attachment_path',
        'image_original_name',
        'attachment_original_name'
    ];

    protected $casts = [
        'tags' => 'array',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'ai_generated_description' => 'boolean'
    ];

    /**
     * Get the FAQs for this product
     */
    public function faqs()
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
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
}
