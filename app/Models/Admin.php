<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_super_admin',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'is_active'      => 'boolean',
            'is_super_admin' => 'boolean',
            'permissions'    => 'array',
        ];
    }

    /**
     * Check if admin has a specific permission.
     * Super admins always return true.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->is_super_admin) return true;
        return in_array($permission, $this->permissions ?? []);
    }
}

