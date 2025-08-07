<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $promotion_id
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 * @property Promotion $promotion
 */
class PromotionReach extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'promotions_reaches';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'promotion_id', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function promotion()
    {
        return $this->belongsTo('App\Models\Promotion');
    }
}
