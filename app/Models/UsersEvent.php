<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $event_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property Event $event
 * @property User $user
 * @property Budget[] $budgets
 */
class UsersEvent extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'event_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo('App\Models\Event');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgets()
    {
        return $this->hasMany('App\Models\Budget');
    }
}
