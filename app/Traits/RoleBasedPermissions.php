<?php

namespace App\Traits;

trait RoleBasedPermissions
{
    protected static function hasAnyOrgRole($user, int $orgId, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($user->hasOrganization($orgId, $role)) {
                return true;
            }
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }

        $orgId = session('selected_archival_id');
        if ($orgId !== null && $user->hasOrganization((int) $orgId)) {
            return true;
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['super_admin', 'admin', 'teamlead', 'data_entry'], true)) {
            return true;
        }

        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'data_entry']);
        }

        return false;
    }

    public static function canImport(): bool
    {
        return static::canViewAny();
    }

    public static function canExport(): bool
    {
        return static::canViewAny();
    }

    public static function canManageMembers(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }

        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'admin']);
        }

        return false;
    }

    public static function canCreateStorage(): bool
    {
        return static::canViewAny();
    }
}
