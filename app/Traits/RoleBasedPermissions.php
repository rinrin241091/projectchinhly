<?php

namespace App\Traits;

trait RoleBasedPermissions
{
    protected static function isDataEntryRole($user): bool
    {
        return $user && in_array($user->role, ['data_entry', 'input_data'], true);
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
        if ($user->role === 'admin') {
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
        if ($user->role === 'admin') {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // editors and admins within the org can create
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
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
        if ($user->role === 'admin') {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
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
        if ($user->role === 'admin') {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        return false;
    }

    /**
     * Check if user can perform import action
     * Only admins and editors can import (not viewers)
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
        if ($user->role === 'admin') {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only admins and editors within the org can import (not viewers)
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        
        return false;
    }

    /**
     * Check if user can perform export action
     * Only admins and editors can export (not viewers)
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
        if ($user->role === 'admin') {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only admins and editors within the org can export (not viewers)
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
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
        if ($user->role === 'admin') {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only org admins can manage members (not editors or viewers)
            return $user->hasOrganization($orgId, 'admin');
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
        if ($user->role === 'admin') {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only org admins can create storage (not editors or viewers)
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        
        return false;
    }
}
