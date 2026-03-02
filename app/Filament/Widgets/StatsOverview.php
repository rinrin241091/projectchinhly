<?php

namespace App\Filament\Widgets;

use App\Models\ArchiveRecord;
use App\Models\Document;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Tổng số hồ sơ lưu trữ', ArchiveRecord::count())
                ->description('Tất cả hồ sơ trong hệ thống')
                ->descriptionIcon('heroicon-m-archive-box')
                ->extraAttributes([
                    'class' => 'widget-with-bg-icon widget-blue widget-card',
                ]),

            Stat::make('Tổng số tài liệu', Document::count())
                ->description('Tất cả tài liệu đã lưu')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Tổng số người dùng', User::count())
                ->description('Tài khoản trong hệ thống')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}