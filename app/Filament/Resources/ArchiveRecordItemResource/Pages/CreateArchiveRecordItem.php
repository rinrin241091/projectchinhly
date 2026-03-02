<?php

namespace App\Filament\Resources\ArchiveRecordItemResource\Pages;

use App\Filament\Resources\ArchiveRecordItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArchiveRecordItem extends CreateRecord
{
    protected static string $resource = ArchiveRecordItemResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Mục lục hồ sơ đã được tạo thành công';
    }
    
    public function getTitle(): string
    {
        return 'Tạo mục lục hồ sơ mới';
    }
}
