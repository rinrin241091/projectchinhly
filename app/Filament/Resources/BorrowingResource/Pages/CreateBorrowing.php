<?php

namespace App\Filament\Resources\BorrowingResource\Pages;

use App\Filament\Resources\BorrowingResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBorrowing extends CreateRecord
{
    protected static string $resource = BorrowingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if (! empty($data['borrowed_at']) && ! empty($data['due_at']) && $data['due_at'] < $data['borrowed_at']) {
            $data['due_at'] = $data['borrowed_at'];
        }

        if ($user?->role === 'admin') {
            $data['approval_status'] = 'approved';
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();

            return $data;
        }

        $data['user_id'] = $user?->id;
        $data['approval_status'] = 'pending';
        $data['approved_by'] = null;
        $data['approved_at'] = null;
        $data['returned_at'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();
        if ($user?->role === 'admin') {
            return;
        }

        Notification::make()
            ->title('Đã gửi đề xuất mượn ngoài thẩm quyền')
            ->body('Đề xuất của bạn đang chờ quản lý duyệt.')
            ->success()
            ->send();

        Notification::make()
            ->title('Có đề xuất mượn hồ sơ mới')
            ->body(($user?->name ?? 'Người dùng') . ' vừa tạo đề xuất mượn ngoài thẩm quyền, vui lòng kiểm tra.')
            ->warning()
            ->sendToDatabase(
                User::query()->whereIn('role', ['admin', 'teamlead'])->get(),
                isEventDispatched: true
            );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Tạo phiếu mượn hồ sơ';
    }
}
