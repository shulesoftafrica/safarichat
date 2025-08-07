<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $file_album_id
 * @property string $mime
 * @property string $name
 * @property int $size
 * @property string $caption
 * @property string $url
 * @property string $created_at
 * @property string $updated_at
 * @property FileAlbum $fileAlbum
 */
class File extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['file_album_id', 'mime', 'name', 'size', 'caption', 'url', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fileAlbum()
    {
        return $this->belongsTo('App\Models\FileAlbum');
    }
}
