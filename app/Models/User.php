<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'password',
        'first_name',
        'last_name',
        'phone',
        'gender',
        'birth_date',
        'district_id',
        'area_id',
        'is_active',
        'is_self_deleted',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'        => 'hashed',
            'birth_date'        => 'date',
            'is_active'       => 'boolean',
            'is_self_deleted' => 'boolean',
        ];
    }

    // ---- Relations ----
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // ---- Accessors ----
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
