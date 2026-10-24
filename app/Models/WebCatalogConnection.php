<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebCatalogConnection extends Model
{
    protected $table = 'web_catalog_connections';

    protected $fillable = [
        'business_id', 'url', 'status', 'last_synced_at', 'product_count', 'last_error',
    ];

    protected $casts = [
        'product_count'  => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
