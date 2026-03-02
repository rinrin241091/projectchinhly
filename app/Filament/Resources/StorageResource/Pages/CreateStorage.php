<?php

namespace App\Filament\Resources\StorageResource\Pages;

use App\Filament\Resources\StorageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStorage extends CreateRecord
{
    protected static string $resource = StorageResource::class;
    public function getTitle(): string
    {
        return 'Thêm kho lưu trữ mới';
    }
    //Hàm kết hộp mã kho + mã đơn vị lưu trữ lưu vào sơ sở dữ liệu
    // protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    // {
    //     if (isset($data['archival_id']) && isset($data['code'])) {
    //         $data['code'] = $data['archival_id'] . '.' . $data['code'];
    //     }
    //     return parent::handleRecordCreation($data);
    // }

    protected function getRedirectUrl(): string
    {
         return $this->getResource()::getUrl('index'); // Chuyển về danh sách Kho
    }
}
