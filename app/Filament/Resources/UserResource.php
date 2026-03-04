<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Quản lý hệ thống';
    
    protected static ?string $navigationLabel = 'Quản lý người dùng';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->hiddenOn('edit'),
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'user' => 'User',
                        'input_data' => 'InputData',
                    ])
                    ->required()
                    ->default('user'),
                Forms\Components\MultiSelect::make('organizations')
                    ->label('Phông được phép truy cập')
                    ->relationship('organizations', 'name')
                    ->preload()
                    ->helperText('Chỉ người dùng có quyền với phông này mới chọn được')
                    ->hiddenOn('create'),
                Forms\Components\Toggle::make('active')
                    ->label('Kích hoạt')
                    ->helperText('Bật/tắt kích hoạt người dùng')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'success',
                        'input_data' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('organizations.name')
                    ->label('Phông')
                    ->wrap()
                    ->limit(30),
                Tables\Columns\IconColumn::make('active')
                    ->label('Trạng thái')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'user' => 'User',
                    ]),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Trạng thái kích hoạt')
                    ->placeholder('Tất cả')
                    ->trueLabel('Đang kích hoạt')
                    ->falseLabel('Đã tắt'),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->where('id', '!=', auth()->id()))
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('changePassword')
                    ->label('Đổi mật khẩu')
                    ->icon('heroicon-o-key')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->password()
                            ->label('Mật khẩu mới')
                            ->required()
                            ->minLength(8),
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->password()
                            ->label('Xác nhận mật khẩu mới')
                            ->required()
                            ->same('new_password')
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'password' => Hash::make($data['new_password']),
                        ]);
                    })
                    ->modalHeading('Đổi mật khẩu'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
        $pages = [
            'index' => Pages\ManageUsers::route('/'),
        ];

        // Restrict access to certain pages for input_data role
        if (auth()->check() && auth()->user()->role === 'input_data') {
            // Example: Remove access to specific pages
            // unset($pages['manage-users']);
        }

        return $pages;
    }
    
    public static function canViewAny(): bool
    {
        return auth()->check() && (auth()->user()->role === 'admin');
    }
}
