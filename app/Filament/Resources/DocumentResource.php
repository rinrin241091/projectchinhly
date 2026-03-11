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
                        $organizationId = $record->archive_record?->organization?->id ?? session('selected_archival_id');
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
                    ->label('Chọn loại hồ sơ')
                    ->relationship('docType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('document_code')
                    ->label('Số ký hiệu')
                    ->required()
                    ->live()
                    ->unique(table: \App\Models\Document::class, column: 'document_code', ignoreRecord: true),

                Forms\Components\DatePicker::make('document_date')
                    ->label('Ngày tháng văn bản'),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Trích yếu nội dung văn bản')
                    ->rows(3),

                Forms\Components\TextInput::make('author')
                    ->label('Tác giả'),
                
                Forms\Components\TextInput::make('page_number')
                    ->label('Tờ số'),
                
                Forms\Components\Textarea::make('note')
                    ->label('Ghi chú')
                    ->rows(2),
                
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

            ->columns([
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
                    ->label('Tác giả'),
                Tables\Columns\TextColumn::make('page_number')
                    ->label('Tờ số'),
                Tables\Columns\TextColumn::make('note')
                    ->label('Ghi chú'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i'),
            ])
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
