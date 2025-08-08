<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * SmsPayments Model
 *
 * @property int $id
 * @property float $amount
 * @property string $method
 * @property string $message
 * @property string $token
 * @property string $phone_number
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $status
 * @property \Carbon\Carbon $payment_date
 * @property string|null $payer
 */
class SmsPayments extends Model
{
    protected $table = 'smspayments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'amount',
        'method',
        'message',
        'token',
        'phone_number',
        'created_at',
        'updated_at',
        'status',
        'payment_date',
        'payer',
    ];

    protected $casts = [
        'amount' => 'float',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'payment_date' => 'datetime',
    ];
}