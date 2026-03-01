<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurtureLibrary extends Model
{
    use HasFactory;

    protected $table = 'nurture_library';

    protected $fillable = [
        'user_id',
        'business_id',
        'product_id',
        'is_business_level',
        'title',
        'content_type',
        'content_body',
        'content_url',
        'target_industry',
        'target_job_title',
        'target_pain_point',
        'target_lead_status',
        'seasonal_relevance',
        'language',
        'tone',
        'usage_count',
        'success_rate',
    ];

    protected $casts = [
        'is_business_level' => 'boolean',
        'usage_count' => 'integer',
        'success_rate' => 'decimal:2',
    ];

    /**
     * Get the user that owns the value nugget
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business that owns the value nugget
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the product this value nugget belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the analytics for this value nugget
     */
    public function analytics()
    {
        return $this->hasMany(NurtureAnalytics::class);
    }

    /**
     * Get message queue entries using this value nugget
     */
    public function messageQueueEntries()
    {
        return $this->hasMany(MessageQueue::class, 'nurture_library_id');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    /**
     * Update success rate based on analytics
     */
    public function updateSuccessRate()
    {
        $totalSent = $this->analytics()->count();
        if ($totalSent > 0) {
            $totalReplies = $this->analytics()->where('did_reply', true)->count();
            $this->success_rate = ($totalReplies / $totalSent) * 100;
            $this->save();
        }
    }

    /**
     * Scope: Get matching value nuggets for a contact
     */
    public function scopeMatchingForContact($query, $contact)
    {
        return $query
            ->where(function ($q) use ($contact) {
                $q->whereNull('target_industry')
                  ->orWhere('target_industry', $contact->industry);
            })
            ->where(function ($q) use ($contact) {
                $q->whereNull('target_job_title')
                  ->orWhere('target_job_title', 'LIKE', "%{$contact->job_title}%");
            })
            ->where(function ($q) use ($contact) {
                $q->whereNull('target_lead_status')
                  ->orWhere('target_lead_status', $contact->lead_status);
            })
            ->where('language', $contact->preferred_language ?? 'en')
            ->orderBy('success_rate', 'DESC');
    }

    /**
     * Scope: Product-specific messages
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId)
                     ->where('is_business_level', false);
    }

    /**
     * Scope: Business-level messages (fallback)
     */
    public function scopeBusinessLevel($query)
    {
        return $query->where('is_business_level', true)
                     ->whereNull('product_id');
    }
}
