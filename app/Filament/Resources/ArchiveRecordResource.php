<?php
namespace App\Filament\Resources;

use App\Traits\RoleBasedPermissions;

use App\Filament\Resources\ArchiveRecordResource\Pages;
use App\Models\ArchiveRecord;
use App\Models\ArchiveRecordItem;
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

        return $form->schema([
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Hidden::make('organization_id')
                        ->default(fn () => session('selected_archival_id'))
                        ->required(),
                    Forms\Components\Card::make(heading:'Số lưu trữ')
                        ->schema([
                            Forms\Components\Placeholder::make('selected_phong')
                                ->label('Phông đang chọn')
                                ->content(function () {
                                    $archivalId = session('selected_archival_id');
                                    return $archivalId ? Organization::find($archivalId)?->name : 'Chưa chọn';
                                })
                                ->visible(fn () => session()->has('selected_archival_id')),
                            Forms\Components\Select::make('storage_id')
                                ->label('Chọn kho')
                                ->options(function ($state, callable $set, $record) {
                                    $orgId = session('selected_archival_id');

                                    $arId = $orgId ? Organization::find($orgId)?->archival_id  : null;
                                    

                                    $archivalId = $record->box?->shelf?->storage?->archival?->id ?? $arId;
                                  //  return \Log::info(session('selected_archival_id').$arId);
                                  //  if (!$archivalId) 
                                    
                                    return \App\Models\Storage::where('archival_id', $archivalId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->afterStateHydrated(function ($state, callable $set, $record) {
                                    if (!$state && $record?->storage_id) {
                                        $set('storage_id', $record->box?->shelf?->storage?->id);
                                    }
                                })
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($set) => $set('shelve_id', null)),
                            Forms\Components\Select::make('shelve_id')
                                ->statePath('shelve_id')
                                ->label('Chọn kệ chứa')
                                ->options(function (callable $get) {
                                    $storageId = $get('storage_id');
                                    return $storageId
                                        ? Shelf::where('storage_id', $storageId)->pluck('description', 'id')
                                        : [];
                                })
                                ->afterStateHydrated(function ($state, callable $set, $record) {
                                    if (!$state && $record?->storage_id) {
                                        $set('shelve_id', $record->box?->shelf?->id);
                                    }
                                })
                                ->required()
                                ->reactive()
                                ->disabled(fn (callable $get) => !$get('storage_id')),
                            // ------------------------------------//
                            

                            // --------------------------------//
                            Forms\Components\Select::make('box_id')
                            ->label($fieldLabels['box_id'])
                            ->options(function ($state, callable $get, $record) {
                                $shelfId = $get('shelve_id');
                                //$shelfId = $record->box?->shelf?->id ?? session('selected_shelf_id');
                                if (!$shelfId) return [];
                                
                                return Box::where('shelf_id', $shelfId)
                                    ->get()
                                    ->mapWithKeys(fn ($box) => [$box->id => $box->code . ' - ' . $box->description])
                                    ->toArray();
                            })
                            ->default(fn () => session('selected_box_id'))
                            ->searchable(['code', 'description'])
                            ->required()
                            ->reactive(),
                            //---------------------------------//

                            Forms\Components\Select::make('archive_record_item_id')
                                ->label('Chọn mục lục hồ sơ')
                                ->default(fn () => session('selected_archive_record_item_id'))
                                ->relationship('archiveRecordItem', 'title', fn ($query) =>
                                    $query->where('organization_id', session('selected_archival_id'))
                                )
                                ->required()
                                ->reactive()
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
                            Forms\Components\TextInput::make('reference_code')
                                ->label('Mã tham chiếu')
                                ->disabled()
                                ->dehydrated(false)
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
                                ->visible(function (callable $get) {
                                    return $get('organization_id') || session()->has('selected_archival_id');
                                }),
                            Forms\Components\Textarea::make('description')
                                ->label('Chú giải')
                                ->rows(3)
                                ->maxLength(65535)
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
                        ->columns(1)
                        ->label('Thông tin hồ sơ'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    private static function getSelectedOrganizationType(): ?string
    {
        $archivalId = session('selected_archival_id');

        return $archivalId ? Organization::find($archivalId)?->type : null;
    }

    private static function isPartyOrganization(): bool
    {
        return static::getSelectedOrganizationType() === 'Đảng';
    }

    private static function getArchiveRecordFieldLabels(): array
    {
        if (static::isPartyOrganization()) {
            return [
                'box_id' => 'Chọn hộp số',
                'code' => 'Địa chỉ BQ',
                'title' => 'Tên đơn vị bảo quản',
                'start_date' => 'Ngày hồ sơ bắt đầu (BĐ)',
                'end_date' => 'Ngày hồ sơ kết thúc (KT)',
                'preservation_duration' => 'THBQ',
                'page_count' => 'Số trang',
            ];
        }

        return [
            'box_id' => 'Chọn hộp',
            'code' => 'Mã hồ sơ',
            'title' => 'Tiêu đề hồ sơ',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'preservation_duration' => 'Thời hạn bảo quản',
            'page_count' => 'Số trang',
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
                    ->label('Lộc theo ngày bắt đầu')
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
                        $query = static::getModel()::query();

                        $archive_record_item = session('selected_archive_record_item_id');
                        if (!empty($archive_record_item)) {
                            $query->where('archive_record_item_id', $archive_record_item);
                        }

                        $export = new \App\Exports\ArchiveRecordsExport($query);
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
                                if ($user->role === 'admin') {
                                    return Organization::pluck('name', 'id');
                                } else {
                                    return $user->organizations()->pluck('name', 'id');
                                }
                            })
                            ->required(),                       

                    ])
                    ->action(function (array $data) {
                        session(['selected_archival_id' => $data['selected_archival_id_modal']]);
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
                        session()->forget('selected_archival_id');
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
                Tables\Columns\TextColumn::make('code')
                    ->label($black('Địa chỉ BQ'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label($black('Tên đơn vị bảo quản'))
                    ->searchable()
                    ->extraAttributes(['style' => 'width: 400px; white-space: normal;']),
                Tables\Columns\TextColumn::make('start_date')
                    ->label($black('Ngày hồ sơ (BĐ - KT)'))
                    ->html()
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $start = $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d/m/Y') : '';
                        $end   = $record->end_date   ? \Carbon\Carbon::parse($record->end_date)->format('d/m/Y')   : '';
                        return $start . '<br>' . $end;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('preservation_duration')
                    ->label($black('THBQ')),
                Tables\Columns\TextColumn::make('page_count')
                    ->label($black('Số trang')),
                Tables\Columns\TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label($black('Số tài liệu')),
                Tables\Columns\TextColumn::make('box.code')
                    ->label($black('Số cặp'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label($black('Ghi chú'))
                    ->wrap(),
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