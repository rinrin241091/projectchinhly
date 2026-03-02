<?php

namespace App\Filament\Resources\ShelveResource\Pages;

use App\Filament\Resources\ShelveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShelve extends EditRecord
{
    protected static string $resource = ShelveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
