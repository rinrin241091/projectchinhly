<?php

namespace App\Filament\Resources\ShelveResource\Pages;

use App\Filament\Resources\ShelveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Shelf;
use Illuminate\Database\Eloquent\Model;

class CreateShelve extends CreateRecord
{
    protected static string $resource = ShelveResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
   foreach ($data['shelves'] as $shelf) {
            Shelf::create([
                'code' => $shelf['code'],
                'description' => $shelf['description'],
                'storage_id' => $data['storage_id'], // Gán kho đã chọn
            ]);
        }

        // Không tạo bản ghi chính vì bạn đang dùng repeater
        return [];
        $this->form->fill(); // reset trạng thái

    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Chuyển về danh sách Đơn vị lưu trữ
    }
    protected function handleRecordCreation(array $data): Model
    {
       // return Shelf::make(); // hoặc throw nếu bạn muốn không tạo bản ghi cha
      return Shelf::make();
    }
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}
