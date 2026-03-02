<?php

namespace App\Filament\Resources\RecordTypeResource\Pages;

use App\Filament\Resources\RecordTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRecordType extends CreateRecord
{
    protected static string $resource = RecordTypeResource::class;

    public function getTitle(): string
    {
        return 'Tạo loại hồ sơ mới';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
