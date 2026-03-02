<?php

namespace App\Filament\Resources\ArchiveRecordResource\Pages;

use App\Filament\Resources\ArchiveRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListArchiveRecords extends ListRecords
{
    protected static string $resource = ArchiveRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (session()->has('selected_archival_id')) {
            $query->where('organization_id', session('selected_archival_id'));
        }

        return $query;
    }
        public function getTitle(): string
    {
        return 'Danh sách hồ sơ lưu trữ';
    }
}
