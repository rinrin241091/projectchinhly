<?php

namespace App\Filament\Resources;

use App\Traits\RoleBasedPermissions;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Organization;
use App\Models\ArchiveRecord;

class DocumentResource extends Resource
{
    use RoleBasedPermissions;

    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
     protected static ?string $navigationLabel = 'Văn bản, tài liệu';
    
    protected static ?string $pluralLabel = 'Văn bản, tài liệu';
    
    protected static ?string $title = 'Văn bản, tài liệu';
    
    protected static ?string $navigationGroup = 'Nhập liệu - Biên mục';

    public static function form(Form $form): Form
    {
        $fieldLabels = static::getDocumentFieldLabels();

        return $form
            ->schema([
                Forms\Components\Placeholder::make('selected_phong')
                    ->label('Phông đang chọn')
                    ->content(function () {
                        $archivalId = session('selected_archival_id');
                        return $archivalId ? Organization::find($archivalId)?->name : 'Chưa chọn';
                    })
                    ->visible(fn () => session()->has('selected_archival_id')),

                Forms\Components\Select::make('archive_record_id')
                    ->label('Chọn hồ sơ lưu trữ')
                    ->options(function ($state, callable $set, $record) {
                        $organizationId = $record?->archive_record?->organization?->id ?? session('selected_archival_id');
                        if (!$organizationId) return [];
                        
                        return \App\Models\ArchiveRecord::where('organization_id', $organizationId)
                            ->get()
                            ->mapWithKeys(function ($record) {
                                return [$record->id => $record->code . ' - ' . $record->title];
                            })
                            ->toArray();
                    })
                    ->default(fn () => session('selected_archive_record_id')) // load sẵn từ session
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        if (!$state && $record?->organization_id) {
                            $set('organization_id', $record->archive_record?->organization?->id);
                        }
                    })
                    ->searchable(['code', 'title'])
                    ->required()
                    ->reactive(),

                Forms\Components\Select::make('doc_type_id')
                    ->label($fieldLabels['doc_type_id'])
                    ->relationship('docType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('document_number')
                    ->label('Số của văn bản')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('document_symbol')
                    ->label('Ký hiệu của văn bản')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('document_code')
                    ->label($fieldLabels['document_code'])
                    ->required(fn () => !static::isPartyOrganization())
                    ->live()
                    ->unique(table: \App\Models\Document::class, column: 'document_code', ignoreRecord: true)
                    ->visible(fn () => !static::isPartyOrganization()),

                Forms\Components\DatePicker::make('document_date')
                    ->label($fieldLabels['document_date']),

                Forms\Components\TextInput::make('issuing_agency')
                    ->label('Tên cơ quan, tổ chức ban hành văn bản')
                    ->visible(fn () => static::isPartyOrganization()),
                    
                Forms\Components\Textarea::make('description')
                    ->label($fieldLabels['description'])
                    ->rows(3),

                Forms\Components\TextInput::make('signer')
                    ->label($fieldLabels['signer'])
                    ->visible(fn () => static::isPartyOrganization())
                    ->dehydrated(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('author')
                    ->label($fieldLabels['author'])
                    ->visible(fn () => !static::isPartyOrganization())
                    ->dehydrated(fn () => !static::isPartyOrganization()),

                Forms\Components\TextInput::make('security_level')
                    ->label('Độ mật')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('copy_type')
                    ->label('Loại bản')
                    ->visible(fn () => static::isPartyOrganization()),
                
                Forms\Components\TextInput::make('page_number')
                    ->label($fieldLabels['page_number']),

                Forms\Components\TextInput::make('total_pages')
                    ->label('Số trang')
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('file_count')
                    ->label('Số lượng tệp (file)')
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('file_name')
                    ->label('Tên tệp')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('document_duration')
                    ->label('Thời gian tài liệu')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('usage_mode')
                    ->label('Chế độ sử dụng')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\Textarea::make('keywords')
                    ->label('Từ khóa')
                    ->rows(2)
                    ->visible(fn () => static::isPartyOrganization()),
                
                Forms\Components\Textarea::make('note')
                    ->label('Ghi chú')
                    ->rows(2),

                Forms\Components\TextInput::make('language')
                    ->label('Ngôn ngữ')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('handwritten')
                    ->label('Bút tích')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('topic')
                    ->label('Chuyên đề')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('information_code')
                    ->label('Ký hiệu thông tin')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('reliability_level')
                    ->label('Mức độ tin cậy')
                    ->visible(fn () => static::isPartyOrganization()),

                Forms\Components\TextInput::make('physical_condition')
                    ->label('Tình trạng vật lý')
                    ->visible(fn () => static::isPartyOrganization()),
                
                Forms\Components\FileUpload::make('file_path')
                    ->label('Tệp đính kèm')
                    ->directory('documents')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user) return $query;
                
                // Admin can see everything - no filtering
                if ($user->role === 'admin') {
                    return $query;
                }
                
                // Non-admin: must select an organization and only see that org's data
                if ($orgId = session('selected_archival_id')) {
                    if (!$user->hasOrganization($orgId)) {
                        // User doesn't have access to this organization
                        return $query->whereRaw('1 = 0'); // Show nothing
                    }
                    // Filter to selected organization
                    $query->whereHas('archive_record', function ($q) use ($orgId) {
                        $q->where('organization_id', $orgId);
                    });
                    // Also filter by selected archive record if applicable
                    if ($recordId = session('selected_archive_record_id')) {
                        $query->where('archive_record_id', $recordId);
                    }
                } else {
                    // Non-admin without selected organization: show nothing
                    return $query->whereRaw('1 = 0');
                }
                
                return $query;
            })

