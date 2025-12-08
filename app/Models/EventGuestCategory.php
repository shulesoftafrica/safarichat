<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property Business $business
 */
class EventGuestCategory extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['business_id', 'name', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Models\Business');
    }
}
