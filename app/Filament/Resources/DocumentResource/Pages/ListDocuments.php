<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
