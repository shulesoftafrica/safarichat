<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $events_guests_id
 * @property float $amount
 * @property string $transaction_id
 * @property string $method
 * @property string $date
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property EventsGuest $eventsGuest
 */
class Payment extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['events_guests_id', 'amount', 'transaction_id', 'method', 'date', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventsGuest()
    {
        return $this->belongsTo('App\Models\EventsGuest', 'events_guests_id');
    }
}
