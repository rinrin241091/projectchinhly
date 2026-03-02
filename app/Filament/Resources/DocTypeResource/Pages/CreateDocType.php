<?php

namespace App\Filament\Resources\DocTypeResource\Pages;

use App\Filament\Resources\DocTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDocType extends CreateRecord
{
    protected static string $resource = DocTypeResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Chuyển về danh sách Đơn vị lưu trữ
    }
}
