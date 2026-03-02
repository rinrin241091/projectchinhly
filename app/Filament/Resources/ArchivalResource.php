<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivalResource\Pages;
use App\Filament\Resources\ArchivalResource\RelationManagers;
use App\Models\Archival;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArchivalResource extends Resource
{
    protected static ?string $model = Archival::class;

    protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái

    protected static ?string $navigationLabel = 'Đơn vị lưu trữ'; //Đổi tên text hiển thị

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('identifier')
                                    ->label('Mã cơ quan lưu trữ')
                                    ->maxLength(20)
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Tên cơ quan lưu trữ')
                                    ->maxLength(255)
                                    ->required(),
                                Forms\Components\TextInput::make('address')
                                    ->label('Địa chỉ')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Số điện thoại')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email liên hệ')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('manager')
                                    ->label('Người phụ trách')
                                    ->maxLength(100),

                            ])
                            
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('identifier')->label('Mã cơ quan lưu trữ')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Tên cơ quan lưu trữ')->searchable(),
                Tables\Columns\TextColumn::make('address')->label('Địa chỉ')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Số điện thoại')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Email liên hệ')->searchable(),
                Tables\Columns\TextColumn::make('manager')->label('Người phụ trách')->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListArchivals::route('/'),
            'create' => Pages\CreateArchival::route('/create'),
            'edit' => Pages\EditArchival::route('/{record}/edit'),
        ];
    }    
}
