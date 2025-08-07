<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $descriptions
 * @property string $created_at
 * @property string $updated_at
 * @property BusinessService[] $businessServices
 * @property Budget[] $budgets
 */
class Service extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'category_id',
        'name',
        'registration_fee',
        'descriptions',
        'created_at',
        'updated_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businessServices()
    {
        return $this->hasMany('App\Models\BusinessService');
    }

    /** 
        * @return \Illuminate\Database\Eloquent\Relations\HasMany
        * Method to get service categories related to the service
        */
 
        public function serviceCategory()
        {
            return $this->belongsTo('App\Models\ServiceCategory', 'category_id');
        }
}
