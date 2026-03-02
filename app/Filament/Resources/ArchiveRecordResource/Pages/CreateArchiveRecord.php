<?php

namespace App\Filament\Resources\ArchiveRecordResource\Pages;

use App\Filament\Resources\ArchiveRecordResource;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CreateArchiveRecord extends CreateRecord
{
    protected static string $resource = ArchiveRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
           
           
        ];
    }
    protected function getValidationMessages(): array
    {
        return [
            'reference_code.required' => 'Vui lòng nhập mã hồ sơ.',
            'reference_code.unique' => 'Mã hồ sơ đã tồn tại trong phông này.',
            'title.required' => 'Tiêu đề không được để trống.',
            'start_date.required' => 'Ngày bắt đầu không được để trống.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];
    }
        public function getRules(): array
    {
        return [
            'reference_code' => [
                'required',
                Rule::unique('archive_records', 'reference_code')
                    ->where(fn ($query) => $query->where('organization_id', session('selected_archival_id')))
            ],
            'title' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Chuyển về danh sách Đơn vị lưu trữ
    }

    public function getTitle(): string
    {
        return 'Thêm hồ sơ lưu trữ mới';
    }

    protected function getModalHeading(): string
    {
        return 'Thêm hồ sơ lưu trữ mới';
    }
}
