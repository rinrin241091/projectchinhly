<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShelveResource\Pages;
use App\Filament\Resources\ShelveResource\RelationManagers;
use App\Models\Shelf;
use App\Models\Archival;
use App\Models\Storage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShelveResource extends Resource
{
    protected static ?string $model = Shelf::class;

    protected static ?string $navigationLabel = 'Danh sách Kệ';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Mã Kệ/Tủ')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->label('Mô tả')
                    ->maxLength(255),
            
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(name:'code')
                    ->searchable()
                    ->sortable()
                    ->label('Mã kệ'),  //Hiển thị cột trong bảng
               
                Tables\Columns\TextColumn::make(name:'description')
                    ->searchable()
                    ->sortable()
                    ->label('Mô tả'),  
                
                Tables\Columns\TextColumn::make(name:'storage.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make(name:'archival.name')
                    ->label('Đơn vị lưu trữ')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
            
                Tables\Actions\CreateAction::make('bulkCreateShelves')
                    ->label('Tạo nhiều kệ')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn () => route('filament.dashboard.pages.bulk-create-shelves')), // Route đến page bạn đã tạo
            ])
            ->emptyStateActions([
                // Remove create action from empty state
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
            'index' => Pages\ListShelves::route('/'),
            'create' => Pages\CreateShelve::route('/create'),
            'edit' => Pages\EditShelve::route('/{record}/edit'),
        ];
    }    
}
