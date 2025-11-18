<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get users of this type
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope for active user types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get AI agents targeting this user type
     */
    public function aiSalesAgents()
    {
        return $this->belongsToMany(AiSalesAgent::class, 'ai_agent_user_types');
    }
}
