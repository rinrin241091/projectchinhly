<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivalResource\Pages;
use App\Filament\Resources\ArchivalResource\RelationManagers;
use App\Models\Archival;
use App\Traits\RoleBasedPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class ArchivalResource extends Resource
{
    use RoleBasedPermissions;

    protected static ?string $model = Archival::class;

    protected static ?string $navigationGroup = 'Khai thác - Thống kê';  //Tạo gộp nhóm menu bên trái

    protected static ?string $navigationLabel = 'Đơn vị lưu trữ'; //Đổi tên text hiển thị

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return \App\Traits\RoleBasedPermissions::canCreate();
    }

    public static function canEdit($record): bool
    {
        return \App\Traits\RoleBasedPermissions::canEdit($record);
    }

    public static function canDelete($record): bool
    {
        return \App\Traits\RoleBasedPermissions::canDelete($record);
    }

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
                                    ->validationAttribute('mã cơ quan lưu trữ')
                                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                                    ->maxLength(100)
                                    ->rule('max:100')
                                    ->live(onBlur: true)
                                    ->unique(
                                        table: Archival::class,
                                        column: 'identifier',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule) => $rule->whereNotNull('identifier'),
                                    )
                                    ->helperText('Mã cơ quan lưu trữ phải là duy nhất.')
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Tên cơ quan lưu trữ')
                                    ->validationAttribute('tên cơ quan lưu trữ')
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                                    ->required(),
                                Forms\Components\TextInput::make('address')
                                    ->label('Địa chỉ')
                                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Số điện thoại')
                                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email liên hệ')
                                    ->validationAttribute('email liên hệ')
                                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                                    ->email()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('manager')
                                    ->label('Người phụ trách')
                                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                                    ->maxLength(100),

                            ])
                            
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                if (!$user) {
                    return $query->whereRaw('1 = 0');
                }

                if (in_array($user->role, ['admin', 'super_admin', 'teamlead'], true)) {
                    return $query;
                }

                $orgId = session('selected_archival_id');

                if (!$orgId || !$user->hasOrganization($orgId)) {
                    return $query->whereRaw('1 = 0');
                }

                $archivalId = \App\Models\Organization::find($orgId)?->archival_id;

                if (!$archivalId) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereKey($archivalId);
            })
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
