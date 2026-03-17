<?php

namespace App\Filament\Pages;

use App\Models\DisasterSyncSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SystemBackup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Sao lưu hệ thống';

    protected static ?string $title = 'Sao lưu hệ thống';

    protected static string $view = 'filament.pages.system-backup';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('target_ip')
                    ->label('IP máy dự phòng')
                    ->placeholder('Ví dụ: 192.168.1.20')
                    ->required(),

                TimePicker::make('sync_time')
                    ->label('Giờ xuất/nhập sang hệ thống dự phòng')
                    ->seconds(false)
                    ->format('H:i')
                    ->displayFormat('H:i')
                    ->required(),

                Toggle::make('sync_is_active')
                    ->label('Bật lịch dự phòng')
                    ->default(true),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDisasterSchedule')
                ->label('Lưu lịch hệ thống dự phòng')
                ->icon('heroicon-o-server-stack')
                ->color('warning')
                ->action(function (): void {
                    $targetIp = trim((string) ($this->data['target_ip'] ?? ''));
                    $syncTime = $this->data['sync_time'] ?? null;
                    $isActive = (bool) ($this->data['sync_is_active'] ?? true);

                    if ($targetIp === '' || ! $syncTime) {
                        Notification::make()
                            ->title('Vui lòng nhập IP máy dự phòng và giờ chạy')
                            ->warning()
                            ->send();

                        return;
                    }

                    DisasterSyncSchedule::query()->updateOrCreate(
                        ['target_ip' => $targetIp],
                        [
                            'sync_time' => date('H:i:s', strtotime((string) $syncTime)),
                            'is_active' => $isActive,
                        ]
                    );

                    Notification::make()
                        ->title('Đã lưu lịch hệ thống dự phòng thành công')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getDisasterSchedulesProperty(): Collection
    {
        return DisasterSyncSchedule::query()
            ->orderBy('sync_time')
            ->get();
    }

    public function editDisasterSchedule(int $scheduleId): void
    {
        $schedule = DisasterSyncSchedule::query()->find($scheduleId);

        if (! $schedule) {
            Notification::make()
                ->title('Không tìm thấy lịch hệ thống dự phòng')
                ->danger()
                ->send();

            return;
        }

        $this->data['target_ip'] = $schedule->target_ip;
        $this->data['sync_time'] = optional($schedule->sync_time)->format('H:i');
        $this->data['sync_is_active'] = (bool) $schedule->is_active;

        Notification::make()
            ->title('Đã nạp lịch hệ thống dự phòng lên form')
            ->success()
            ->send();
    }

    public function deleteDisasterSchedule(int $scheduleId): void
    {
        $schedule = DisasterSyncSchedule::query()->find($scheduleId);

        if (! $schedule) {
            Notification::make()
                ->title('Lịch hệ thống dự phòng không tồn tại')
                ->warning()
                ->send();

            return;
        }

        $schedule->delete();

        Notification::make()
            ->title('Đã xóa lịch hệ thống dự phòng')
            ->success()
            ->send();
    }
}
