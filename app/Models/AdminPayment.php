<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property float $amount
 * @property string $transaction_id
 * @property string $method
 * @property string $date
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property User $user
 * @property AdminPackagesPayment[] $adminPackagesPayments
 */
class AdminPayment extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'amount', 'transaction_id', 'method', 'date', 'created_at', 'updated_at', 'deleted_at','admin_booking_id'];

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
    public function adminPackagesPayments()
    {
        return $this->hasMany('App\Models\AdminPackagesPayment');
    }
}
