<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $admin_payment_id
 * @property int $admin_package_id
 * @property string $start_date
 * @property string $end_date
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property AdminPackage $adminPackage
 * @property AdminPayment $adminPayment
 */
class AdminPackagePayment extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'admin_packages_payments';

    /**
     * @var array
     */
    protected $fillable = ['admin_payment_id', 'admin_package_id', 'start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminPackage()
    {
        return $this->belongsTo('App\AdminPackage');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminPayment()
    {
        return $this->belongsTo('App\AdminPayment');
    }
}
