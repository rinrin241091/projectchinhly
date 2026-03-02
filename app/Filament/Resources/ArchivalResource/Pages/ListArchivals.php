<?php

namespace App\Filament\Resources\ArchivalResource\Pages;

use App\Filament\Resources\ArchivalResource;
use Filament\Resources\Pages\ListRecords;

class ListArchivals extends ListRecords
{
    protected static string $resource = ArchivalResource::class;

    public function getTitle(): string
    {
        return 'Đơn vị lưu trữ';
    }

    public function getBreadcrumb(): ?string
    {
        // Return the navigation group name from the resource, replacing 'Archival' with the group name
        return static::$resource::$navigationGroup ?? 'Đơn vị lưu trữ';
    }
}
