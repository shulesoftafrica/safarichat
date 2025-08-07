<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $events_guests_id
 * @property string $body
 * @property string $subject
 * @property int $status
 * @property string $return_code
 * @property int $type
 * @property int $message_type
 * @property int $sms_count
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property EventsGuest $eventsGuest
 * @property User $user
 */
class Message extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'phone', 'body', 'subject', 'email', 'type', 'sms_count', 'created_at', 'updated_at', 'deleted_at'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
