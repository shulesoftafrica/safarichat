<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property Event $event
 */
class EventGuestCategory extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['event_id', 'name', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo('App\Models\Event');
    }
}
