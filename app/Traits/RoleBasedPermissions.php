<?php

namespace App\Traits;

trait RoleBasedPermissions
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // global admin bypasses everything
        if ($user->role === 'admin') {
            return true;
        }

        // if an archival/organization is selected, allow if user has at least viewer role
        $orgId = session('selected_archival_id');
        if ($orgId !== null && $user->hasOrganization($orgId)) {
            return true;
        }

        // fallback to previous input_data logic for non-organized access
        if ($user->role === 'input_data') {
            return in_array(static::class, [
                \App\Filament\Resources\ArchiveRecordResource::class,
                \App\Filament\Resources\DocumentResource::class,
            ]);
        }

        return false;
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin') {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // editors and admins within the org can create
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        return $user->role === 'input_data';
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin') {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        return $user->role === 'input_data';
    }
    
    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin') {
            return true;
        }
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        return $user->role === 'input_data';
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
        
        // Global admin can always import
        if ($user->role === 'admin') {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only admins and editors within the org can import (not viewers)
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        
        // input_data role can import
        return $user->role === 'input_data';
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
        
        // Global admin can always export
        if ($user->role === 'admin') {
            return true;
        }
        
        $orgId = session('selected_archival_id');
        if ($orgId !== null) {
            // Only admins and editors within the org can export (not viewers)
            return $user->hasOrganization($orgId, 'admin') || $user->hasOrganization($orgId, 'editor');
        }
        
        // input_data role can export
        return $user->role === 'input_data';
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
