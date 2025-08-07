<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_id
 * @property string $guest_name
 * @property string $guest_email
 * @property string $guest_email_verified_at
 * @property string $guest_phone
 * @property string $guest_category
 * @property float $guest_pledge
 * @property string $created_at
 * @property string $updated_at
 * @property Event $event
 * @property Message[] $messages
 * @property Payment[] $payments
 */
class EventsGuest extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['event_id', 'user_id', 'guest_name', 'guest_email', 'guest_email_verified_at', 'guest_phone', 'event_guest_category_id', 'guest_pledge', 'created_at', 'updated_at','code'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo('App\Models\Event');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany('App\Models\Message', 'events_guests_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany('App\Models\Payment', 'events_guests_id');
    }
     /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventGuestCategory()
    {
        return $this->belongsTo('App\Models\EventGuestCategory');
    }
}
