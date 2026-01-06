<?php

namespace App\Models\AdminCrm;

use Illuminate\Database\Eloquent\Model;

class AdminTaskClient extends Model
{
    protected $connection = 'admin_crm';
    protected $table = 'admin.tasks_clients';
    
    protected $fillable = [
        'task_id', 'client_id', 'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the task this relationship belongs to
     */
    public function task()
    {
        return $this->belongsTo(AdminTask::class, 'task_id');
    }

    /**
     * Get the client this relationship belongs to
     */
    public function client()
    {
        return $this->belongsTo(AdminClient::class, 'client_id');
    }
}