<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $business_id
 * @property string $uid
 * @property string $promotion_type
 * @property int $total_users
 * @property string $created_at
 * @property string $updated_at
 * @property Business $business
 * @property AdminBooking $adminBooking
 * @property PromotionsPayment[] $promotionsPayments
 * @property PromotionsReach[] $promotionsReaches
 */
class Promotion extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['booking_id', 'business_service_id', 'uid', 'promotion_type', 'total_users', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function businessService()
    {
        return $this->belongsTo('App\Models\BusinessService');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminBooking()
    {
        return $this->belongsTo('App\Models\AdminBooking', 'booking_id');
    }



    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function promotionsPayments()
    {
        return $this->hasMany('App\Models\PromotionsPayment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function promotionsReaches()
    {
        return $this->hasMany('App\Models\PromotionsReach');
    }
}
