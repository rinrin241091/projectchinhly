<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tổng quan';
    protected static ?string $navigationLabel = 'Tổng quan';

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(auth()->user()?->role, ['data_entry', 'input_data'], true);
    }
}