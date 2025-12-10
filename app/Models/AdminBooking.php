<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $order_id
 * @property float $amount
 * @property string $token
 * @property string $methods
 * @property string $reference
 * @property integer $status
 * @property string $gateway_buyer_uuid
 * @property string $qr
 * @property string $payment_gateway_url
 * @property string $created_at
 * @property string $updated_at
 * @property Admin.user $admin.user
 */
class AdminBooking extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'user_id', 
        'order_id', 
        'amount', 
        'token', 
        'methods', 
        'reference', 
        'status', 
        'gateway_buyer_uuid', 
        'qr', 
        'payment_gateway_url', 
        'admin_package_id',
        // Currency and payment fields
        'base_currency',
        'base_amount',
        'display_currency',
        'display_amount',
        'fx_rate',
        'fx_markup',
        'locked_at',
        'expires_at',
        'payment_status'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'base_amount' => 'integer',
        'display_amount' => 'decimal:2',
        'fx_rate' => 'decimal:8',
        'fx_markup' => 'decimal:4',
        'locked_at' => 'timestamp',
        'expires_at' => 'timestamp'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function promotions()
    {
        return $this->hasMany('App\Models\Promotions');
    }

    public function adminPackage()
    {
        return $this->belongsTo('App\Models\AdminPackage', 'admin_package_id');
    }

    public function adminPackagePayment()
    {
        return $this->hasOne('App\Models\AdminPackagePayment', 'admin_package_id');
    }
}
