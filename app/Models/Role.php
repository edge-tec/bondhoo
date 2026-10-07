<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    public function givePermissionTo(Permission|string $permission): void
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
}
