<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $business_id
 * @property int $service_id
 * @property float $price
 * @property string $created_at
 * @property string $updated_at
 * @property Business $business
 * @property Service $service
 * @property Budget[] $budgets
 */
class BusinessService extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['business_id', 'service_id', 'price', 'created_at', 'updated_at','service_name','details','service_logo','phone','email','facebook','instagram','twitter','offer','images'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Models\Business');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo('App\Models\Service');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgets()
    {
        return $this->hasMany('App\Models\Budget');
    }
    
      /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function promotions()
    {
        return $this->hasMany('App\Models\Promotion');
    }
}
