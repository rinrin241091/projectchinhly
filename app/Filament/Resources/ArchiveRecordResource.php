<?php
namespace App\Filament\Resources;

use App\Traits\RoleBasedPermissions;

use App\Filament\Resources\ArchiveRecordResource\Pages;
use App\Models\ArchiveRecord;
use App\Models\ArchiveRecordItem;
use App\Models\RecordType;
use App\Models\Organization;
use App\Models\Archival;
use App\Models\Storage;
use App\Models\Shelf;
use App\Models\Box;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

class ArchiveRecordResource extends Resource
{
    use RoleBasedPermissions;

    protected static ?string $model = ArchiveRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Hồ sơ lưu trữ';
    
    protected static ?string $navigationGroup = 'Nhập liệu - Biên mục';

    public static function form(Form $form): Form
    {
        $fieldLabels = static::getArchiveRecordFieldLabels();
        $isPartyOrganization = static::isPartyOrganization();

        return $form->schema([
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Hidden::make('organization_id')
                        ->default(fn () => session('selected_archival_id'))
                        ->required(),
                    Forms\Components\Card::make(heading:'Số lưu trữ')
                        ->schema([
                            Forms\Components\Placeholder::make('selected_phong')
                                ->label($isPartyOrganization ? 'Phông số' : 'Phông đang chọn')
                                ->content(function () {
                                    $archivalId = session('selected_archival_id');
                                    $organization = $archivalId ? Organization::find($archivalId) : null;

                                    if (! $organization) {
                                        return 'Chưa chọn';
                                    }

                                    if (static::isPartyOrganization()) {
                                        return trim(collect([$organization->code, $organization->name])->filter()->implode(' - '));
                                    }

                                    return $organization->name;
                                })
                                ->visible(fn () => session()->has('selected_archival_id')),
                            Forms\Components\Select::make('storage_id')
                                ->label('Chọn kho')
                                ->options(function ($record) {
                                    // Khi sửa record: lấy archival từ box đang gán
                                    // Khi tạo mới: lấy archival từ phông đang chọn trong session
                                    $archivalId = $record?->box?->shelf?->storage?->archival_id
                                        ?? $record?->storage?->archival_id
                                        ?? (($orgId = session('selected_archival_id'))
                                            ? Organization::find($orgId)?->archival_id
                                            : null);

                                    if (!$archivalId) return [];

                                    return \App\Models\Storage::where('archival_id', $archivalId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->default(fn () => session('archive_record_create_storage_id'))
                                ->afterStateHydrated(function ($state, callable $set, $record) {
                                    if (!$state && $record?->box_id) {
                                        $set('storage_id', $record?->box?->shelf?->storage?->id);
                                    } elseif (! $state && session()->has('archive_record_create_storage_id')) {
                                        $set('storage_id', session('archive_record_create_storage_id'));
                                    }
                                })
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    session([
                                        'archive_record_create_storage_id' => $state,
                                        'archive_record_create_shelve_id' => null,
                                        'archive_record_create_box_id' => null,
                                    ]);

                                    $set('shelve_id', null);
                                    $set('box_id', null);
                                }),
                            Forms\Components\Select::make('shelve_id')
                                ->label('Chọn kệ chứa')
                                ->options(function (callable $get) {
                                    $storageId = $get('storage_id');
                                    return $storageId
                                        ? Shelf::where('storage_id', $storageId)->pluck('description', 'id')->toArray()
                                        : [];
                                })
                                ->default(fn () => session('archive_record_create_shelve_id'))
                                ->afterStateHydrated(function ($state, callable $set, $record) {
                                    if (!$state && $record?->box_id) {
                                        $set('shelve_id', $record?->box?->shelf?->id);
                                    } elseif (! $state && session()->has('archive_record_create_shelve_id')) {
                                        $set('shelve_id', session('archive_record_create_shelve_id'));
                                    }
                                })
                                ->required()
                                ->reactive()
                                ->disabled(fn (callable $get) => !$get('storage_id'))
                                ->afterStateUpdated(function ($state, callable $set) {
                                    session([
                                        'archive_record_create_shelve_id' => $state,
                                        'archive_record_create_box_id' => null,
                                    ]);

                                    $set('box_id', null);
                                }),
                            // ------------------------------------//
                            

                            // --------------------------------//
                            Forms\Components\Select::make('box_id')
                            ->label($fieldLabels['box_id'])
                            ->options(function (callable $get) {
                                $shelfId = $get('shelve_id');
                                if (!$shelfId) return [];

                                return Box::where('shelf_id', $shelfId)
                                    ->get()
                                    ->mapWithKeys(fn ($box) => [$box->id => $box->code . ' - ' . $box->description])
                                    ->toArray();
                            })
                            ->default(fn () => session('archive_record_create_box_id', session('selected_box_id')))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state) => session(['archive_record_create_box_id' => $state]))
                            ->disabled(fn (callable $get) => !$get('shelve_id')),
                            //---------------------------------//

                            Forms\Components\Select::make('archive_record_item_id')
                                ->label($fieldLabels['archive_record_item_id'])
                                ->default(fn () => session('archive_record_create_item_id', session('selected_archive_record_item_id')))
                                ->relationship('archiveRecordItem', 'title', fn ($query) =>
                                    $query->where('organization_id', session('selected_archival_id'))
                                )
                                ->getOptionLabelFromRecordUsing(fn (ArchiveRecordItem $record): string => static::formatArchiveRecordItemLabel($record))
                                ->searchable(['archive_record_item_code', 'title'])
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($state) => session(['archive_record_create_item_id' => $state]))
                                ->visible(fn () => session()->has('selected_archival_id')),

                            Forms\Components\TextInput::make('code')
                                ->label($fieldLabels['code'])
                                ->required()
                                ->live(!request()->route('record'))
                                ->afterStateUpdated(function ($livewire, $component) {
                                    $livewire->validateOnly($component->getStatePath());
                                })
                                ->rule(function () {
                                    $recordId = request()->route('record');
                                    return [
                                        'required',
                                        Rule::unique('archive_records', 'code')
                                            ->ignore($recordId)
                                            ->where(fn ($query) => $query->where('organization_id', session('selected_archival_id')))
                                    ];
                                })
                                ->validationAttribute('mã hồ sơ')
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                        ])
                        ->columnSpan(1)
                        ->columns(1)
                        ->label('Thông tin lưu trữ'),
                    Forms\Components\Card::make()
                        ->schema([

                            // Forms\Components\TextInput::make('symbols_code')
                            //     ->label('Ký hiệu thông tin')
                            //     ->maxLength(255)
                            //     ->visible(function (callable $get) {
                            //         return $get('organization_id') || session()->has('selected_archival_id');
                            //     }),
                            Forms\Components\TextInput::make('title')
                                ->label($fieldLabels['title'])
                                ->required()
                                ->columnSpan($isPartyOrganization ? 2 : 1)
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                            Forms\Components\TextInput::make('symbols_code')
                                ->label($fieldLabels['symbols_code'])
                                ->visible(fn () => $isPartyOrganization),
                            Forms\Components\Textarea::make('description')
                                ->label($fieldLabels['description'])
                                ->rows(3)
                                ->maxLength(65535)
                                ->columnSpan($isPartyOrganization ? 2 : 1)
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                            Forms\Components\DatePicker::make('start_date')
                                ->label($fieldLabels['start_date'])
                                ->required()
                                ->native()
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                            Forms\Components\DatePicker::make('end_date')
                                ->label($fieldLabels['end_date'])
                                ->required()
                                ->native()
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->reactive()
                                ->rules([
                                    function (callable $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $start = $get('start_date');
                                            if ($start && $value < $start) {
                                                $fail('Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.');
                                            }
                                        };
                                    },
                                ])
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                            // Forms\Components\Select::make('language')
                            //     ->label('Ngôn ngữ')
                            //     ->options([
                            //         'Tiếng Việt' => 'Tiếng Việt',
                            //         'Tiếng Anh' => 'Tiếng Anh',
                            //         'Tiếng Pháp' => 'Tiếng Pháp',
                            //         'Tiếng Trung' => 'Tiếng Trung',
                            //         'Khác' => 'Khác',
                            //     ])
                            //     ->native(false)
                            //     ->columnSpan(1)
                            //     ->visible(function (callable $get) {
                            //         return $get('organization_id') || session()->has('selected_archival_id');
                            //     }),
                            // Forms\Components\TextInput::make('handwritten')
                            //     ->label('Bút tích')
                            //     ->placeholder('Bút tích, chữ ký, v.v.')
                            //     ->maxLength(255)
                            //     ->columnSpan(1)
                            //     ->visible(function (callable $get) {
                            //         return $get('organization_id') || session()->has('selected_archival_id');
                            //     }),
                            // Forms\Components\Select::make('usage_mode')                
                            //     ->label('Chế độ sử dụng')   
                            //     ->options([
                            //         'Công khai' => 'Công khai',
                            //         'Hạn chế' => 'Hạn chế',
                            //         'Bí mật' => 'Bí mật',
                            //     ])
                            //     ->native(false)
                            //     ->columnSpan(1)
                            //     ->visible(function (callable $get) {
                            //         return $get('organization_id') || session()->has('selected_archival_id');
                            //     }),
                            Forms\Components\TextInput::make('preservation_duration')
                                ->label($fieldLabels['preservation_duration'])
                                ->placeholder('VD: 50 năm')
                                ->columnSpan(1)
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                            Forms\Components\TextInput::make('page_count')
                                ->label($fieldLabels['page_count'])
                                ->numeric()
                                ->minValue(0)
                                ->columnSpan(1)
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                            Forms\Components\Radio::make('usage_mode')
                                ->label($fieldLabels['usage_mode'])
                                ->options([
                                    'Thường' => 'Thường',
                                    'Mật' => 'Mật',
                                    'Tuyệt mật' => 'Tuyệt mật',
                                    'Tối mật' => 'Tối mật',
                                ])
                                ->default('Thường')
                                ->visible(fn () => $isPartyOrganization),
                            // Forms\Components\Select::make('condition')
                            //     ->label('Tình trạng')
                            //     ->options([
                            //         'Tốt' => 'Tốt',
                            //         'Hư hỏng nhẹ' => 'Hư hỏng nhẹ',
                            //         'Hư hỏng nặng' => 'Hư hỏng nặng',
                            //     ])
                            //     ->native(false)
                            //     ->columnSpan(1)
                            //     ->visible(function (callable $get) {
                            //         return $get('organization_id') || session()->has('selected_archival_id');
                            //     }),
                            Forms\Components\Textarea::make('note')
                                ->label('Ghi chú')
                                ->rows(2)
                                ->columnSpanFull()
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                        ])
                        ->columnSpan(1)
                        ->columns($isPartyOrganization ? 2 : 1)
                        ->label('Thông tin hồ sơ'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    private static function getSelectedOrganizationType(): ?string
    {
        if (filled(session('organization_type'))) {
            return session('organization_type');
        }

        $archivalId = session('selected_archival_id');

        return $archivalId ? Organization::find($archivalId)?->type : null;
    }

    private static function formatArchiveRecordItemLabel(ArchiveRecordItem $record): string
    {
        if (! static::isPartyOrganization()) {
            return $record->title;
        }

        return trim(collect([$record->archive_record_item_code, $record->title])->filter()->implode(' - '));
    }

    private static function isPartyOrganization(): bool
    {
        return static::getSelectedOrganizationType() === 'Đảng';
    }

    private static function getArchiveRecordFieldLabels(): array
    {
        if (static::isPartyOrganization()) {
            return [
                'archive_record_item_id' => 'Mục lục số',
                'box_id' => 'Số cặp (hộp)',
                'code' => 'Hồ sơ số',
                'title' => 'Tên nhóm và tên hồ sơ',
                'description' => 'Chú giải',
                'symbols_code' => 'Từ khóa',
                'start_date' => 'Ngày bắt đầu',
                'end_date' => 'Ngày kết thúc',
                'preservation_duration' => 'Thời hạn bảo quản',
                'page_count' => 'Số lượng tờ',
                'usage_mode' => 'Độ mật',
            ];
        }

        return [
            'archive_record_item_id' => 'Chọn mục lục hồ sơ',
            'box_id' => 'Chọn hộp',
            'code' => 'Mã hồ sơ',
            'title' => 'Tiêu đề hồ sơ',
            'description' => 'Chú giải',
            'symbols_code' => 'Ký hiệu thông tin',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'preservation_duration' => 'Thời hạn bảo quản',
            'page_count' => 'Số lượng tờ',
            'usage_mode' => 'Chế độ sử dụng',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table 
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user) return $query;
                
                $archiveRecordItemId = session('selected_archive_record_item_id');
                $archivalId = session('selected_archival_id');
                
                // Admin can filter by selected organization if chosen
                if ($user->role === 'admin') {
                    if (!empty($archivalId)) {
                        // Admin chose an organization - filter by it
                        $query->where('organization_id', $archivalId);
                        // Also filter by selected archive record item if applicable
                        if (!empty($archiveRecordItemId)) {
                            $query->where('archive_record_item_id', $archiveRecordItemId);
                        }
                    }
                    return $query;
                }
                
                // Non-admin: must select an organization and only see that org's data
                if (!empty($archivalId)) {
                    if (!$user->hasOrganization($archivalId)) {
                        // User doesn't have access to this organization
                        return $query->whereRaw('1 = 0'); // Show nothing
                    }
                    // Filter to selected organization
                    $query->where('organization_id', $archivalId);
                    // Also filter by selected archive record item if applicable
                    if (!empty($archiveRecordItemId)) {
                        $query->where('archive_record_item_id', $archiveRecordItemId);
                    }
                } else {
                    // Non-admin without selected organization: show nothing
                    return $query->whereRaw('1 = 0');
                }
                
                return $query;
})
            ->columns(static::resolveTableColumns())
            ->filters([
                Tables\Filters\Filter::make('quick_search')
                    ->label('Tìm kiếm nhanh hồ sơ')
                    ->form([
                        Forms\Components\TextInput::make('code')
                            ->label('Mã hồ sơ')
                            ->placeholder('Nhập mã hồ sơ...'),
                        Forms\Components\TextInput::make('title')
                            ->label('Tiêu đề hồ sơ')
                            ->placeholder('Nhập tiêu đề hồ sơ...'),
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Giai đoạn từ ngày')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Giai đoạn đến ngày')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d'),
                        Forms\Components\Select::make('record_type_id')
                            ->label('Loại hồ sơ')
                            ->options(fn () => RecordType::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['code'] ?? null, function (Builder $q, $value): Builder {
                                $keyword = trim((string) $value);

                                return $q->where(function (Builder $inner) use ($keyword): void {
                                    $inner
                                        ->where('code', 'like', "%{$keyword}%")
                                        ->orWhere('reference_code', 'like', "%{$keyword}%");
                                });
                            })
                            ->when($data['title'] ?? null, fn (Builder $q, $value): Builder => $q->where('title', 'like', '%' . trim((string) $value) . '%'))
                            ->when($data['record_type_id'] ?? null, fn (Builder $q, $value): Builder => $q->where('record_type_id', $value))
                            ->when($data['date_from'] ?? null, fn (Builder $q, $value): Builder => $q->whereDate('end_date', '>=', $value))
                            ->when($data['date_to'] ?? null, fn (Builder $q, $value): Builder => $q->whereDate('start_date', '<=', $value));
                    }),

                // Thêm filters cho Admin
                Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Lọc theo phông')
                    ->options(Organization::pluck('name', 'id'))
                    ->visible(fn () => auth()->user()?->role === 'admin'),
                
                Tables\Filters\SelectFilter::make('archiveRecordItem')
                    ->label('Lọc theo mục lục')
                    ->relationship('archiveRecordItem', 'title')
                    ->visible(fn () => auth()->user()?->role === 'admin'),
                
                Tables\Filters\Filter::make('start_date')
                    ->label('Lọc theo ngày bắt đầu')
                    ->form([
                        Forms\Components\DatePicker::make('start_date_from')
                            ->label('Từ ngày')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d'),
                        Forms\Components\DatePicker::make('start_date_to')
                            ->label('Đến ngày')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['start_date_from'], fn(Builder $q) => $q->whereDate('start_date', '>=', $data['start_date_from']))
                            ->when($data['start_date_to'], fn(Builder $q) => $q->whereDate('start_date', '<=', $data['start_date_to']));
                    })
                    ->visible(fn () => auth()->user()?->role === 'admin'),

