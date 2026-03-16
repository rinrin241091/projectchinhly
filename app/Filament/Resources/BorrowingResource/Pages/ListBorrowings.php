<?php

namespace App\Filament\Resources\BorrowingResource\Pages;

use App\Filament\Resources\BorrowingResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBorrowings extends ListRecords
{
    protected static string $resource = BorrowingResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả'),
            'borrowed' => Tab::make('Hồ sơ đã mượn')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('approval_status', 'approved')
                    ->whereNull('returned_at')),
            'return_pending' => Tab::make('Chờ admin xác nhận trả')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('approval_status', 'approved')
                    ->whereNull('returned_at')
                    ->whereNotNull('return_requested_at')),
            'returned' => Tab::make('Đã trả')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('approval_status', 'approved')
                    ->whereNotNull('returned_at')),
        ];
    }

    public function getTitle(): string
    {
        return 'Mượn trả hồ sơ';
    }
}
