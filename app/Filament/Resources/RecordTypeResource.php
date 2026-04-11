<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordTypeResource\Pages;
use App\Filament\Resources\RecordTypeResource\RelationManagers;
use App\Models\RecordType;
use App\Traits\RoleBasedPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecordTypeResource extends Resource
{
    use RoleBasedPermissions;

    protected static ?string $model = RecordType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Loại hồ sơ'; //Đổi tên text hiển thị
    protected static ?string $navigationGroup = 'Khai thác - Thống kê';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make([
                            Forms\Components\TextInput::make('code')
                                ->label('Mã loại')
                                ->required(),
                            Forms\Components\TextInput::make('name')
                                ->label('Tên loại hồ sơ')
                                ->required(),
                            Forms\Components\TextInput::make('description')
                                ->label('Mô tả (nếu có)'),

                        ])
                    ])
                
            ]);
    }

    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'super_admin', 'teamlead'], true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(name:'code')
                    ->searchable()
                    ->sortable()
                    ->label('Mã loại'), //Hiển thị cột trong bảng
               
                Tables\Columns\TextColumn::make(name:'name')
                    ->searchable()
                    ->sortable()
                    ->label('Tên loại'), 
                Tables\Columns\TextColumn::make(name:'description')
                    ->searchable()
                    ->sortable()
                    ->label('Mô tả (nếu có)'), 
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => static::canEdit(null)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => static::canDelete(null)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => static::canDelete(null)),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => static::canCreate()),
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
            'index' => Pages\ListRecordTypes::route('/'),
            'create' => Pages\CreateRecordType::route('/create'),
            'edit' => Pages\EditRecordType::route('/{record}/edit'),
        ];
    }    
}
