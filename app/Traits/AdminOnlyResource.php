<?php

namespace App\Traits;

trait AdminOnlyResource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }
    
    public static function canEdit($record): bool
    {
        return auth()->user()?->role === 'admin';
    }
    
    public static function canDelete($record): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
