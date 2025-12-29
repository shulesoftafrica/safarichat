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
     * @deprecated Event model is completely deprecated - use Business model instead
     * This relationship is no longer functional as both events and event_business_mapping tables are gone
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function businessContacts()
    {
        // This relationship no longer works as events and mapping tables are gone
        // Use Business model and its businessContacts() relationship instead
        throw new \Exception('Event model is deprecated. Use Business model with businessContacts() relationship instead.');
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
