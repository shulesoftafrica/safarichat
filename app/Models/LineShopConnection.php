<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineShopConnection extends Model
{
    protected $fillable = [
        'business_id',
        'base_url',
        'client_id',
        'api_key',
        'shop_name',
        'status',
        'webhook_token',
        'last_synced_at',
        'product_count',
        'last_error',
    ];

    protected $casts = [
        'client_id'      => 'integer',
        'product_count'  => 'integer',
        'last_synced_at' => 'datetime',
    ];

    // Hide the secret from any accidental array/JSON serialization.
    protected $hidden = ['api_key', 'webhook_token'];

    const STATUS_PENDING   = 'pending';
    const STATUS_CONNECTED = 'connected';
    const STATUS_ERROR     = 'error';

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
    }
}
