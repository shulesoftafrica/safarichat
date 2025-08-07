<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $business_service_id
 * @property string $device
 * @property string $created_at
 * @property string $updated_at
 * @property BusinessService $businessService
 * @property User $user
 */
class PageViewer extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'business_service_id', 'device', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function businessService()
    {
        return $this->belongsTo('App\BusinessService');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
