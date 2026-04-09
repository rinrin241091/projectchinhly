<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string
    {
        return 'Thêm phông lưu trữ mới';
    }

    protected function getValidationMessages(): array
    {
        return [
            'data.code.required' => 'Vui lòng nhập code phông.',
            'data.code.unique' => 'Code phông này đã tồn tại. Vui lòng nhập code khác.',
            'data.name.required' => 'Vui lòng nhập tên phông.',
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $startYear = $this->form->getState()['start_year'] ?? null;
        $endYear = $this->form->getState()['end_year'] ?? null;

        // Gộp cẩn thận, tránh null
        $data['archivals_time'] = trim("{$startYear}-{$endYear}", '-');

        if ($startYear && $endYear) {
            $data['archivals_time'] = "$startYear-$endYear";
        } elseif ($startYear) {
            $data['archivals_time'] = "$startYear-";
        } elseif ($endYear) {
            $data['archivals_time'] = "-$endYear";
        } else {
            $data['archivals_time'] = null;
        }

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Chuyển về danh sách Đơn vị lưu trữ
    }
}
