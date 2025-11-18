<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSalesAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assistant_name',
        'status',
        'target_audience',
        'target_user_types',
        'industries',
        'communication_tone',
        'personality_description',
        'always_available',
        'business_days',
        'start_time',
        'end_time',
        'timezone',
        'out_of_hours_message',
        'primary_language',
        'additional_languages',
        'auto_detect_language',
        'language_fallback_message',
        'allow_negotiation',
        'max_discount_allowed',
        'accept_installments',
        'max_installments',
        'min_down_payment',
        'stop_orders_low_stock',
        'low_stock_threshold',
        'negotiation_script',
        'fallback_number',
        'fallback_person',
        'escalation_triggers',
        'large_order_threshold',
        'auto_followup',
        'followup_delay',
        'max_followups',
        'followup_message',
        'notify_on_deal',
        'notification_methods',
        'additional_notifications',
        'accepted_terms',
        'terms_accepted_at'
    ];

    protected $casts = [
        'target_user_types' => 'array',
        'industries' => 'array',
        'business_days' => 'array',
        'additional_languages' => 'array',
        'escalation_triggers' => 'array',
        'notification_methods' => 'array',
        'additional_notifications' => 'array',
        'always_available' => 'boolean',
        'auto_detect_language' => 'boolean',
        'allow_negotiation' => 'boolean',
        'accept_installments' => 'boolean',
        'stop_orders_low_stock' => 'boolean',
        'auto_followup' => 'boolean',
        'notify_on_deal' => 'boolean',
        'accepted_terms' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'large_order_threshold' => 'decimal:2'
    ];

    /**
     * Get the user that owns the AI sales agent
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the target user types for this agent
     */
    public function targetUserTypes()
    {
        return $this->belongsToMany(UserType::class, 'target_user_types', 'ai_sales_agent_id', 'user_type_id');
    }

    /**
     * Scope for active agents
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for agents by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if agent is available at current time
     */
    public function isAvailableNow()
    {
        if ($this->always_available) {
            return true;
        }

        $now = now($this->timezone);
        $currentDay = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        return in_array($currentDay, $this->business_days ?? []) &&
               $currentTime >= $this->start_time &&
               $currentTime <= $this->end_time;
    }

    /**
     * Get formatted business hours
     */
    public function getFormattedBusinessHours()
    {
        if ($this->always_available) {
            return '24/7 Available';
        }

        $days = implode(', ', array_map('ucfirst', $this->business_days ?? []));
        return "{$days}, {$this->start_time} - {$this->end_time} ({$this->timezone})";
    }

    /**
     * Get targeted user type names
     */
    public function getTargetUserTypeNames()
    {
        if (!$this->target_user_types) {
            return [];
        }

        return UserType::whereIn('id', $this->target_user_types)->pluck('name')->toArray();
    }
}
