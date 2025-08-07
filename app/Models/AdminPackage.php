<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property integer $is_addon
 * @property AdminPackagesPayment[] $adminPackagesPayments
 */
class AdminPackage extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['name', 'created_at', 'updated_at', 'deleted_at', 'is_addon','price'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adminPackagesPayments()
    {
        return $this->hasMany('App\Models\AdminPackagePayment');
    }
    
    /**
     * Get the list of bookings under this package.
     *
     * @return float
     */

     public function bookings()
     {
         return $this->hasMany('App\Models\AdminBooking', 'admin_package_id');
     }
}
