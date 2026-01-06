<?php

namespace App\Models\AdminCrm;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdminTask extends Model
{
    protected $connection = 'admin_crm';
    protected $table = 'admin.tasks';
    
    protected $fillable = [
        'client_id', 'activity', 'date', 'time', 'user_id', 'priority',
        'action', 'to_user_id', 'next_action', 'status', 'budget'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'date' => 'date',
        'budget' => 'decimal:2',
        'priority' => 'integer',
        'remainder' => 'boolean'
    ];

    /**
     * Get the client this task belongs to
     */
    public function client()
    {
        return $this->belongsTo(AdminClient::class, 'client_id');
    }

    /**
     * Get the user who created this task
     */
    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }

    /**
     * Get the user this task is assigned to
     */
    public function assignedUser()
    {
        return $this->belongsTo(AdminUser::class, 'to_user_id');
    }

    /**
     * Get full timestamp by combining date and time
     */
    public function getFullTimestampAttribute()
    {
        if ($this->date && $this->time) {
            try {
                return Carbon::createFromFormat('Y-m-d H:i:s', $this->date->format('Y-m-d') . ' ' . $this->time);
            } catch (\Exception $e) {
                return $this->date;
            }
        }
        return $this->created_at;
    }

    /**
     * Get priority text
     */
    public function getPriorityTextAttribute()
    {
        $priorities = [
            1 => 'Low',
            2 => 'Medium', 
            3 => 'High',
            4 => 'Urgent'
        ];

        return $priorities[$this->priority] ?? 'Medium';
    }

    /**
     * Get mapped priority for SafariChat
     */
    public function getMappedPriorityAttribute()
    {
        $priorityMap = config('admin_crm.status_mappings.priorities');
        return $priorityMap[$this->priority] ?? 'medium';
    }

    /**
     * Get mapped task status for SafariChat
     */
    public function getMappedStatusAttribute()
    {
        $statusMap = config('admin_crm.status_mappings.task_status');
        return $statusMap[$this->status] ?? 'pending';
    }

    /**
     * Determine interaction type from action field
     */
    public function getInteractionTypeAttribute()
    {
        $action = strtolower($this->action ?: '');
        
        // Map common actions to interaction types
        if (str_contains($action, 'call') || str_contains($action, 'phone')) {
            return 'phone_call';
        }
        if (str_contains($action, 'email') || str_contains($action, 'mail')) {
            return 'email';
        }
        if (str_contains($action, 'meeting') || str_contains($action, 'visit')) {
            return 'meeting';
        }
        if (str_contains($action, 'demo') || str_contains($action, 'presentation')) {
            return 'demo';
        }
        if (str_contains($action, 'follow') || str_contains($action, 'reminder')) {
            return 'follow_up';
        }
        
        return 'general';
    }

    /**
     * Check if task has follow-up action
     */
    public function getHasFollowUpAttribute()
    {
        return !empty($this->next_action);
    }

    /**
     * Get task clients (many-to-many relationship)
     */
    public function taskClients()
    {
        return $this->hasMany(AdminTaskClient::class, 'task_id');
    }

    /**
     * Scope for tasks with specific status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for tasks assigned to specific user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for tasks within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for tasks with high priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [3, 4]);
    }

    /**
     * Format activity for import
     */
    public function getFormattedActivityAttribute()
    {
        $activity = $this->activity ?: 'No activity description';
        
        // Add context information
        $context = [];
        
        if ($this->action) {
            $context[] = "Action: " . $this->action;
        }
        
        if ($this->budget > 0) {
            $context[] = "Budget: $" . number_format($this->budget, 2);
        }
        
        if ($this->next_action) {
            $context[] = "Follow-up: " . $this->next_action;
        }
        
        if (!empty($context)) {
            $activity .= "\n\n" . implode("\n", $context);
        }
        
        return $activity;
    }
}