            ->columns(static::resolveTableColumns())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => static::canEdit(null)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => static::canDelete(null)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn() => static::canCreate()),
                Tables\Actions\Action::make('import')
                    ->label('Import')
                    ->url(static::getUrl('import'))
                    ->icon('heroicon-o-rectangle-stack')
                    ->visible(fn() => static::canImport()),
                //các nút chức năng trên phần header table
 /*NÚT CHỌN ML*/Tables\Actions\Action::make('chonMucLuc')
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
            ->visible(fn () => session()->has('selected_archival_id')),

            Tables\Actions\Action::make('currentMucLuc')
            ->label(function () {
                $itemId = session('selected_archive_record_item_id');
                if (!$itemId) return 'Tất cả hồ sơ';
                $item = \App\Models\ArchiveRecordItem::find($itemId);
                return 'Mục lục: ' . ($item?->title ?? 'Không xác định');
            })
            ->color('info')
            ->disabled()
            ->visible(fn () => session()->has('selected_archival_id')),

        Tables\Actions\Action::make('resetMucLuc')
            ->label('Chọn lại mục lục')
            ->requiresConfirmation()
            ->color('warning')
            ->action(function () {
                session()->forget('selected_archive_record_item_id');
            })
            ->visible(fn () => session()->has('selected_archive_record_item_id')),
        
            Tables\Actions\Action::make('selectDoc')
            ->label('Chọn hồ sơ')
            ->form([
                Forms\Components\Select::make('selected_archive_record_id')
                    ->label('Chọn hồ sơ')
                    ->options(function () {
                        $archivalId = session('selected_archive_record_item_id'); // lấy phông đang chọn
                        if (!$archivalId) return [];
                        return \App\Models\ArchiveRecord::where('archive_record_item_id', $archivalId)
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Tất cả tài liệu')
                    ->hint('Để trống để hiển thị tất cả tài liệu'),
            ])
            ->action(function (array $data) {
                session(['selected_archive_record_id' => $data['selected_archive_record_id'] ?? null]);
            })
            ->visible(fn () => session()->has('selected_archive_record_item_id')),

            Tables\Actions\Action::make('currentDoc')
                ->label(function () {
                $recordId = session('selected_archive_record_id');
                if (!$recordId) return 'Tất cả tài liệu';
                $record = \App\Models\ArchiveRecord::find($recordId);
                return 'Hồ sơ: ' . ($record?->title ?? 'Không xác định');
            })
            ->color('info')
            ->disabled()
            ->visible(fn () => session()->has('selected_archival_id')),

            Tables\Actions\Action::make('resetHoSo')
                ->label('Chọn lại hồ sơ')
                ->requiresConfirmation()
                ->color('warning')
                ->action(function () {
                    session()->forget('selected_archive_record_id');
                })
                ->visible(fn () => session()->has('selected_archive_record_id')),

                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => static::canDelete(null)),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    private static function getSelectedOrganizationType(): ?string
    {
        $orgId = session('selected_archival_id');

        return $orgId ? Organization::find($orgId)?->type : null;
    }

    private static function isPartyOrganization(): bool
    {
        return static::getSelectedOrganizationType() === 'Đảng';
    }

    private static function getDocumentFieldLabels(): array
    {
        if (static::isPartyOrganization()) {
            return [
                'doc_type_id' => 'Tên loại văn bản',
                'document_code' => 'Số của văn bản / Ký hiệu của văn bản',
                'document_date' => 'Ngày, tháng, năm văn bản',
                'description' => 'Trích yếu nội dung',
                'signer' => 'Người ký',
                'author' => 'Tác giả',
                'page_number' => 'Trang số',
            ];
        }

        return [
            'doc_type_id' => 'Chọn loại hồ sơ',
            'document_code' => 'Số ký hiệu',
            'document_date' => 'Ngày tháng văn bản',
            'description' => 'Trích yếu nội dung văn bản',
            'signer' => 'Người ký',
            'author' => 'Tác giả',
            'page_number' => 'Tờ số',
        ];
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
                Tables\Columns\TextColumn::make('document_number')
                    ->label($black('Số của văn bản'))
                    ->formatStateUsing(fn ($state, $record) => $state ?: $record->document_code)
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_symbol')
                    ->label($black('Ký hiệu của văn bản'))
                    ->formatStateUsing(fn ($state, $record) => $state ?: $record->document_code)
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_code')
                    ->label($black('Số của văn bản / Ký hiệu của văn bản'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('document_date')
                    ->label($black('Ngày, tháng, năm văn bản'))
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('issuing_agency')
                    ->label($black('Tên cơ quan, tổ chức ban hành văn bản')),
                Tables\Columns\TextColumn::make('docType.name')
                    ->label($black('Tên loại văn bản')),
                Tables\Columns\TextColumn::make('description')
                    ->label($black('Trích yếu nội dung'))
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\TextColumn::make('signer')
                    ->label($black('Người ký'))
                    ->formatStateUsing(fn ($state, $record) => $state ?: $record->author),
                Tables\Columns\TextColumn::make('security_level')
                    ->label($black('Độ mật')),
                Tables\Columns\TextColumn::make('copy_type')
                    ->label($black('Loại bản')),
                Tables\Columns\TextColumn::make('page_number')
                    ->label($black('Trang số')),
                Tables\Columns\TextColumn::make('total_pages')
                    ->label($black('Số trang')),
                Tables\Columns\TextColumn::make('file_count')
                    ->label($black('Số lượng tệp (file)')),
                Tables\Columns\TextColumn::make('file_name')
                    ->label($black('Tên tệp')),
                Tables\Columns\TextColumn::make('document_duration')
                    ->label($black('Thời gian tài liệu')),
                Tables\Columns\TextColumn::make('usage_mode')
                    ->label($black('Chế độ sử dụng')),
                Tables\Columns\TextColumn::make('keywords')
                    ->label($black('Từ khóa')),
                Tables\Columns\TextColumn::make('archive_record.code')
                    ->label($black('Mã hồ sơ')),
                Tables\Columns\TextColumn::make('note')
                    ->label($black('Ghi chú')),
                Tables\Columns\TextColumn::make('language')
                    ->label($black('Ngôn ngữ')),
                Tables\Columns\TextColumn::make('handwritten')
                    ->label($black('Bút tích')),
                Tables\Columns\TextColumn::make('topic')
                    ->label($black('Chuyên đề')),
                Tables\Columns\TextColumn::make('information_code')
                    ->label($black('Ký hiệu thông tin')),
                Tables\Columns\TextColumn::make('reliability_level')
                    ->label($black('Mức độ tin cậy')),
                Tables\Columns\TextColumn::make('physical_condition')
                    ->label($black('Tình trạng vật lý')),
            ];
        }

        // Chính quyền giữ nguyên layout hiện có.
        return [
            Tables\Columns\TextColumn::make('document_code')
                ->label('Số ký hiệu')
                ->searchable(),
            Tables\Columns\TextColumn::make('document_date')
                ->label('Ngày tháng')
                ->date('d/m/Y'),
            Tables\Columns\TextColumn::make('description')
                ->label('Trích yếu')
                ->limit(50),
            Tables\Columns\TextColumn::make('archive_record.code')
                ->label('Mã hồ sơ'),
            Tables\Columns\TextColumn::make('docType.name')
                ->label('Loại tài liệu'),
            Tables\Columns\TextColumn::make('author')
                ->label('Tác giả')
                ->formatStateUsing(fn ($state, $record) => $state ?: $record->signer),
            Tables\Columns\TextColumn::make('page_number')
                ->label('Tờ số'),
            Tables\Columns\TextColumn::make('note')
                ->label('Ghi chú'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Ngày tạo')
                ->dateTime('d/m/Y H:i'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            //'create' => Pages\CreateDocument::route('/create'),
            //'edit' => Pages\EditDocument::route('/{record}/edit'),
            'import' => Pages\ImportDocuments::route('/import'),
        ];
    }
}
