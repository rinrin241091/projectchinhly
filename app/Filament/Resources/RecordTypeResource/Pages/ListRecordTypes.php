<?php

namespace App\Filament\Resources\RecordTypeResource\Pages;

use App\Filament\Resources\RecordTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecordTypes extends ListRecords
{
    protected static string $resource = RecordTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => RecordTypeResource::canCreate()),
        ];
    }
    public function getTitle(): string
    {
        return 'Danh sách Loại hồ sơ';
    }
}
