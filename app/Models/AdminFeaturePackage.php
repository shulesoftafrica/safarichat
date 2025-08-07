<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $admin_package_id
 * @property int $admin_feature_id
 * @property string $value
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property AdminFeature $adminFeature
 * @property AdminPackage $adminPackage
 */
class AdminFeaturePackage extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['admin_package_id', 'admin_feature_id', 'value', 'description', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminFeature()
    {
        return $this->belongsTo('App\Models\AdminFeature');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminPackage()
    {
        return $this->belongsTo('App\Models\AdminPackage');
    }
}
