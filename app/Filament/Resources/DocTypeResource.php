<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocTypeResource\Pages;
use App\Filament\Resources\DocTypeResource\RelationManagers;
use App\Models\DocType;
use App\Traits\RoleBasedPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class DocTypeResource extends Resource
{
    use RoleBasedPermissions;

    protected static ?string $model = DocType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Loại tài liệu'; //Đổi tên text hiển thị
    
    protected static ?string $pluralLabel = 'Loại tài liệu';
    
    protected static ?string $title = 'Loại tài liệu';
    
    protected static ?string $navigationGroup = 'Khai thác - Thống kê';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                  ->schema([
                        Forms\Components\Section::make([
                        
                            Forms\Components\TextInput::make(name:'name')
                            ->label(label: 'Tên loại văn bản')
                            ->required()
                          //  ->live(onBlur:true) //Hàm này gửi giá trị nhập trực tiếp đến server khi người dùng nhập
                          //  ->unique()
                            ->afterStateUpdated(function(string $operation, $state, Forms\Set $set)
                                { //chức năng tạo slug tự động
                                    if($operation != 'create'){
                                        return;
                                }
                                $set('slug', Str::slug($state));
                                }),

                            Forms\Components\MarkdownEditor::make(name:'description')
                            ->label(label: 'Mô tả')
                             ->columnSpan(span:'full')
                            

                        ]) ->columns(columns:2)
                    ]),
                ]);

                    
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(name:'name')
                    ->label('Tên loại văn bản')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make(name:'description')
                    ->label('Mô tả')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make(name:'created_at')
                    ->label('Ngày tạo')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => static::canEdit(null)),
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
            'index' => Pages\ListDocTypes::route('/'),
            'create' => Pages\CreateDocType::route('/create'),
            'edit' => Pages\EditDocType::route('/{record}/edit'),
        ];
    }    
}
