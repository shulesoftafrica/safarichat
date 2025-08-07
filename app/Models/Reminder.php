<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $date
 * @property string $time
 * @property string $title
 * @property integer $is_repeated
 * @property string $days
 * @property string $message
 * @property integer $type
 * @property int $event_guest_category_id
 * @property string $inputs
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 */
class Reminder extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'date', 'time', 'title', 'is_repeated', 'days', 'message', 'type', 'event_guest_category_id', 'users', 'created_at', 'updated_at','last_date','channels'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    
    public function guests() {
        return  \App\Models\EventsGuest::whereIn('id',explode(',', $this->attributes['users']))->get(['guest_name']);
    }
}
