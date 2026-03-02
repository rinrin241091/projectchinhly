<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StorageResource\Pages;
use App\Filament\Resources\StorageResource\RelationManagers;
use App\Models\Storage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StorageResource extends Resource
{
    protected static ?string $model = Storage::class;
    protected static ?string $navigationLabel = 'Danh sách Kho';

    // protected static ?int $navigationSort = 0;

    protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('archival_id')
                            ->label('Đơn vị lưu trữ')
                            ->relationship('archival', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Chọn đơn vị lưu trữ'),

                        Forms\Components\TextInput::make('code')
                            ->label('Mã kho')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('name')
                            ->label('Tên kho')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('location')
                            ->label('Vị trí')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->extraAttributes(['class' => 'w-1/2']),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('code')->label('Mã kho')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Tên kho')->searchable(),
                Tables\Columns\TextColumn::make('location')->label('Mô tả vị trí')->searchable(),
                Tables\Columns\TextColumn::make(name:'archival.name')
                    ->label('Đơn vị lưu trữ')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tạo mới')
                    ->modalHeading('Thêm mới Kho')
                    ->modalWidth('lg')
                    ->slideOver()
                    ->button(),
            ])
            ->emptyStateActions([
                // Remove the create action from empty state
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
            'index' => Pages\ListStorages::route('/'),
            'create' => Pages\CreateStorage::route('/create'),
            'edit' => Pages\EditStorage::route('/{record}/edit'),
        ];
    }
}
