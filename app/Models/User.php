<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFilamentName(): string
    {
        return "{$this->name} ({$this->email})";
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }

    /**
     * Organizations (phông) the user has access to.
     */
    public function organizations()
    {
        return $this->belongsToMany(\App\Models\Organization::class);
    }

    public function hasOrganization(int $orgId): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->organizations()->where('organization_id', $orgId)->exists();
    }
}
