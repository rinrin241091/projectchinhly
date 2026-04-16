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

    protected static function isDataEntryRole($user): bool
    {
        return $user && $user->role === 'data_entry';
    }

    protected static function dataEntryViewableResources(): array
    {
        return [
            \App\Filament\Resources\ArchiveRecordResource::class,
            \App\Filament\Resources\DocumentResource::class,
            \App\Filament\Resources\ArchiveRecordItemResource::class,
            \App\Filament\Resources\ProjectResource::class,
            \App\Filament\Resources\ArchivalResource::class,
            \App\Filament\Resources\OrganizationResource::class,
            \App\Filament\Resources\StorageResource::class,
            \App\Filament\Resources\ShelveResource::class,
            \App\Filament\Resources\BoxResource::class,
        ];
    }

    protected static function dataEntryWritableResources(): array
    {
        return [
            \App\Filament\Resources\DocumentResource::class,
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if (static::isDataEntryRole($user)) {
            return in_array(static::class, static::dataEntryViewableResources(), true);
        }

        // global admin bypasses everything
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }

        // if an archival/organization is selected, allow if user has at least viewer role
        $orgId = session('selected_archival_id');
        if ($orgId !== null && $user->hasOrganization($orgId)) {
            return true;
        }

        return false;
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (static::isDataEntryRole($user)) {
            return in_array(static::class, static::dataEntryWritableResources(), true);
        }
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead']);
        }
        return false;
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (static::isDataEntryRole($user)) {
            return in_array(static::class, static::dataEntryWritableResources(), true);
        }
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead']);
        }
        return false;
    }
    
    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (static::isDataEntryRole($user)) {
            return in_array(static::class, static::dataEntryWritableResources(), true);
        }
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead']);
        }
        return false;
    }

    /**
     * Check if user can perform import action
    * Only admins and teamleads can import
     */
    public static function canImport(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (static::isDataEntryRole($user)) {
            return in_array(static::class, static::dataEntryWritableResources(), true);
        }
        
        // Global admin can always import
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead']);
        }
        
        return false;
    }

    /**
     * Check if user can perform export action
    * Only admins and teamleads can export
     */
    public static function canExport(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (static::isDataEntryRole($user)) {
            return in_array(static::class, static::dataEntryWritableResources(), true);
        }
        
        // Global admin can always export
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead']);
        }
        
        return false;
    }

    /**
     * Check if user can manage members (add/edit/delete users in organization)
     * Only organization admins can manage members
     */
    public static function canManageMembers(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (static::isDataEntryRole($user)) {
            return false;
        }
        
        // Global admin can always manage members
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only org teamleads can manage members (not data-entry staff or viewers)
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'admin']);
        }
        
        return false;
    }

    /**
     * Check if user can create storage units
     * Only global admins and organization admins can create storage
     */
    public static function canCreateStorage(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (static::isDataEntryRole($user)) {
            return false;
        }
        
        // Global admin can always create storage units
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead']);
        }
        
        return false;
    }
}
