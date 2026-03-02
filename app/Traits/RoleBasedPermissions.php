<?php

namespace App\Traits;

trait RoleBasedPermissions
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin can access everything
        if ($user->role === 'admin') {
            return true;
        }
        
        // InputData can only access ArchiveRecord and Document resources
        if ($user->role === 'input_data') {
            return in_array(static::class, [
                \App\Filament\Resources\ArchiveRecordResource::class,
                \App\Filament\Resources\DocumentResource::class,
            ]);
        }
        
        // Regular users have no access to admin resources
        return false;
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'admin' || $user->role === 'input_data');
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'admin' || $user->role === 'input_data');
    }
    
    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user && ($user->role === 'admin' || $user->role === 'input_data');
    }
}
