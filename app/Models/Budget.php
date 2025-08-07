<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_id
 * @property int $business_service_id
 * @property float $initial_price
 * @property float $actual_price
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property float $paid_amount
 * @property UsersEvent $usersEvent
 * @property BusinessService $businessService
 * @property BudgetPayment[] $budgetPayments
 */
class Budget extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['event_id', 'business_service_id', 'initial_price', 'actual_price', 'created_at', 'updated_at', 'deleted_at', 'paid_amount','approved','quantity', 'service_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo('App\Models\Event');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function businessService()
    {
        return $this->belongsTo('App\Models\BusinessService');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetPayments()
    {
        return $this->hasMany('App\Models\BudgetPayment');
    }

    /** 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo('App\Models\Service');
    }
}
