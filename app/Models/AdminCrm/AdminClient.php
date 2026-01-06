<?php

namespace App\Models\AdminCrm;

use Illuminate\Database\Eloquent\Model;

class AdminClient extends Model
{
    protected $connection = 'admin_crm';
    protected $table = 'admin.clients';
    
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'status', 'created_by',
        'estimated_students', 'registration_number', 'note', 'owner_phone',
        'owner_email', 'director_name', 'director_phone', 'director_email'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'invoice_start_date' => 'date',
        'invoice_end_date' => 'date',
        'renewal_date' => 'date',
        'start_usage_date' => 'date',
        'estimated_students' => 'integer',
        'price_per_student' => 'decimal:2',
        'trial' => 'boolean',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean'
    ];

    /**
     * Get status text for display
     */
    public function getStatusTextAttribute()
    {
        $statusMap = [
            0 => 'Lead',
            1 => 'Prospect', 
            2 => 'Customer',
            4 => 'Churned',
            5 => 'Qualified Lead',
            6 => 'Low Usage Client'
        ];

        return $statusMap[$this->status] ?? 'Unknown';
    }

    /**
     * Get mapped status for SafariChat
     */
    public function getMappedStatusAttribute()
    {
        $statusMap = config('admin_crm.status_mappings.clients');
        return $statusMap[$this->status] ?? 'lead';
    }

    /**
     * Get full name (director or main contact)
     */
    public function getFullContactNameAttribute()
    {
        return $this->director_name ?: $this->name;
    }

    /**
     * Get primary contact phone
     */
    public function getPrimaryPhoneAttribute() 
    {
        return $this->director_phone ?: $this->owner_phone ?: $this->phone;
    }

    /**
     * Get primary contact email
     */
    public function getPrimaryEmailAttribute()
    {
        return $this->director_email ?: $this->owner_email ?: $this->email;
    }

    /**
     * Get tasks for this client
     */
    public function tasks()
    {
        return $this->hasMany(AdminTask::class, 'client_id');
    }

    /**
     * Get the user who created this client
     */
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    /**
     * Get the assigned user/relationship manager
     */
    public function assignedUser()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }

    /**
     * Scope for specific status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for active clients (not churned)
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 4);
    }

    /**
     * Get custom data for SafariChat import
     */
    public function getCustomDataAttribute()
    {
        return [
            'estimated_students' => $this->estimated_students,
            'registration_number' => $this->registration_number,
            'region_id' => $this->region_id,
            'ward_id' => $this->ward_id,
            'price_per_student' => $this->price_per_student,
            'payment_option' => $this->payment_option,
            'ownership' => $this->ownership,
            'project_id' => $this->project_id,
            'invoice_period' => [
                'start' => $this->invoice_start_date,
                'end' => $this->invoice_end_date
            ],
            'director_info' => [
                'name' => $this->director_name,
                'phone' => $this->director_phone,
                'email' => $this->director_email
            ],
            'trial_info' => [
                'is_trial' => $this->trial,
                'special_code' => $this->special_trial_code
            ]
        ];
    }
}