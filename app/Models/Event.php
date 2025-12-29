<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated This model is deprecated as of Phase 4 database modernization
 * Event functionality has been consolidated into the Business model
 * Use App\Models\Business for campaign/event management instead
 * 
 * @property int $id
 * @property int $event_type_id
 * @property string $name
 * @property string $date
 * @property string $created_at
 * @property string $updated_at
 * @property EventsType $eventsType
 * @property BusinessContact[] $businessContacts (formerly EventsGuest)
 * @property UsersEvent[] $usersEvents
 */
class Event extends Model
{
    /**
     * @deprecated Use Business model instead
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
     * @deprecated Use Business model's businessContacts() relationship instead
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventsGuests()
    {
        return $this->hasMany('App\Models\EventsGuest');
    }

    /**
     * New relationship to business contacts (replaces eventsGuests)
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function businessContacts()
    {
        return $this->hasManyThrough(
            'App\Models\BusinessContact',
            'App\Models\EventBusinessMapping',
            'event_id', // Foreign key on event_business_mapping table
            'business_id', // Foreign key on business_contacts table
            'id', // Local key on events table
            'business_id' // Local key on event_business_mapping table
        );
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
