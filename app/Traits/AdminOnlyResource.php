<?php

namespace App\Traits;

trait AdminOnlyResource
{
    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'admin'], true);
    }
    
    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'admin'], true);
    }
    
    public static function canEdit($record): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'admin'], true);
    }
    
    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'admin'], true);
    }
}
