<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $topic
 * @property string $details
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 */
class AdminSupport extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'topic', 'details', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
