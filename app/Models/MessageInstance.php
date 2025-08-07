<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageInstance extends Model
{
    protected $table = 'message_instances';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'instance_id',
        'type',
        'name',
        'owner',
        'user_id',
        'connect_status',
        'phone_number',
        'pairing_code',
        'webhook_url',
        'webhook_events',
        'status',
        'is_paid',
        'created_at',
        'updated_at',
        'file_path',
        'nida'
    ];

    protected $casts = [
        'id' => 'integer',
        'instance_id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
        'is_paid' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}