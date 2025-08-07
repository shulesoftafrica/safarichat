<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $budget_id
 * @property int $created_by
 * @property float $amount
 * @property string $date
 * @property string $method
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 * @property Budget $budget
 */
class BudgetPayment extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['budget_id', 'created_by', 'amount', 'date', 'method', 'created_at', 'updated_at','note'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('App\Models\Budget');
    }
}
