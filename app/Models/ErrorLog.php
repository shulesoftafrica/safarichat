<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $error_message
 * @property string $file
 * @property string $route
 * @property string $url
 * @property string $error_instance
 * @property string $request
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $deleted_by
 */
class ErrorLog extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['error_message', 'file', 'route', 'url', 'error_instance', 'request', 'created_by', 'created_at', 'updated_at', 'deleted_at', 'deleted_by'];

}
