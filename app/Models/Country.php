<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $country_code
 * @property string $dialling_code
 * @property Region[] $regions
 */
class Country extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['name', 'country_code', 'dialling_code'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function regions()
    {
        return $this->hasMany('App\Models\Region');
    }
}
