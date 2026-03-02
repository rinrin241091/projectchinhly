<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Filament\Resources\OrganizationResource\RelationManagers;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái //Tạo gộp nhóm menu bên trái

    protected static ?string $navigationLabel = 'Phông lưu trữ'; //Đổi tên text hiển thị
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('code')->required(),
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\Select::make('archival_id')
                                    ->relationship('archival', 'name')->label('Cơ quan lưu trữ'),
                                Forms\Components\TextInput::make('start_year')
                                    ->label('Từ năm')
                                    ->numeric()
                                    ->minValue(1000)
                                    ->maxValue(date('Y'))
                                    ->required()
                                    ->dehydrated(true)
                                    ->reactive(),
                                Forms\Components\TextInput::make('end_year')
                                    ->label('Đến năm')
                                    ->numeric()
                                    ->minValue(1000)
                                    ->maxValue(date('Y'))
                                    ->required()
                                    ->dehydrated(true)
                                    ->reactive(),
                                Forms\Components\Select::make('type')
                                    ->label('Loại phông')
                                    ->options([
                                        'Đảng' => 'Phông Đảng',
                                        'Chính quyền' => 'Phông Chính quyền',
                                    ])
                                    ->required(),
                                Forms\Components\TagsInput::make('key_groups')
                                    ->label('Các nhóm tài liệu chủ yếu ')
                                    ->placeholder('Nhập tên nhóm rồi ấn Enter'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable()->toggleable(isToggledHiddenByDefault: false)->extraAttributes(['class' => 'border-r']),
                Tables\Columns\TextColumn::make('code')->label('Mã phông')->searchable()->extraAttributes(['class' => 'border-r']),
                Tables\Columns\TextColumn::make('name')->label('Tên phông')->searchable()->extraAttributes(['class' => 'border-r']),
                Tables\Columns\TextColumn::make('archivals_time')->label('Thời gian hồ sơ')->searchable()->extraAttributes(['class' => 'border-r']),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Ngày tạo')->extraAttributes(['class' => 'border-r']),
                Tables\Columns\TextColumn::make('key_groups')->label('Nhóm hồ sơ'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tạo mới')->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }    
}
