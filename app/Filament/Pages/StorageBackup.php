<?php

namespace App\Filament\Pages;

use App\Models\Storage;
use App\Models\StorageBackupSchedule;
use Illuminate\Support\Collection;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StorageBackup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Sao lưu dữ liệu kho';

    protected static ?string $title = 'Sao lưu dữ liệu kho';

    protected static string $view = 'filament.pages.storage-backup';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('storage_id')
                    ->label('Chọn kho cần sao lưu')
                    ->options(fn (): array => Storage::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('schedule_storage_id')
                    ->label('Kho đặt lịch sao lưu')
                    ->options(fn (): array => Storage::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),

                TimePicker::make('backup_time')
                    ->label('Giờ sao lưu hằng ngày')
                    ->seconds(false)
                    ->format('H:i')
                    ->displayFormat('H:i'),

                Toggle::make('is_active')
                    ->label('Bật lịch sao lưu')
                    ->default(true),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadBackup')
                ->label('Sao lưu dữ liệu')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(function (): string {
                    $storageId = $this->data['storage_id'] ?? null;

                    if (! $storageId) {
                        Notification::make()
                            ->title('Vui lòng chọn kho trước khi sao lưu')
                            ->warning()
                            ->send();

                        return '#';
                    }

                    return route('storage-backup.download', ['storage' => $storageId]);
                })
                ->openUrlInNewTab(),

            Action::make('saveSchedule')
                ->label('Lưu lịch sao lưu')
                ->icon('heroicon-o-clock')
                ->color('primary')
                ->action(function (): void {
                    $scheduleStorageId = $this->data['schedule_storage_id'] ?? null;
                    $backupTime = $this->data['backup_time'] ?? null;
                    $isActive = (bool) ($this->data['is_active'] ?? true);

                    if (! $scheduleStorageId || ! $backupTime) {
                        Notification::make()
                            ->title('Vui lòng chọn kho và giờ sao lưu')
                            ->warning()
                            ->send();

                        return;
                    }

                    StorageBackupSchedule::query()->updateOrCreate(
                        ['storage_id' => $scheduleStorageId],
                        [
                            'backup_time' => date('H:i:s', strtotime((string) $backupTime)),
                            'is_active' => $isActive,
                        ]
                    );

                    Notification::make()
                        ->title('Đã lưu lịch sao lưu thành công')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getSchedulesProperty(): Collection
    {
        return StorageBackupSchedule::query()
            ->with('storage:id,name')
            ->orderBy('backup_time')
            ->get();
    }

    public function editSchedule(int $scheduleId): void
    {
        $schedule = StorageBackupSchedule::query()->find($scheduleId);

        if (! $schedule) {
            Notification::make()
                ->title('Không tìm thấy lịch sao lưu')
                ->danger()
                ->send();

            return;
        }

        $this->data['schedule_storage_id'] = $schedule->storage_id;
        $this->data['backup_time'] = optional($schedule->backup_time)->format('H:i');
        $this->data['is_active'] = (bool) $schedule->is_active;

        Notification::make()
            ->title('Đã nạp lịch lên form, chỉnh sửa rồi bấm Lưu lịch sao lưu')
            ->success()
            ->send();
    }

    public function deleteSchedule(int $scheduleId): void
    {
        $schedule = StorageBackupSchedule::query()->find($scheduleId);

        if (! $schedule) {
            Notification::make()
                ->title('Lịch sao lưu không tồn tại')
                ->warning()
                ->send();

            return;
        }

        $schedule->delete();

        Notification::make()
            ->title('Đã xóa lịch sao lưu')
            ->success()
            ->send();
    }
}
