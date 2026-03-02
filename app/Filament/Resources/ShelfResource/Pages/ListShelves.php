<?php

namespace App\Filament\Resources\ShelfResource\Pages;

// Import lớp ShelfResource từ namespace App\Filament\Resources
use App\Filament\Resources\ShelfResource;
// Import lớp Actions từ thư viện Filament
use Filament\Actions;
// Import lớp ListRecords từ thư viện Filament
use Filament\Resources\Pages\ListRecords;
// Import lớp BulkCreateShelves từ namespace App\Filament\Pages
use App\Filament\Pages\BulkCreateShelves;

// Định nghĩa lớp ListShelves kế thừa từ lớp ListRecords
class ListShelves extends ListRecords
{
    // Khai báo thuộc tính tĩnh $resource, liên kết với ShelfResource
    protected static string $resource = ShelfResource::class;

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
