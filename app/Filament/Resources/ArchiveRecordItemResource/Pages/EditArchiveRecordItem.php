<?php

namespace App\Filament\Resources\ArchiveRecordItemResource\Pages;

use App\Filament\Resources\ArchiveRecordItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchiveRecordItem extends EditRecord
{
    protected static string $resource = ArchiveRecordItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
