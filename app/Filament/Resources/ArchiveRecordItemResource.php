<?php

namespace App\Filament\Resources;

// Import các lớp cần thiết cho Resource
use App\Filament\Resources\ArchiveRecordItemResource\Pages;
use App\Filament\Resources\ArchiveRecordItemResource\RelationManagers;
use App\Models\ArchiveRecordItem;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// Lớp Resource cho quản lý ArchiveRecordItem trong Filament
class ArchiveRecordItemResource extends Resource
{
    // Model liên kết với Resource này
    protected static ?string $model = ArchiveRecordItem::class;

    // Slug cho URL của Resource
    protected static ?string $slug = 'archive-record-items';

    // Icon hiển thị trong navigation
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Nhãn hiển thị trong navigation
    protected static ?string $navigationLabel = 'Mục lục hồ sơ';

    // Nhãn số nhiều
    protected static ?string $pluralLabel = 'Mục lục hồ sơ';

    // Tiêu đề của Resource
    protected static ?string $title = 'Mục lục hồ sơ';

    // Nhóm navigation
    protected static ?string $navigationGroup = 'Nhập liệu - Biên mục';

    // Phương thức định nghĩa form cho tạo và chỉnh sửa bản ghi
    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            // Phần thông tin mục lục hồ sơ
            Forms\Components\Section::make('Thông tin mục lục hồ sơ')
                ->description('Vui lòng điền đầy đủ thông tin mục lục hồ sơ')
                ->schema([
                    // Chọn phông lưu trữ
                    Forms\Components\Select::make('organization_id')
                        ->label('Chọn phông lưu trữ')
                        ->options(Organization::pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->columnSpan(1),
                    // Mã mục lục
                    Forms\Components\TextInput::make('archive_record_item_code')
                        ->label('Mã mục lục')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    // Tên mục lục hồ sơ
                    Forms\Components\TextInput::make('title')
                        ->label('Tên mục lục hồ sơ')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    // Năm hồ sơ
                    Forms\Components\TextInput::make('document_date')
                        ->label('Năm hồ sơ')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(1),
                    // Thời hạn bảo quản
                    Forms\Components\Textarea::make('description')
                        ->label('Thời hạn bảo quản')
                        ->maxLength(65535)
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->collapsible()
                ,
        ]);
    }

    // Phương thức định nghĩa bảng hiển thị danh sách bản ghi
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Cột ID
                Tables\Columns\TextColumn::make('id')->sortable(),
                // Cột mã mục lục
                Tables\Columns\TextColumn::make('archive_record_item_code')->label('Mã mục lục')->searchable(),
                // Cột tên mục lục
                Tables\Columns\TextColumn::make('title')->label('Tên mục lục')->searchable(),
                // Cột phông
                Tables\Columns\TextColumn::make('organization.name')->label('Phông')->searchable(),
                // Cột năm hồ sơ
                Tables\Columns\TextColumn::make('document_date')->label('Năm hồ sơ'),
                // Cột ghi chú
                Tables\Columns\TextColumn::make('description')->label('Ghi chú'),

            ])
            ->actions([
                // Hành động chỉnh sửa
                Tables\Actions\EditAction::make(),
                // Hành động xóa
                Tables\Actions\DeleteAction::make(),
                // Hành động xem mục lục
                Tables\Actions\Action::make('viewRecords')
                    ->label('Xem mục lục')
                    ->url(fn ($record) => route('archive-record-items.view', ['id' => $record->id]))
                    ->extraAttributes(['target' => '_blank']),
            ])
            ->bulkActions([
                // Hành động xóa hàng loạt
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                // Hành động tạo mới
                Tables\Actions\CreateAction::make()->label('Thêm mục lục hồ sơ'),
            ])
            ->emptyStateActions([]);
    }

    // Phương thức trả về các quan hệ
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // Phương thức trả về các trang
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchiveRecordItems::route('/'),
            'create' => Pages\CreateArchiveRecordItem::route('/create'),
            'edit' => Pages\EditArchiveRecordItem::route('/{record}/edit'),
            'view-archival-record' => Pages\ViewArchivalRecord::route('/{record}/view-archival-record'),
        ];
    }
}
