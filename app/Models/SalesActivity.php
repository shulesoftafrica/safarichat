<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesActivity extends Model
{
    protected $fillable = [
        'business_id',
        'business_contact_id',
        'lead_id',
        'user_id',
        'type',
        'notes',
        'activity_at',
        'follow_up_text',
        'appointment_id',
    ];

    protected $casts = [
        'activity_at' => 'datetime',
    ];

    // Activity types
    const TYPE_CALL    = 'call';
    const TYPE_VISIT   = 'visit';
    const TYPE_WHATSAPP= 'whatsapp';
    const TYPE_EMAIL   = 'email';
    const TYPE_MEETING = 'meeting';
    const TYPE_OTHER   = 'other';

    public static function types(): array
    {
        return [
            self::TYPE_CALL     => 'Phone call',
            self::TYPE_VISIT    => 'Physical visit',
            self::TYPE_WHATSAPP => 'WhatsApp',
            self::TYPE_EMAIL    => 'Email',
            self::TYPE_MEETING  => 'Meeting',
            self::TYPE_OTHER    => 'Other',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(BusinessContact::class, 'business_contact_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
