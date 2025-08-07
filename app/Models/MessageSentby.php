<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $message_id
 * @property string $channel
 * @property string $created_at
 * @property string $updated_at
 * @property string $return_code
 * @property Message $message
 */
class MessageSentby extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'messages_sentby';

    /**
     * @var array
     */
    protected $fillable = ['message_id', 'channel', 'created_at', 'updated_at', 'return_code','status'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function message()
    {
        return $this->belongsTo('App\Models\Message');
    }
}
