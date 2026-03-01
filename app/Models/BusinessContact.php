<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\UserResolutionService;

/**
 * @property int $id
 * @property int $business_id
 * @property string $guest_name
 * @property string $guest_email
 * @property string $guest_email_verified_at
 * @property string $guest_phone
 * @property int $contact_category_id
 * @property float $guest_pledge
 * @property string $created_at
 * @property string $updated_at
 * @property string $code
 * @property boolean $contacted_for_sales
 * @property string $contacted_at
 * @property string $handoff_status
 * @property int $assigned_agent_id
 * @property string $handoff_reason
 * @property string $handoff_notes
 * @property string $priority_level
 * @property string $handoff_requested_at
 * @property string $handoff_assigned_at
 * @property string $handoff_completed_at
 * @property string $last_ai_interaction
 * @property string $last_human_interaction
 * @property int $user_id
 * @property string $crm_id
 * @property string $crm_data
 * @property Business $business
 * @property BusinessContactCategory $contactCategory
 * @property Message[] $messages
 * 
 * Business Contact Management - formerly EventsGuest
 * Represents contacts/leads for WhatsApp business campaigns
 */
class BusinessContact extends Model
{
    protected $table = 'business_contacts';
    
    /**
     * @var array
     */
    protected $fillable = [
        'business_id',
        'user_id',
        'guest_name',
        'guest_email', 
        'guest_email_verified_at',
        'guest_phone',
        'contact_category_id',
        'guest_pledge',
        'code',
        'contacted_for_sales',
        'contacted_at',
        'handoff_status',
        'assigned_agent_id', 
        'handoff_reason',
        'handoff_notes',
        'priority_level',
        'handoff_requested_at',
        'handoff_assigned_at', 
        'handoff_completed_at',
        'last_ai_interaction',
        'last_human_interaction',
        'crm_id',
        'crm_data',
        // AI Personalization fields
        'preferred_language',
        'preferred_tone',
        'last_message_sentiment',
        'opt_out_status',
        'opt_out_at',
        'avg_reply_hour',
        'engagement_score'
    ];

    /**
     * @var array
     */
    protected $hidden = ['updated_at'];

    /**
     * @var array
     */
    protected $casts = [
        'opt_out_status' => 'boolean',
        'opt_out_at' => 'datetime',
        'engagement_score' => 'decimal:2',
        'contacted_for_sales' => 'boolean',
        'contacted_at' => 'datetime',
        'handoff_requested_at' => 'datetime',
        'handoff_assigned_at' => 'datetime',
        'handoff_completed_at' => 'datetime',
        'last_ai_interaction' => 'datetime',
        'last_human_interaction' => 'datetime'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Models\Business');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contactCategory()
    {
        return $this->belongsTo('App\Models\BusinessContactCategory', 'contact_category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @deprecated Alias for contactCategory() - for backward compatibility
     */
    public function eventGuestCategory()
    {
        return $this->contactCategory();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany('App\Models\Message', 'business_contact_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function incomingMessages()
    {
        return $this->hasMany('App\Models\IncomingMessage', 'business_contact_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function outgoingMessages()
    {
        return $this->hasMany('App\Models\OutgoingMessage', 'business_contact_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedAgent()
    {
        return $this->belongsTo('App\Models\User', 'assigned_agent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leads()
    {
        return $this->hasMany('App\Models\Lead', 'business_contact_id');
    }

    /**
     * Get the primary lead for this contact (singular relationship)
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lead()
    {
        return $this->hasOne('App\Models\Lead', 'business_contact_id')->latest();
    }

    /**
     * Get the user who created this contact
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * Get all queued campaign messages for this contact
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messageQueue()
    {
        return $this->hasMany('App\Models\MessageQueue', 'contact_id');
    }

    /**
     * Check if contact requires handoff to human agent
     * @return bool
     */
    public function needsHandoff()
    {
        return in_array($this->handoff_status, ['requested', 'pending']);
    }

    /**
     * Check if contact is actively being handled by human agent
     * @return bool
     */
    public function isHandedOff()
    {
        return $this->handoff_status === 'assigned' && $this->assigned_agent_id;
    }

    /**
     * Get contact's priority level for agent assignment
     * @return string
     */
    public function getPriorityLevel()
    {
        return $this->priority_level ?? 'normal';
    }

    /**
     * Mark contact as requiring sales follow-up
     * @return void
     */
    public function markForSalesContact()
    {
        $this->contacted_for_sales = true;
        $this->contacted_at = now();
        $this->save();
    }

    /**
     * Update last AI interaction timestamp
     * @return void
     */
    public function updateAiInteraction()
    {
        $this->last_ai_interaction = now();
        $this->save();
    }

    /**
     * Update last human interaction timestamp
     * @return void
     */
    public function updateHumanInteraction()
    {
        $this->last_human_interaction = now();
        $this->save();
    }

    /**
     * Request handoff to human agent
     * @param string $reason
     * @param string $notes
     * @param string $priority
     * @return void
     */
    public function requestHandoff($reason = null, $notes = null, $priority = 'normal')
    {
        $this->handoff_status = 'requested';
        $this->handoff_reason = $reason;
        $this->handoff_notes = $notes;
        $this->priority_level = $priority;
        $this->handoff_requested_at = now();
        $this->save();
    }

    /**
     * Assign contact to human agent
     * @param int $agentId
     * @return void
     */
    public function assignToAgent($agentId)
    {
        $this->handoff_status = 'assigned';
        $this->assigned_agent_id = $agentId;
        $this->handoff_assigned_at = now();
        $this->save();
    }

    /**
     * Complete handoff process
     * @param string $notes
     * @return void
     */
    public function completeHandoff($notes = null)
    {
        $this->handoff_status = 'completed';
        if ($notes) {
            $this->handoff_notes = ($this->handoff_notes ? $this->handoff_notes . '\n' : '') . $notes;
        }
        $this->handoff_completed_at = now();
        $this->save();
    }

    /**
     * Scope for contacts needing handoff
     */
    public function scopeNeedsHandoff($query)
    {
        return $query->whereIn('handoff_status', ['requested', 'pending']);
    }

    /**
     * Scope for high priority contacts
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority_level', 'high');
    }

    /**
     * Scope for contacts with recent AI interactions
     */
    public function scopeRecentlyActive($query, $hours = 24)
    {
        return $query->where('last_ai_interaction', '>=', now()->subHours($hours));
    }
}