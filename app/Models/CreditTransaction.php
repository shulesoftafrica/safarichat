<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_payment_id',
        'conversation_id',
        'transaction_type',
        'credits_amount',
        'tokens_consumed',
        'sms_count',
        'balance_before',
        'balance_after',
        'description',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    public $timestamps = false;
    protected $dates = ['created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // AdminPayment relationship removed - using new billing system

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isDebit(): bool
    {
        return $this->credits_amount < 0;
    }

    public function isCredit(): bool
    {
        return $this->credits_amount > 0;
    }
}
