<?php

namespace App\Models\AdminCrm;

use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    protected $connection = 'admin_crm';
    protected $table = 'admin.users';
    
    protected $fillable = [
        'firstname', 'middlename', 'lastname', 'email', 'phone', 
        'role_id', 'name', 'status', 'department'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'contract_end_date' => 'date',
        'contract_start_date' => 'date',
        'salary' => 'decimal:2',
        'status' => 'integer',
        'experience' => 'integer'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * Get full name of the user
     */
    public function getFullNameAttribute()
    {
        $parts = array_filter([
            $this->firstname,
            $this->middlename,
            $this->lastname
        ]);
        
        return implode(' ', $parts) ?: $this->name;
    }

    /**
     * Get display name for SafariChat
     */
    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: $this->email;
    }

    /**
     * Check if user is active
     */
    public function getIsActiveAttribute()
    {
        return $this->status == 1 && is_null($this->deleted_at);
    }

    /**
     * Get role name based on role_id
     * You may need to adjust this based on your role mapping
     */
    public function getRoleNameAttribute()
    {
        $roles = [
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Sales Representative',
            4 => 'Support',
            5 => 'Consultant'
        ];

        return $roles[$this->role_id] ?? 'User';
    }

    /**
     * Map admin role to SafariChat role
     */
    public function getMappedRoleAttribute()
    {
        // Map admin roles to SafariChat permissions
        $roleMapping = [
            1 => 'admin',           // Admin
            2 => 'manager',         // Manager  
            3 => 'sales',          // Sales Representative
            4 => 'support',        // Support
            5 => 'consultant'      // Consultant
        ];

        return $roleMapping[$this->role_id] ?? 'user';
    }

    /**
     * Get clients created by this user
     */
    public function createdClients()
    {
        return $this->hasMany(AdminClient::class, 'created_by');
    }

    /**
     * Get clients assigned to this user
     */
    public function assignedClients()
    {
        return $this->hasMany(AdminClient::class, 'user_id');
    }

    /**
     * Get tasks created by this user
     */
    public function createdTasks()
    {
        return $this->hasMany(AdminTask::class, 'user_id');
    }

    /**
     * Get tasks assigned to this user
     */
    public function assignedTasks()
    {
        return $this->hasMany(AdminTask::class, 'to_user_id');
    }

    /**
     * Scope for active users only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1)->whereNull('deleted_at');
    }

    /**
     * Scope for users with specific role
     */
    public function scopeWithRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Check if user has specific permission/role
     */
    public function hasRole($role)
    {
        $roleIds = [
            'admin' => [1],
            'manager' => [1, 2],
            'sales' => [1, 2, 3],
            'support' => [1, 2, 4],
            'consultant' => [1, 2, 5]
        ];

        return in_array($this->role_id, $roleIds[$role] ?? []);
    }

    /**
     * Get user statistics
     */
    public function getStatsAttribute()
    {
        return [
            'clients_created' => $this->createdClients()->count(),
            'clients_assigned' => $this->assignedClients()->count(),
            'tasks_created' => $this->createdTasks()->count(),
            'tasks_assigned' => $this->assignedTasks()->count(),
            'active_clients' => $this->assignedClients()->active()->count()
        ];
    }

    /**
     * Format user data for SafariChat import
     */
    public function getImportDataAttribute()
    {
        return [
            'name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->mapped_role,
            'department' => $this->department,
            'is_active' => $this->is_active,
            'original_data' => [
                'admin_crm_id' => $this->id,  // Store for reference but don't use as field
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'role_id' => $this->role_id,
                'joining_date' => $this->joining_date,
                'salary' => $this->salary
            ]
        ];
    }
}