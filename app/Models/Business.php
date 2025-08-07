<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $ward_id
 * @property string $address
 * @property string $descriptions
 * @property string $created_at
 * @property string $updated_at
 * @property Ward $ward
 * @property User $user
 * @property BusinessService[] $businessServices
 */
class Business extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'ward_id', 'address', 'descriptions', 'created_at', 'updated_at','name','email','phone','website','instagram','facebook','linkedin','cover_page','twitter','legal_document','business_type_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ward()
    {
        return $this->belongsTo('App\Models\Ward');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businessServices()
    {
        // This assumes there is a 'business_services' table with a 'business_id' foreign key.
        return $this->hasMany('App\Models\BusinessService', 'business_id')->dd();
    }
  
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * Method to get service
     */
    public function service(){
        return $this->belongsTo('App\Models\Service');
    }
}
