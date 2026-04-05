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
        ];
    }

    protected static function dataEntryWritableResources(): array
    {
        return [
            \App\Filament\Resources\ArchiveRecordResource::class,
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
            // teamleads and data-entry staff within the org can create
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'data_entry']);
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
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'data_entry']);
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
            return false;
        }
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'data_entry']);
        }
        return false;
    }

    /**
     * Check if user can perform import action
    * Only admins and data-entry staff can import (not viewers)
     */
    public static function canImport(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (static::isDataEntryRole($user)) {
            return false;
        }
        
        // Global admin can always import
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only teamleads and data-entry staff within the org can import (not viewers)
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'data_entry']);
        }
        
        return false;
    }

    /**
     * Check if user can perform export action
    * Only admins and data-entry staff can export (not viewers)
     */
    public static function canExport(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (static::isDataEntryRole($user)) {
            return false;
        }
        
        // Global admin can always export
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only teamleads and data-entry staff within the org can export (not viewers)
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'data_entry']);
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
            // Only org teamleads and data-entry staff can create storage (not viewers)
            return static::hasAnyOrgRole($user, (int) $orgId, ['teamlead', 'data_entry']);
        }
        
        return false;
    }
}
