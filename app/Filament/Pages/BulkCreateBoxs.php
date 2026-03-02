<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Shelf;
use App\Models\Storage;
use App\Models\Archival;
use App\Models\Box;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;


class BulkCreateBoxs extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Tạo nhiều hộp';
     protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái
    protected static ?int $navigationSort = 7;


    protected static string $view = 'filament.pages.bulk-create-boxs';
    public ?array $data = [];


    public function mount(): void
    {
        $this->form->fill(); // form từ trait InteractsWithForms
    }
    public function getTitle(): string
    {
        return 'Tạo nhiều hộp';
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
                        ->heading('Thêm nhiều hộp')
                        ->description('Chọn đơn vị lưu trữ, kho và kệ, sau đó nhập thông tin các hộp cần tạo.')
                        ->columns(1)
                        ->schema([
                            Select::make('archival_id')
                                ->statePath('archival_id')
                                ->label('Đơn vị lưu trữ')
                                ->options(Archival::pluck('name', 'id'))
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn (callable $set) => $set('storage_id', null)),

                            Select::make('storage_id')
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

                            Select::make('shelve_id')
                                ->statePath('shelve_id')
                                ->label('Chọn kệ chứa')
                                ->options(function (callable $get) {
                                    $storageId = $get('storage_id');
                                    return $storageId
                                        ? Shelf::where('storage_id', $storageId)->pluck('description', 'id')
                                        : [];
                                })
                                ->required()
                                ->reactive()
                                ->disabled(fn (callable $get) => !$get('storage_id')),

                            Repeater::make('boxes')
                                ->statePath('boxes')
                                ->label('Danh sách hộp')
                                ->schema([
                                    TextInput::make('code')->label('Mã Hộp')->required(),
                                    TextInput::make('description')->label('Tên/Mô tả hộp')->required(),
                                    TextInput::make('type')->label('Loại hộp'),
                                    TextInput::make('status')->label('Vị trí lưu trữ'),
                                ])
                                ->minItems(1)
                                ->required(),
                        ]),
                ]),
        ];
    }
    public function createBoxs(): void
    {
        $data = $this->form->getState();

        $this->form->fill(); // reset form
        session()->flash('success', 'Đã tạo danh sách hộp thành công!');

        //CODE IN NHÃN
        $createdBoxes = [];

        foreach ($data['boxes'] as $box) {
            $created = Box::create([
                'shelf_id' => $data['shelve_id'],
                'code' => $box['code'],
                'type' => $box['type'] ?? null,
                'description' => $box['description'] ?? null,
                'record_count' => $box['record_count'] ?? null,
                'page_count' => $box['page_count'] ?? null,
                'status' => $box['status'] ?? null,
            ]);
            $createdBoxes[] = $created->id;
        }

        $url = route('print.box.labels', ['ids' => $createdBoxes]);

        Notification::make()
            ->title('Tạo hộp thành công!')
            ->success()
            ->body('Bạn có thể in nhãn hộp ngay.')
            ->actions([
                \Filament\Notifications\Actions\Action::make('print')
                    ->label('In nhãn')
                    ->url($url, shouldOpenInNewTab: true)
            ])
            ->send();
            $this->redirect('/dashboard/boxes');

            }
}
