<?php

namespace App\Filament\Resources\ArchiveRecordResource\Pages;

use App\Filament\Resources\ArchiveRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchiveRecord extends EditRecord
{
    protected static string $resource = ArchiveRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
