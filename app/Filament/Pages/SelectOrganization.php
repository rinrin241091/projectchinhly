<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Organization;

class SelectOrganization extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $title = 'Chọn phông làm việc';
    protected static string $view = 'filament.pages.select-organization';
    protected static bool $shouldRegisterNavigation = false; // Không hiển thị trong menu điều hướng

    public ?string $type = null;
    public ?int $organizationId = null; // chosen organization id

    public function mount(): void
    {
        // khi truy cập trang chọn phông, luôn xóa phiên trước
        // để đảm bảo thanh điều hướng không hiển thị dù session cũ có tồn tại
        session()->forget(['organization_id', 'organization_type', 'selected_archival_id']);

        // Gán lại từ session nếu người dùng đã chọn trước đó (ít khi dùng vì vừa xóa)
        $this->type = session('organization_type');
        $this->organizationId = session('organization_id');
    }
    public function hasLogo(): bool
    {
        return false;
    }
    
    

    public function save(): void
    {
        session([
            'organization_type' => $this->type,
            'organization_id' => $this->organizationId,
            'selected_archival_id' => $this->organizationId,
        ]);

        $organization = Organization::find($this->organizationId);

        Notification::make()
            ->title('Đã chọn phông: ' . ($organization?->name ?? 'Không xác định'))
            ->success()
            ->send();

            $this->redirect(route('filament.dashboard.pages.dashboard'));
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('type')
                ->label('Loại phông')
                ->options([
                    'Đảng' => 'Phông Đảng',
                    'Chính quyền' => 'Phông Chính quyền',
                ])
                ->live()
                ->required(),

            Select::make('organizationId')
                ->label('Chọn phông')
                ->options(function (callable $get) {
                    if (!$get('type')) {
                        return [];
                    }

                    $query = Organization::query()
                        ->where('type', $get('type'));

                    // nếu user không phải admin, chỉ hiển thị những phông được gán
                    if (auth()->check() && auth()->user()->role !== 'admin') {
                        $query->whereIn('id', auth()->user()->organizations()->pluck('organizations.id'));
                    }

                    return $query->pluck('name', 'id');
                })
                ->required(),
        ]);
    }
}
