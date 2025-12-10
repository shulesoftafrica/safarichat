<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway_type',
        'merchant_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'is_default',
        'is_active',
        'metadata',
        'last_used_at'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_used_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLipaNamba(): bool
    {
        return $this->gateway_type === 'lipa_number';
    }

    public function isStripe(): bool
    {
        return $this->gateway_type === 'stripe';
    }
}
