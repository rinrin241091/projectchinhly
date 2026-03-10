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
    
    protected static ?string $navigationLabel = 'Quản lý phân quyền';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin cơ bản')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Tên người dùng')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Mật khẩu')
                            ->password()
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->hiddenOn('edit'),
                        Forms\Components\Select::make('role')
                            ->label('Vai trò toàn cục')
                            ->options([
                                'admin' => 'Admin',
                                'user' => 'User',
                                'input_data' => 'InputData',
                            ])
                            ->required()
                            ->default('user')
                            ->helperText('Quyền cấp cao nhất của người dùng trong hệ thống'),
                        Forms\Components\Toggle::make('active')
                            ->label('Kích hoạt')
                            ->helperText('Bật/tắt kích hoạt người dùng')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Tên người dùng')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('organizations.name')
                ->label('Phông làm việc')
                ->badge()
                ->separator(',')
                ->searchable()
                ->wrap(),

            Tables\Columns\TextColumn::make('organizations_count')
                ->label('Số phông')
                ->counts('organizations')
                ->sortable(),

            Tables\Columns\TextColumn::make('organizations')
                ->label('Vai trò')
                ->getStateUsing(function ($record) {
                    return $record->organizations->map(function ($org) {
                        return match ($org->pivot->role) {
                            'admin' => 'Quản trị viên',
                            'editor' => 'Người chỉnh sửa',
                            'viewer' => 'Người xem',
                            default => $org->pivot->role,
                        };
                    });
                })
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'Quản trị viên' => 'danger',
                    'Người chỉnh sửa' => 'warning',
                    'Người xem' => 'success',
                    default => 'gray',
                })
                ->listWithLineBreaks(),

            Tables\Columns\TextColumn::make('role')
                ->label('Vai trò toàn cục')
                ->badge()
                ->formatStateUsing(fn ($state) => match ($state) {
                    'admin' => 'Admin',
                    'user' => 'User',
                    'input_data' => 'Nhập dữ liệu',
                    default => $state,
                })
                ->color(fn ($state) => match ($state) {
                    'admin' => 'danger',
                    'user' => 'success',
                    'input_data' => 'warning',
                    default => 'gray',
                })
                ->sortable(),

            Tables\Columns\IconColumn::make('active')
                ->label('Trạng thái')
                ->boolean()
                ->trueColor('success')
                ->falseColor('danger')
                ->sortable(),
        ])

        ->modifyQueryUsing(fn (Builder $query) =>
            $query->with('organizations')
                  ->where('id', '!=', auth()->id() ?? 0)
        )

        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),

            Tables\Actions\Action::make('assignWorkspace')
                ->label('')
                ->icon('heroicon-o-plus')
                ->tooltip('Gán phông')
                ->form([
                    Forms\Components\Select::make('organization_id')
                        ->label('Phông làm việc')
                        ->relationship('organizations', 'name')
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('role')
                        ->label('Vai trò')
                        ->options([
                            'admin' => 'Quản trị viên',
                            'editor' => 'Người chỉnh sửa',
                            'viewer' => 'Người xem',
                        ])
                        ->required(),
                ])
                ->action(function (User $record, array $data) {
                    $record->organizations()->syncWithoutDetaching([
                        $data['organization_id'] => [
                            'role' => $data['role']
                        ],
                    ]);
                }),
        ]);
}

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrganizationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        $pages = [
            'index' => Pages\ManageUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
