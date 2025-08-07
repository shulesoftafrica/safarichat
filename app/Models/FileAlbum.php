<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 * @property File[] $files
 */
class FileAlbum extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'name', 'created_at', 'updated_at'];

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
    public function files()
    {
        return $this->hasMany('App\Models\File');
    }
}
