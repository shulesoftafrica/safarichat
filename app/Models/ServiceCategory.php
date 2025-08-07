<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    protected $table = 'service_categories';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
    ];

    protected $guarded = [
        'id',
    ];
}