<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AdminSession::class, 'admin_id');
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(AdminLoginHistory::class, 'admin_id');
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(AdminTrustedDevice::class, 'admin_id');
    }

    public function isTrustedDevice(string $fingerprint): bool
    {
        return $this->trustedDevices()
            ->where('device_fingerprint', $fingerprint)
            ->where(function ($query) {
                $query->whereNull('trusted_until')
                    ->orWhere('trusted_until', '>', now());
            })
            ->exists();
    }

    public function trustDevice(string $fingerprint, array $meta = [], int $days = 30): AdminTrustedDevice
    {
        return $this->trustedDevices()->updateOrCreate(
            ['device_fingerprint' => $fingerprint],
            [
                'device_name' => $meta['device_name'] ?? 'Admin Device',
                'browser' => $meta['browser'] ?? null,
                'os' => $meta['os'] ?? null,
                'ip_address' => $meta['ip_address'] ?? null,
                'trusted_until' => now()->addDays($days),
                'last_used_at' => now(),
            ]
        );
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'admin_permissions');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasRole(string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles, true);
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()->where('name', $permissionName)->exists();
    }

    public function givePermission(Permission|string $permission): void
    {
        if (is_string($permission)) {
            $perm = Permission::firstOrCreate(
                ['name' => $permission],
                ['label' => ucfirst(str_replace('.', ' ', $permission))]
            );
        } else {
            $perm = $permission;
        }

        $this->permissions()->syncWithoutDetaching([$perm->id]);
    }

    public function isIpAllowed(string $ip): bool
    {
        $allowedIps = config('auth.admin_allowed_ips', env('ADMIN_ALLOWED_IPS'));
        if (empty($allowedIps)) {
            return true;
        }

        $ips = array_map('trim', explode(',', $allowedIps));

        return in_array($ip, $ips, true) || in_array('*', $ips, true);
    }
}
