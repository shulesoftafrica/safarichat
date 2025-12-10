<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AiSalesAgent extends Model
{
    use HasFactory;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
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

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

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
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'max_discount_allowed' => 'integer',
        'max_installments' => 'integer',
        'min_down_payment' => 'integer',
        'low_stock_threshold' => 'integer',
        'followup_delay' => 'integer',
        'max_followups' => 'integer',
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
     * Get leads associated with this agent
     */
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get conversations associated with this agent
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the target user types for this agent
     * Note: target_user_types is stored as JSON field, not a separate table
     */
    public function targetUserTypes()
    {
        if (!$this->target_user_types || !is_array($this->target_user_types)) {
            return collect([]);
        }
        
        return UserType::whereIn('id', $this->target_user_types)->get();
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
        if (!$this->target_user_types || !is_array($this->target_user_types) || empty($this->target_user_types)) {
            return [];
        }

        try {
            return UserType::whereIn('id', $this->target_user_types)->pluck('name')->toArray();
        } catch (\Exception $e) {
            \Log::error('Error getting target user type names: ' . $e->getMessage(), [
                'agent_id' => $this->id,
                'target_user_types' => $this->target_user_types
            ]);
            return [];
        }
    }

    /**
     * Check if agent can negotiate within discount limits
     */
    public function canNegotiate($requestedDiscount)
    {
        return $this->allow_negotiation && 
               ($this->max_discount_allowed >= $requestedDiscount);
    }

    /**
     * Check if situation should escalate to human agent
     */
    public function shouldEscalate($trigger)
    {
        return in_array($trigger, $this->escalation_triggers ?? []);
    }

    /**
     * Get personality prompt for OpenAI
     */
    public function getPersonalityPrompt()
    {
        $tone = match($this->communication_tone) {
            'professional' => 'Maintain a professional, business-focused tone',
            'friendly' => 'Be warm, approachable, and conversational',
            'consultative' => 'Act as a trusted advisor, asking thoughtful questions',
            'direct' => 'Be clear, concise, and straight to the point'
        };
        
        $personality = $this->personality_description ?: "I am {$this->assistant_name}, a helpful sales assistant.";
        
        return "{$personality} {$tone}. Target audience: {$this->target_audience}.";
    }

    /**
     * Get out of hours response message
     */
    public function getOutOfHoursResponse()
    {
        return $this->out_of_hours_message ?: 
               "Thank you for your message! I'm currently unavailable, but I'll respond during business hours: {$this->start_time} - {$this->end_time} {$this->timezone}.";
    }

    /**
     * Get follow-up configuration
     */
    public function getFollowupSettings()
    {
        return [
            'enabled' => $this->auto_followup,
            'delay_hours' => $this->followup_delay ?? 24,
            'max_attempts' => $this->max_followups ?? 3,
            'message' => $this->followup_message
        ];
    }
}
