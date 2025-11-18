<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class OfficialWhatsappCredential extends Model
{
    use HasFactory;

    protected $table = 'official_whatsapp_credentials';

    protected $fillable = [
        'user_id',
        'waba_id',
        'phone_number_id',
        'access_token',
        'token_expiration',
        'api_provider',
        'status',
        'phone_number',
        'display_phone_number',
        'verified_name',
        'quality_rating',
        'webhook_verification_token',
        'meta_app_config',
        'temporary_code',
        'onboarding_started_at',
        'onboarding_completed_at',
        'error_logs'
    ];

    protected $casts = [
        'token_expiration' => 'datetime',
        'webhook_verification_token' => 'array',
        'meta_app_config' => 'array',
        'error_logs' => 'array',
        'onboarding_started_at' => 'datetime',
        'onboarding_completed_at' => 'datetime'
    ];

    protected $hidden = [
        'access_token',
        'webhook_verification_token',
        'temporary_code'
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Encrypt and store access token
     */
    public function setAccessTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['access_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt and retrieve access token
     */
    public function getAccessTokenAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                \Log::error('Failed to decrypt access token for credential ID: ' . $this->id);
                return null;
            }
        }
        return null;
    }

    /**
     * Encrypt and store temporary code
     */
    public function setTemporaryCodeAttribute($value)
    {
        if ($value) {
            $this->attributes['temporary_code'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt and retrieve temporary code
     */
    public function getTemporaryCodeAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                \Log::error('Failed to decrypt temporary code for credential ID: ' . $this->id);
                return null;
            }
        }
        return null;
    }

    /**
     * Check if token is expired or about to expire
     */
    public function isTokenExpired($bufferMinutes = 60)
    {
        if (!$this->token_expiration) {
            return true;
        }

        return Carbon::now()->addMinutes($bufferMinutes)->isAfter($this->token_expiration);
    }

    /**
     * Check if credential is active and usable
     */
    public function isActive()
    {
        return $this->status === 'connected' && 
               !$this->isTokenExpired() && 
               $this->access_token && 
               $this->phone_number_id;
    }

    /**
     * Get formatted status for display
     */
    public function getStatusLabelAttribute()
    {
        $statusLabels = [
            'pending' => 'Setup Pending',
            'connected' => 'Connected',
            'disconnected' => 'Disconnected',
            'suspended' => 'Suspended',
            'verification_pending' => 'Verification Pending'
        ];

        return $statusLabels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get quality rating color class
     */
    public function getQualityRatingColorAttribute()
    {
        switch (strtolower($this->quality_rating)) {
            case 'green':
                return 'success';
            case 'yellow':
                return 'warning';
            case 'red':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * Log error information
     */
    public function logError($error, $context = [])
    {
        $errorLogs = $this->error_logs ?? [];
        $errorLogs[] = [
            'timestamp' => now()->toISOString(),
            'error' => $error,
            'context' => $context
        ];

        // Keep only last 10 errors
        if (count($errorLogs) > 10) {
            $errorLogs = array_slice($errorLogs, -10);
        }

        $this->update(['error_logs' => $errorLogs]);
    }

    /**
     * Update onboarding progress
     */
    public function markOnboardingStarted()
    {
        $this->update([
            'onboarding_started_at' => now(),
            'status' => 'verification_pending'
        ]);
    }

    /**
     * Complete onboarding process
     */
    public function markOnboardingCompleted()
    {
        $this->update([
            'onboarding_completed_at' => now(),
            'status' => 'connected'
        ]);
    }

    /**
     * Get onboarding duration in minutes
     */
    public function getOnboardingDurationAttribute()
    {
        if ($this->onboarding_started_at && $this->onboarding_completed_at) {
            return $this->onboarding_started_at->diffInMinutes($this->onboarding_completed_at);
        }
        return null;
    }

    /**
     * Scope for active credentials
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'connected')
                    ->whereNotNull('access_token')
                    ->whereNotNull('phone_number_id')
                    ->where(function($q) {
                        $q->whereNull('token_expiration')
                          ->orWhere('token_expiration', '>', now()->addHour());
                    });
    }

    /**
     * Scope for user credentials
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get API provider configuration
     */
    public function getProviderConfig()
    {
        $providers = config('whatsapp.official.providers');
        return $providers[$this->api_provider] ?? null;
    }
}
