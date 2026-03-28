<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

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

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if (! in_array($user->role, ['super_admin', 'admin'], true)) {
                return;
            }

            $hasOtherSameRole = static::query()
                ->where('role', $user->role)
                ->when($user->exists, fn ($query) => $query->whereKeyNot($user->getKey()))
                ->exists();

            if (! $hasOtherSameRole) {
                return;
            }

            $message = $user->role === 'super_admin'
                ? 'Hệ thống chỉ cho phép một tài khoản Super Admin.'
                : 'Hệ thống chỉ cho phép một tài khoản Admin.';

            throw ValidationException::withMessages([
                'role' => $message,
            ]);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Người dùng')
            ->setDescriptionForEvent(function (string $eventName): string {
                $name = trim((string) ($this->name ?? $this->getOriginal('name') ?? ''));
                $email = trim((string) ($this->email ?? $this->getOriginal('email') ?? ''));

                if ($name !== '' && $email !== '') {
                    $userLabel = "{$name} ({$email})";
                } elseif ($name !== '') {
                    $userLabel = $name;
                } elseif ($email !== '') {
                    $userLabel = $email;
                } else {
                    $userLabel = '#' . $this->id;
                }

                return match ($eventName) {
                    'created' => "Admin đã tạo người dùng {$userLabel}",
                    'updated' => "Admin đã cập nhật người dùng {$userLabel}",
                    'deleted' => "Admin đã xóa người dùng {$userLabel}",
                    default => "Admin đã thao tác {$eventName} trên người dùng {$userLabel}",
                };
            });
    }

    public function getFilamentName(): string
    {
        return "{$this->name} ({$this->email})";
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }

    /**
     * Organizations (phông) the user has access to.  We include pivot data
     * so that the user can have a distinct "role" in each organization.
     */
    public function organizations()
    {
        return $this->belongsToMany(\App\Models\Organization::class)
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function managedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Cache of organizations for this request (memoization).
     * Prevents querying the DB multiple times per request.
     */
    private ?array $organizationIds = null;
    private ?array $organizationRoles = null;

    /**
     * Determine whether the user is associated with the given organization.
     * Uses memoization to avoid repeated DB queries in the same request.
     *
     * @param  int  $orgId
     * @param  string|null  $role   optional pivot role to check for
     */
    public function hasOrganization(int $orgId, ?string $role = null): bool
    {
        if (in_array($this->role, ['super_admin', 'admin'], true)) {
            return true;
        }

        // Load organizations once and cache in memory for this request
        if ($this->organizationIds === null) {
            $this->organizationIds = [];
            $this->organizationRoles = [];

            foreach ($this->organizations as $org) {
                $this->organizationIds[] = (int) $org->id;
                $this->organizationRoles[(int) $org->id] = (array) ($org->pivot?->role ? [$org->pivot->role] : []);
            }
        }

        // Check if organization exists in memoized list
        if (!in_array($orgId, $this->organizationIds, true)) {
            return false;
        }

        // If specific role required, verify it
        if ($role !== null) {
            $userRole = $this->organizationRoles[$orgId] ?? [];
            if (in_array($role, ['editor', 'input_data'], true)) {
                return count(array_intersect($userRole, ['editor', 'input_data'])) > 0;
            }
            return in_array($role, $userRole, true);
        }

        return true;
    }
}
