<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        if (! in_array(auth()->user()?->role, ['super_admin', 'admin'], true)) {
            return [];
        }

        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        if (! in_array(auth()->user()?->role, ['super_admin', 'admin'], true)) {
            return [
                Actions\Action::make('back')
                    ->label('Quay lại')
                    ->url(static::getResource()::getUrl('index'))
                    ->color('gray'),
            ];
        }

        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
