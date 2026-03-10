<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Shelf;
use App\Models\Storage;
use App\Models\Archival;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Notifications\Notification;

class BulkCreateShelves extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Tạo nhiều kệ';
    protected static ?string $title = 'Thêm nhiều kệ';
    protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái
    protected static string $view = 'filament.pages.bulk-create-shelves';
    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Traits\RoleBasedPermissions::canCreate();
    }

    public static function canAccess(): bool
    {
        return \App\Traits\RoleBasedPermissions::canCreate();
    }
    
    public function mount(): void
    {
        $this->form->fill(); // form từ trait InteractsWithForms
    }

    public function form(Form $form): Form
{
    return $form
        ->schema($this->getFormSchema())
        ->statePath('data'); // để liên kết với thuộc tính $data
}

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make()
                        ->heading('Thêm nhiều kệ')
                        ->description('Chọn đơn vị lưu trữ và kho, sau đó nhập thông tin các kệ cần tạo.')
                        ->columns(1)
                        ->schema([
                            Forms\Components\Select::make('archival_id')
                                ->statePath('archival_id')
                                ->label('Đơn vị lưu trữ')
                                ->options(Archival::pluck('name', 'id'))
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn (callable $set) => $set('storage_id', null)),

                            Forms\Components\Select::make('storage_id')
                                ->statePath('storage_id')
                                ->label('Kho lưu trữ')
                                ->options(function (callable $get) {
                                    $archivalId = $get('archival_id');
                                    return $archivalId
                                        ? Storage::where('archival_id', $archivalId)->pluck('name', 'id')
                                        : [];
                                })
                                ->required()
                                ->reactive()
                                ->disabled(fn (callable $get) => !$get('archival_id')),

                            Forms\Components\Repeater::make('shelves')
                                ->statePath('shelves')
                                ->label('Danh sách kệ')
                                ->schema([
                                    TextInput::make('code')
                                        ->label('Mã kệ')
                                        ->required()
                                        ->unique(table: 'shelves', column: 'code')
                                        ->reactive(),
                                    TextInput::make('description')->label('Tên/Mô tả kệ')->required(),
                                ])
                                ->minItems(1)
                                ->required(),
                        ]),
                ]),
        ];
    }

    public function createShelves(): void
    {
        $data = $this->form->getState();

        foreach ($data['shelves'] as $shelf) {
            Shelf::create([
                'code' => $shelf['code'],
                'description' => $shelf['description'] ?? null,
                'storage_id' => $data['storage_id'],
            ]);
        }

        Notification::make()
            ->title('Thành công')
            ->body('Đã tạo danh sách kệ thành công!')
            ->success()
            ->duration(3000)
            ->send();

        $this->redirect('/dashboard/shelves');
    }
}
