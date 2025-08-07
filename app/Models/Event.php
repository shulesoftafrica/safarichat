<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_type_id
 * @property string $name
 * @property string $date
 * @property string $created_at
 * @property string $updated_at
 * @property EventsType $eventsType
 * @property EventsGuest[] $eventsGuests
 * @property UsersEvent[] $usersEvents
 */
class Event extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['event_type_id', 'name', 'date', 'created_at', 'updated_at','whatsapp_api_url','whatsapp_token','district_id','uid','url','location'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventsType()
    {
        return $this->belongsTo('App\Models\EventsType', 'event_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventsGuests()
    {
        return $this->hasMany('App\Models\EventsGuest');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usersEvents()
    {
        return $this->hasMany('App\Models\UsersEvent');
    }
       /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }
}
