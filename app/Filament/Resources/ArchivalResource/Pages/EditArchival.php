<?php

namespace App\Filament\Resources\ArchivalResource\Pages;

use App\Filament\Resources\ArchivalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchival extends EditRecord
{
    protected static string $resource = ArchivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Chuyển về danh sách sản phẩm
    }
}
