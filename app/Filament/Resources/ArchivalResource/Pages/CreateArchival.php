<?php

namespace App\Filament\Resources\ArchivalResource\Pages;

use App\Filament\Resources\ArchivalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArchival extends CreateRecord
{
    protected static string $resource = ArchivalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            'identifier' => trim((string) ($data['identifier'] ?? '')),
            'name' => trim((string) ($data['name'] ?? '')),
            'address' => filled($data['address'] ?? null) ? trim((string) $data['address']) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'manager' => filled($data['manager'] ?? null) ? trim((string) $data['manager']) : null,
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Tạo đơn vị lưu trữ thành công';
    }

    protected function getValidationMessages(): array
    {
        return [
            'data.identifier.required' => 'Vui lòng nhập mã cơ quan lưu trữ.',
            'data.identifier.unique' => 'Mã cơ quan lưu trữ đã tồn tại. Vui lòng nhập mã khác.',
            'data.name.required' => 'Vui lòng nhập tên cơ quan lưu trữ.',
            'data.email.email' => 'Email liên hệ không đúng định dạng.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Chuyển về danh sách Đơn vị lưu trữ
    }
}