                Tables\Filters\Filter::make('phong_so')
                    ->label('Phông số')
                    ->form([
                        Forms\Components\TextInput::make('phong_so')
                            ->label('Phông số')
                            ->placeholder('Nhập phông số...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['phong_so'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('organization', fn (Builder $inner) => $inner->where('code', 'like', '%' . trim($value) . '%'));
                        });
                    }),

                Tables\Filters\SelectFilter::make('box_id')
                    ->label('Số cặp (hộp)')
                    ->relationship('box', 'code')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('ho_so_so')
                    ->label('Hồ sơ số')
                    ->form([
                        Forms\Components\TextInput::make('ho_so_so')
                            ->label('Hồ sơ số')
                            ->placeholder('Nhập hồ sơ số...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['ho_so_so'] ?? null, fn (Builder $q, $value) => $q->where('code', 'like', '%' . trim($value) . '%'));
                    }),

                Tables\Filters\Filter::make('preservation')
                    ->label('Thời hạn bảo quản')
                    ->form([
                        Forms\Components\TextInput::make('preservation_duration')
                            ->label('Thời hạn bảo quản')
                            ->placeholder('VD: Vĩnh viễn, 50 năm...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['preservation_duration'] ?? null, fn (Builder $q, $value) => $q->where('preservation_duration', 'like', '%' . trim($value) . '%'));
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'chưa nhập' => 'Chưa nhập',
                        'đang nhập' => 'Đang nhập',
                        'đã nhập' => 'Đã nhập',
                    ])
                    ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'super_admin', 'teamlead'], true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tạo hồ sơ mới')
                    ->modalHeading('Tạo hồ sơ mới')
                    ->visible(fn () => session()->has('selected_archival_id') && static::canCreate())
                    ->modalWidth('7xl')
                    ->after(function ($record) {
                        // có thể xử lý sau khi lưu nếu cần
                    }),
                Tables\Actions\Action::make('exportExcel')
                    ->label('Xuất Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $user = auth()->user();
                        $query = static::getModel()::query();
                        $organizationId = session('selected_archival_id');
                        $archiveRecordItemId = session('selected_archive_record_item_id');

                        if (in_array($user?->role, ['admin', 'super_admin'], true)) {
                            if (!empty($organizationId)) {
                                $query->where('organization_id', $organizationId);
                            }
                        } elseif (!empty($organizationId) && $user?->hasOrganization($organizationId)) {
                            $query->where('organization_id', $organizationId);
                        } else {
                            $query->whereRaw('1 = 0');
                        }

                        if (!empty($archiveRecordItemId)) {
                            $query->where('archive_record_item_id', $archiveRecordItemId);
                        }

                        $organization = $organizationId ? Organization::find($organizationId) : null;
                        $export = new \App\Exports\ArchiveRecordsExport($query, $organization);
                        return $export->download('archive_records.xlsx');
                    })
                    ->visible(fn () => session()->has('selected_archival_id') && static::canExport()),


                Tables\Actions\Action::make('chonPhong')
                    ->label('Chọn phông lưu trữ')
                    ->form([
                        Forms\Components\Select::make('selected_archival_id_modal')
                            ->label('Chọn phông lưu trữ')
                            ->options(function () {
                                $user = auth()->user();
                                if (in_array($user->role, ['admin', 'super_admin'], true)) {
                                    return Organization::pluck('name', 'id');
                                } else {
                                    return $user->organizations()->pluck('name', 'id');
                                }
                            })
                            ->required(),                       

                    ])
                    ->action(function (array $data) {
                        $organization = Organization::find($data['selected_archival_id_modal']);

                        session([
                            'organization_id' => $organization?->id,
                            'organization_type' => $organization?->type,
                            'selected_archival_id' => $organization?->id,
                            'archival_id' => $organization?->archival_id,
                            'selected_archive_record_item_id' => null,
                            'selected_archive_record_id' => null,
                        ]);
                    })
                    ->visible(fn () => !session()->has('selected_archival_id'))
                    ->extraAttributes(['class' => 'ml-auto']),
                
                Tables\Actions\Action::make('currentOrganization')
                    ->label(function () {
                        $archivalId = session('selected_archival_id');
                        return $archivalId ? 'Phông đang chọn: ' . Organization::find($archivalId)?->name : 'Chưa chọn phông';
                    })
                    ->color('success')
                    ->disabled()
                    ->visible(fn () => session()->has('selected_archival_id') && auth()->user()?->role === 'admin'),

                Tables\Actions\Action::make('resetPhong')
                    ->label(function () {
                        $archivalId = session('selected_archival_id');
                        $organizationName = $archivalId ? Organization::find($archivalId)?->name : '';
                        return 'Chọn lại phông';
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function () {
                        session()->forget([
                            'organization_id',
                            'organization_type',
                            'selected_archival_id',
                            'selected_archive_record_item_id',
                            'selected_archive_record_id',
                        ]);
                    })
                    ->visible(fn () => session()->has('selected_archival_id') && auth()->user()?->role === 'admin'),
                
                Tables\Actions\Action::make('chonMucLuc')
                    ->label('Chọn mục lục hồ sơ')
                    ->form([
                        Forms\Components\Select::make('selected_archive_record_item_id')
                            ->label('Chọn mục lục hồ sơ')
                            ->options(function () {
                                $archivalId = session('selected_archival_id');
                                if (!$archivalId) return [];
                                return \App\Models\ArchiveRecordItem::where('organization_id', $archivalId)
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Tất cả hồ sơ')
                            ->hint('Để trống để hiển thị tất cả hồ sơ'),
                    ])
                    ->action(function (array $data) {
                session(['selected_archive_record_item_id' => $data['selected_archive_record_item_id'] ?? null]);
                session(['ArchiveRecorditem' => $data['selected_archive_record_item_id'] ?? null]);
            })
            ->visible(fn () => session()->has('selected_archival_id') && auth()->user()?->role === 'admin'),
                
                Tables\Actions\Action::make('currentMucLuc')
                    ->label(function () {
                        $itemId = session('selected_archive_record_item_id');
                        if (!$itemId) return 'Tất cả hồ sơ';
                        $item = \App\Models\ArchiveRecordItem::find($itemId);
                        return 'Mục lục: ' . ($item?->title ?? 'Không xác định');
                    })
                    ->color('info')
                    ->disabled()
                    ->visible(fn () => session()->has('selected_archival_id') && auth()->user()?->role === 'admin'),

                Tables\Actions\Action::make('resetMucLuc')
                    ->label('Chọn lại mục lục')
                    ->requiresConfirmation()
                    ->color('warning')
                    ->action(function () {
                        session()->forget('selected_archive_record_item_id');
                    })
                    ->visible(fn () => session()->has('selected_archive_record_item_id') && auth()->user()?->role === 'admin'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('7xl')
                    ->visible(fn() => static::canEdit(null)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => static::canDelete(null)),
                Tables\Actions\Action::make('viewDocuments')
                    ->label('Mục lục tài liệu')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('archive-records.documents', $record->id))
                    ->openUrlInNewTab()
                    ->requiresConfirmation(false),
                Tables\Actions\Action::make('changeStatus')
                    ->label('Đổi trạng thái')
                    ->icon('heroicon-o-check-circle')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'chưa nhập' => 'Chưa nhập',
                                'đang nhập' => 'Đang nhập',
                                'đã nhập' => 'Đã nhập',
                            ])
                            ->default(fn ($record) => $record->status ?? 'chưa nhập')
                            ->disablePlaceholderSelection()
                            ->required(),
                    ])
                    ->action(function (\App\Models\ArchiveRecord $record, array $data) {
                        $record->update(['status' => $data['status']]);
                    })
                    ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'super_admin', 'teamlead'], true)),
                // Tables\Actions\Action::make('printReceipt')
                //     ->label('In phiếu tin')
                //     ->modalHeading('In phiếu tin')
                //     ->modalSubmitAction(false)
                //     ->modalWidth('lg')
                //     ->modalContent(function ($record) {
                //         return view('receipt_template', compact('record'));
                //     })
                //     ->requiresConfirmation(false)
                   


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => static::canDelete(null)),
                ]),
            ])
            ->emptyStateActions([
                
            ]);
            
    }

    

   
   


    private static function resolveTableColumns(): array
    {
        $black = fn (string $text) => new \Illuminate\Support\HtmlString(
            '<span style="color: black; font-weight: 600;">' . e($text) . '</span>'
        );

        if (static::isPartyOrganization()) {
            return [
                Tables\Columns\TextColumn::make('id')
                    ->label($black('STT'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.code')
                    ->label($black('Phông số'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('box.code')
                    ->label($black('Số cặp (hộp)'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('archiveRecordItem.archive_record_item_code')
                    ->label($black('Mục lục số'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('code')
                    ->label($black('Hồ sơ số'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label($black('Tên nhóm và tên hồ sơ'))
                    ->searchable()
                    ->extraAttributes(['style' => 'width: 400px; white-space: normal;']),
                Tables\Columns\TextColumn::make('symbols_code')
                    ->label($black('Từ khóa'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('description')
                    ->label($black('Chú giải'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label($black('Thời gian bắt đầu và kết thúc'))
                    ->html()
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $start = $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d/m/Y') : '';
                        $end   = $record->end_date   ? \Carbon\Carbon::parse($record->end_date)->format('d/m/Y')   : '';
                        return $start . '<br>' . $end;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('preservation_duration')
                    ->label($black('Thời hạn bảo quản')),
                Tables\Columns\TextColumn::make('page_count')
                    ->label($black('Số trang')),
                Tables\Columns\TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label($black('Số tài liệu')),
                Tables\Columns\TextColumn::make('usage_mode')
                    ->label($black('Độ mật')),
                Tables\Columns\TextColumn::make('note')
                    ->label($black('Ghi chú'))
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label($black('Trạng thái'))
                    ->colors([
                        'danger' => 'chưa nhập',
                        'warning' => 'đang nhập',
                        'success' => 'đã nhập',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'đã nhập' => 'Đã nhập',
                            'đang nhập' => 'Đang nhập',
                            default => 'Chưa nhập',
                        };
                    })
                    ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'super_admin', 'teamlead'], true)),
            ];
        }

        // Default layout – Chính quyền hoặc chưa chọn phông
        return [
            Tables\Columns\TextColumn::make('id')
                ->sortable(),
            Tables\Columns\TextColumn::make('box.code')->label('Hộp số')->sortable(),
            Tables\Columns\TextColumn::make('code')->label('Hồ sơ số')->searchable(),
            Tables\Columns\TextColumn::make('title')
                ->label('Tiêu đề hồ sơ')
                ->searchable()
                ->extraAttributes(['style' => 'width: 400px; white-space: normal;']),
            Tables\Columns\TextColumn::make('start_date')
                ->label('Ngày tháng bắt đầu và kết thúc')
                ->html()
                ->wrap()
                ->formatStateUsing(function ($state, $record) {
                    $start = $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d/m/Y') : '';
                    $end   = $record->end_date   ? \Carbon\Carbon::parse($record->end_date)->format('d/m/Y')   : '';
                    return $start . '<br>' . $end;
                })
                ->searchable(),
            Tables\Columns\TextColumn::make('preservation_duration')->label('Thời hạn bảo quản'),
            Tables\Columns\TextColumn::make('page_count')->label('Số lượng tờ'),
            Tables\Columns\TextColumn::make('archiveRecordItem.title')->label('Mục lục'),
            Tables\Columns\TextColumn::make('note')->label('Ghi chú')->wrap(),
            Tables\Columns\BadgeColumn::make('status')
                ->label('Trạng thái')
                ->colors([
                    'danger' => 'chưa nhập',
                    'warning' => 'đang nhập',
                    'success' => 'đã nhập',
                ])
                ->formatStateUsing(function ($state) {
                    return match ($state) {
                        'đã nhập' => 'Đã nhập',
                        'đang nhập' => 'Đang nhập',
                        default => 'Chưa nhập',
                    };
                })
                ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'super_admin', 'teamlead'], true)),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchiveRecords::route('/'),
            // Không cần dùng create/edit page riêng nữa
        ];
    }
}