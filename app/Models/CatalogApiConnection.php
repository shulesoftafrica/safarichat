<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogApiConnection extends Model
{
    protected $table = 'catalog_api_connections';

    protected $fillable = [
        'business_id', 'name', 'base_url', 'catalog_path',
        'auth_type', 'auth_header', 'auth_value', 'page_param',
        'status', 'last_synced_at', 'product_count', 'last_error',
    ];

    protected $casts = [
        'product_count'  => 'integer',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = ['auth_value'];

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
