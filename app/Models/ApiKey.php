<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'permissions',
        'metadata',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'metadata' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'key_hash',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Static methods for key generation
    public static function generateKey(): string
    {
        return 'sk-' . Str::random(40);
    }

    public static function createForUser(User $user, string $name, array $permissions = [], ?Carbon $expiresAt = null): array
    {
        $key = self::generateKey();
        $keyPrefix = substr($key, 0, 8); // sk-12345678

        $apiKey = self::create([
            'user_id' => $user->id,
            'name' => $name,
            'key_hash' => Hash::make($key),
            'key_prefix' => $keyPrefix,
            'permissions' => $permissions,
            'expires_at' => $expiresAt,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $key, // Only returned once during creation
        ];
    }

    // Instance methods
    public function hasPermission(string $permission): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return !empty(array_intersect($permissions, $this->permissions ?? []));
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeValid($query)
    {
        return $query->active()->notExpired();
    }

    // Verify a plain key against this hashed key
    public function verifyKey(string $plainKey): bool
    {
        return Hash::check($plainKey, $this->key_hash);
    }

    // Find API key by plain key
    public static function findByKey(string $plainKey): ?self
    {
        $prefix = substr($plainKey, 0, 8);
        
        $keys = self::where('key_prefix', $prefix)->get();
        
        foreach ($keys as $key) {
            if ($key->verifyKey($plainKey)) {
                return $key;
            }
        }
        
        return null;
    }
}
