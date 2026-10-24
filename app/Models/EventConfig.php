<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * An owner-configured conversion event. Each config exposes its own webhook URL
 * (/api/events/hook/{token}) and encodes what to do when the event fires.
 */
class EventConfig extends Model
{
    protected $table = 'event_configs';

    protected $fillable = [
        'business_id',
        'ai_sales_agent_id',
        'name',
        'event_type',
        'token',
        'is_enabled',
        'set_status',
        'actions',
        'message_template',
        'match_by',
        'field_map',
        'last_received_at',
        'received_count',
        'last_error',
    ];

    protected $casts = [
        'is_enabled'       => 'boolean',
        'actions'          => 'array',
        'field_map'        => 'array',
        'last_received_at' => 'datetime',
        'received_count'   => 'integer',
    ];

    // Event types the UI offers.
    const TYPE_PURCHASE       = 'purchase';
    const TYPE_PAYMENT        = 'payment';
    const TYPE_FORM_SUBMITTED = 'form_submitted';
    const TYPE_SIGNUP         = 'signup';
    const TYPE_REGISTRATION   = 'registration';
    const TYPE_CUSTOM         = 'custom';

    public static function types(): array
    {
        return [
            self::TYPE_PURCHASE       => 'Purchase / order placed',
            self::TYPE_PAYMENT        => 'Payment received',
            self::TYPE_FORM_SUBMITTED => 'Application / form submitted',
            self::TYPE_SIGNUP         => 'Sign-up / account created',
            self::TYPE_REGISTRATION   => 'Registration completed',
            self::TYPE_CUSTOM         => 'Custom event',
        ];
    }

    // Actions a config can carry out when its event fires.
    const ACTION_CONGRATULATE        = 'congratulate';
    const ACTION_STOP_SELLING        = 'stop_selling';
    const ACTION_HANDOFF_AFTER_SALE  = 'handoff_after_sale';
    const ACTION_NOTIFY_OWNER        = 'notify_owner';
    const ACTION_RECORD_REVENUE      = 'record_revenue';

    public static function availableActions(): array
    {
        return [
            self::ACTION_CONGRATULATE       => 'Send a thank-you / congratulations message',
            self::ACTION_STOP_SELLING       => 'Stop the AI from selling to this contact',
            self::ACTION_HANDOFF_AFTER_SALE => 'Hand off to the after-sale agent',
            self::ACTION_NOTIFY_OWNER       => 'Notify the owner / team',
            self::ACTION_RECORD_REVENUE     => 'Record the amount as revenue',
        ];
    }

    public function hasAction(string $action): bool
    {
        return in_array($action, $this->actions ?? [], true);
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function aiSalesAgent()
    {
        return $this->belongsTo(AiSalesAgent::class, 'ai_sales_agent_id');
    }

    public function events()
    {
        return $this->hasMany(ConversionEvent::class, 'event_config_id');
    }

    /**
     * Generate a URL-safe, unguessable token for a new webhook config.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function webhookUrl(): string
    {
        return url('/api/events/hook/' . $this->token);
    }
}
