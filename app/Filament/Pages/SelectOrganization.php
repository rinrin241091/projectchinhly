<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class SelectOrganization extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $title = 'Chọn phông làm việc';
    protected static string $view = 'filament.pages.select-organization';
    protected static bool $shouldRegisterNavigation = false; // Không hiển thị trong menu điều hướng

    public ?string $type = null;
    public ?int $organizationId = null; // chosen organization id

    //chỉ admin mới có quyền truy cập trang này để chọn phông, người dùng bình thường sẽ được gán phông mặc định và không cần chọn lại
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

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
        $organization = Organization::find($this->organizationId);
        
        session([
            'organization_type' => $this->type,
            'organization_id' => $this->organizationId,
            'selected_archival_id' => $this->organizationId,
            'archival_id' => $organization?->archival_id, // Load archival of the organization
        ]);

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
                ->afterStateUpdated(fn (callable $set) => $set('organizationId', null))
                ->required(),

            Select::make('organizationId')
                ->label('Chọn phông')
                ->options(function (callable $get) {
                    if (!$get('type')) {
                        return [];
                    }

                    $selectedType = (string) $get('type');

                    $query = Organization::query()
                        ->where(function (Builder $builder) use ($selectedType): void {
                            $builder
                                ->where('type', $selectedType)
                                ->orWhere('type', 'Phông ' . $selectedType);
                        });

                    // nếu user không phải admin, chỉ hiển thị những phông được gán
                    if (auth()->check() && auth()->user()->role !== 'admin') {
                        $query->whereIn('id', auth()->user()->organizations()->pluck('organizations.id'));
                    }

                    return $query->orderBy('name')->pluck('name', 'id');
                })
                ->required(),
        ]);
    }
}
