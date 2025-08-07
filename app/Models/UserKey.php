<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $api_key
 * @property string $api_secret
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 */
class UserKey extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'users_keys';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'type', 'api_key', 'api_secret', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
