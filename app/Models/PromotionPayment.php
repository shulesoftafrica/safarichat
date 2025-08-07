<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $payment_id
 * @property string $created_at
 * @property string $updated_at
 * @property Promotion $promotion
 * @property Promotion payment
 */
class PromotionPayment extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'promotions_payments';

    /**
     * @var array
     */
    protected $fillable = ['promotion_id', 'payment_id', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo('App\Models\Payment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function promotion()
    {
        return $this->belongsTo('App\Models\Promotion');
    }
}
