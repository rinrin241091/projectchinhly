<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getValidationMessages(): array
    {
        return [
            'data.code.required' => 'Vui lòng nhập code phông.',
            'data.code.unique' => 'Code phông này đã tồn tại. Vui lòng nhập code khác.',
            'data.name.required' => 'Vui lòng nhập tên phông.',
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
{
    $startYear = $this->form->getState()['start_year'] ?? null;
    $endYear = $this->form->getState()['end_year'] ?? null;

    $data['archivals_time'] = $startYear . '-' . $endYear;

    return $data;
}
}
