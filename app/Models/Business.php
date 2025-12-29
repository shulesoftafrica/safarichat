<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $ward_id
 * @property string $address
 * @property string $descriptions
 * @property string $created_at
 * @property string $updated_at
 * @property Ward $ward
 * @property User $user
 * @property BusinessService[] $businessServices
 */
class Business extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'user_id', 'ward_id', 'address', 'descriptions', 'created_at', 'updated_at',
        'name', 'email', 'phone', 'website', 'instagram', 'facebook', 'linkedin', 
        'cover_page', 'twitter', 'legal_document', 'business_type_id',
        // Company Credibility Kit fields
        'mission', 'credibility_statistics',
        // Consolidated Events functionality (Phase 1)
        'campaign_name', 'campaign_start_date', 'campaign_end_date', 'campaign_uid',
        'whatsapp_api_url', 'whatsapp_token', 'business_category', 'district_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ward()
    {
        return $this->belongsTo('App\Models\Ward');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businessServices()
    {
        // This assumes there is a 'business_services' table with a 'business_id' foreign key.
        return $this->hasMany('App\Models\BusinessService', 'business_id');
    }
    
    /**
     * @deprecated EventsGuest model removed - use businessContacts() instead
     */
    public function businessGuests()
    {
        throw new \Exception('EventsGuest model has been removed. Use businessContacts() relationship instead.');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * Business contacts (formerly guests) - main contact management
     */
    public function businessContacts()
    {
        return $this->hasMany('App\Models\BusinessContact', 'business_id');
    }
    
    /**
     * Alias for businessContacts for simpler access
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->businessContacts();
    }
    
    /**
     * @deprecated EventGuestCategory model removed - use businessContactCategories() instead
     */
    public function guestCategories()
    {
        throw new \Exception('EventGuestCategory model has been removed. Use businessContactCategories() relationship instead.');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * Business contact categories for organizing contacts
     */
    public function businessContactCategories()
    {
        return $this->hasMany('App\Models\BusinessContactCategory', 'business_id');
    }
    
    /**
     * Alias for businessContactCategories for simpler access
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contactCategories()
    {
        return $this->businessContactCategories();
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leads()
    {
        return $this->hasMany('App\Models\Lead', 'business_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgets()
    {
        return $this->hasMany('App\Models\Budget', 'business_id');
    }
  
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * Method to get service
     */
    public function service(){
        return $this->belongsTo('App\Models\Service');
    }

    // === CREDIBILITY KIT METHODS ===
    
    /**
     * Check if business has complete credibility data
     */
    public function hasCompleteCredibilityKit(): bool
    {
        return !empty($this->mission) 
            && !empty($this->credibility_statistics) 
            && !empty($this->website);
    }

    /**
     * Get formatted credibility data for AI prompts
     */
    public function getCredibilityDataForAI(): array
    {
        return [
            'company_name' => $this->name ?? 'Our Company',
            'mission' => $this->mission ?? '',
            'credibility' => $this->credibility_statistics ?? '',
            'website' => $this->website ?? '',
            'contact_email' => $this->email ?? '',
            'contact_phone' => $this->phone ?? ''
        ];
    }

    // === CONSOLIDATED CAMPAIGN/EVENT METHODS ===
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function businessType()
    {
        return $this->belongsTo('App\Models\BusinessType', 'business_type_id');
    }

    /**
     * Check if business has an active campaign
     */
    public function hasActiveCampaign(): bool
    {
        return !empty($this->campaign_name) 
            && (!$this->campaign_end_date || $this->campaign_end_date >= now());
    }

    /**
     * Get campaign status
     */
    public function getCampaignStatus(): string
    {
        if (empty($this->campaign_name)) return 'no_campaign';
        
        if ($this->campaign_end_date && $this->campaign_end_date < now()) {
            return 'expired';
        }
        
        if ($this->campaign_start_date && $this->campaign_start_date > now()) {
            return 'scheduled';
        }
        
        return 'active';
    }

    /**
     * Get WhatsApp integration status
     */
    public function hasWhatsAppIntegration(): bool
    {
        return !empty($this->whatsapp_api_url) && !empty($this->whatsapp_token);
    }
}
