<?php

namespace App\Filament\Resources\ArchivalResource\Pages;

use App\Filament\Resources\ArchivalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArchival extends CreateRecord
{
    protected static string $resource = ArchivalResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Chuyển về danh sách Đơn vị lưu trữ
    }
}
