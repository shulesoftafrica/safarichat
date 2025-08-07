<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $district_id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property District $district
 * @property Business[] $businesses
 */
class Ward extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['district_id', 'name', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businesses()
    {
        return $this->hasMany('App\Models\Business');
    }
}
