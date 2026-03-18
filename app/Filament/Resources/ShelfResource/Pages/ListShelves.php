<?php

namespace App\Filament\Resources\ShelfResource\Pages;

use App\Filament\Resources\ShelveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Pages\BulkCreateShelves;

// NOTE: This file is a legacy duplicate. The active resource is ShelveResource.
class ListShelves extends ListRecords
{
    protected static string $resource = ShelveResource::class;

    // Phương thức getHeaderActions trả về một mảng các hành động trên header
    protected function getHeaderActions(): array
    {
        return [
            // Tạo một hành động mới với tên 'bulk_create'
            Actions\Action::make('bulk_create')
                // Gán nhãn cho hành động là 'Tạo Kệ/Tủ'
                ->label('Tạo Kệ/Tủ')
                // Gán biểu tượng cho hành động
                ->icon('heroicon-o-plus-circle')
                // Gán URL cho hành động, sử dụng phương thức getUrl của BulkCreateShelves
                ->url(BulkCreateShelves::getUrl()),
        ];
    }
}
