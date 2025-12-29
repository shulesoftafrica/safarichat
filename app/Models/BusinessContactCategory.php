<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property Business $business
 * @property BusinessContact[] $businessContacts
 * 
 * Business Contact Categories - formerly EventGuestCategory
 * Categories for organizing business contacts/leads
 */
class BusinessContactCategory extends Model
{
    protected $table = 'business_contact_categories';
    
    /**
     * @var array
     */
    protected $fillable = [
        'business_id', 
        'name', 
        'created_at', 
        'updated_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Models\Business');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businessContacts()
    {
        return $this->hasMany('App\Models\BusinessContact', 'contact_category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->businessContacts();
    }

    /**
     * Get count of contacts in this category
     * @return int
     */
    public function getContactsCount()
    {
        return $this->businessContacts()->count();
    }

    /**
     * Get active contacts in this category (recently interacted)
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeContacts($hours = 48)
    {
        return $this->businessContacts()
            ->where(function($query) use ($hours) {
                $query->where('last_ai_interaction', '>=', now()->subHours($hours))
                      ->orWhere('last_human_interaction', '>=', now()->subHours($hours));
            });
    }

    /**
     * Get contacts needing handoff in this category
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contactsNeedingHandoff()
    {
        return $this->businessContacts()->needsHandoff();
    }
}