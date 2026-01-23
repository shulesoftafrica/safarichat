<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail{

    use HasFactory,
        Notifiable,
        HasApiTokens;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 
        'email', 
        'email_verified_at', 
        'phone_verified_at',
        'password', 
        'password_reset_at',
        'remember_token', 
        'created_at', 
        'updated_at', 
        'phone', 
        'user_type_id',
        'subscription_status',
        'trial_ends_at',
        'country_code',
        'available_credits',
        'whatsapp_number',
        'last_activity_at',
        'uuid',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password_reset_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'available_credits' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function business() {
        return $this->hasOne('App\Models\Business', 'user_id');
    }

    /**
     * Get the billing account (single source of truth for billing)
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function billingAccount() {
        return $this->belongsTo('App\Models\BillingAccount', 'billing_account_id');
    }

    /**
     * Get or create billing account for this user
     * @return \App\Models\BillingAccount
     */
    public function getOrCreateBillingAccount() {
        if ($this->billingAccount) {
            return $this->billingAccount;
        }

        // If user has a business, use business billing account
        if ($this->business && $this->business->billingAccount) {
            $this->billing_account_id = $this->business->billing_account_id;
            $this->save();
            return $this->business->billingAccount;
        }

        // Create new billing account for user
        $planConfig = config("safarichat_billing.plans.trial");
        $billingAccount = \App\Models\BillingAccount::create([
            'owner_type' => 'App\\Models\\User',
            'owner_id' => $this->id,
            'subscription_plan' => 'trial',
            'ai_credits' => $planConfig['limits']['ai_credits'] ?? 1000,
            'max_contacts' => $planConfig['limits']['max_contacts'] ?? 10,
            'max_products' => $planConfig['limits']['max_products'] ?? 1,
            'whatsapp_channels' => $planConfig['limits']['whatsapp_channels'] ?? 1,
        ]);

        $this->billing_account_id = $billingAccount->id;
        $this->save();

        return $billingAccount;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages() {
        return $this->hasMany('App\Models\Message');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function usersEvents() {
        return $this->hasOne('App\Models\Business', 'user_id');
    }

    /**
     * Get the active business campaign associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|null
     */
    public function event() {
        return $this->hasOne('App\Models\Business', 'user_id')
            ->whereNotNull('campaign_name')
            ->where('campaign_end_date', '>=', now())
            ->orWhereNull('campaign_end_date');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userKey() {
        return $this->hasMany('App\Models\UserKey');
    }

      /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fileAlbums() {
        return $this->hasMany('App\Models\FileAlbum');
    }
    
    // discountRequests relationship removed - table no longer exists
    /**
     * @return message left from the database
     */

     public function messagesLeft($channel = 'bulksms') {   
        // Use the new credits system - return available_credits for all channels
        return $this->available_credits ?? 0;
    }

    /**
     * Get the user's WhatsApp instance
     * @return \App\Models\WhatsappInstance|null
     */
    public function whatsappInstance() {
        // Get ready instance first
        $readyInstance = \App\Models\WhatsappInstance::where('user_id', $this->id)
            ->where('status', 'connected')
            ->orderBy('is_primary', 'desc')
            ->first();
            
        if ($readyInstance) {
            return $readyInstance;
        }
        
        // Get most recent instance
        return \App\Models\WhatsappInstance::where('user_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get all WhatsApp instances for this user
     */
    public function whatsappInstances()
    {
        return $this->hasMany(\App\Models\WhatsappInstance::class);
    }

    /**
     * Get incoming messages for user's instances
     */
    public function incomingMessages()
    {
        return $this->hasMany(\App\Models\IncomingMessage::class);
    }

    /**
     * Get outgoing messages for user's instances
     */
    public function outgoingMessages()
    {
        return $this->hasMany(\App\Models\OutgoingMessage::class);
    }

    /**
     * Get user's subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get user's active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    /**
     * Get user's credit transactions
     */
    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Get user's payment methods
     */
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get user's missed automations
     */
    public function missedAutomations()
    {
        return $this->hasMany(MissedAutomation::class);
    }

    /**
     * Get user's notifications
     */
    public function notifications()
    {
        return $this->hasMany(NotificationQueue::class);
    }

    /**
     * Get user's products
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get user's AI sales agents
     */
    public function aiSalesAgents()
    {
        return $this->hasMany(AiSalesAgent::class);
    }

    /**
     * Get handoffs assigned to this user (as an agent)
     */
    public function assignedHandoffs()
    {
        return $this->hasMany(Handoff::class, 'human_agent_id');
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get user's roles for role-based access control
     */
    public function roles()
    {
        // Simple role implementation - can be expanded later
        $roles = [];
        
        // Check if user is admin based on business ownership or other criteria
        if ($this->business && $this->business->user_id === $this->id) {
            $roles[] = 'business_owner';
        }
        
        // Check if user is an agent (has assigned handoffs)
        if ($this->assignedHandoffs()->exists()) {
            $roles[] = 'agent';
        }
        
        // Default role for all users
        $roles[] = 'user';
        
        return collect($roles);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        return $this->roles()->contains($role);
    }

    /**
     * Scope a query to only include active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
