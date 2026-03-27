<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
                            ->options(function (): array {
                                $userRole = auth()->user()?->role;
                                $hasSuperAdmin = User::query()->where('role', 'super_admin')->exists();

                                if ($userRole === 'super_admin') {
                                    return [
                                        'super_admin' => 'Super Admin',
                                        'admin' => 'Admin',
                                        'teamlead' => 'Teamlead',
                                        'user' => 'User',
                                        'data_entry' => 'Nhân viên nhập liệu',
                                        'input_data' => 'InputData',
                                    ];
                                }

                                if ($userRole === 'admin' && ! $hasSuperAdmin) {
                                    return [
                                        'super_admin' => 'Super Admin',
                                        'admin' => 'Admin',
                                        'teamlead' => 'Teamlead',
                                        'user' => 'User',
                                        'data_entry' => 'Nhân viên nhập liệu',
                                        'input_data' => 'InputData',
                                    ];
                                }

                                return [
                                    'admin' => 'Admin',
                                    'teamlead' => 'Teamlead',
                                    'user' => 'User',
                                    'data_entry' => 'Nhân viên nhập liệu',
                                    'input_data' => 'InputData',
                                ];
                            })
                            ->required()
                            ->default('user')
                            ->helperText('Quyền cấp cao nhất của người dùng trong hệ thống'),
                        Forms\Components\Select::make('managedProjects')
                            ->label('Dự án phụ trách')
                            ->relationship('managedProjects', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->helperText('Gán ngay user vào một hoặc nhiều dự án để tiện phân công.'),
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
                            'teamlead' => 'Teamlead',
                            'editor' => 'Người chỉnh sửa',
                            'viewer' => 'Người xem',
                            default => $org->pivot->role,
                        };
                    });
                })
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'Teamlead' => 'info',
                    'Người chỉnh sửa' => 'warning',
                    'Người xem' => 'success',
                    default => 'gray',
                })
                ->listWithLineBreaks(),

            Tables\Columns\TextColumn::make('role')
                ->label('Vai trò toàn cục')
                ->badge()
                ->formatStateUsing(fn ($state) => match ($state) {
                    'super_admin' => 'Super Admin',
                    'admin' => 'Admin',
                    'teamlead' => 'Teamlead',
                    'user' => 'User',
                    'data_entry' => 'Nhân viên nhập liệu',
                    'input_data' => 'Nhập dữ liệu',
                    default => $state,
                })
                ->color(fn ($state) => match ($state) {
                    'super_admin' => 'primary',
                    'admin' => 'danger',
                    'teamlead' => 'info',
                    'user' => 'success',
                    'data_entry' => 'warning',
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

        ->modifyQueryUsing(function (Builder $query): Builder {
            $query->with('organizations')
                ->where('id', '!=', auth()->id() ?? 0);

            // Admin thường không được nhìn thấy tài khoản Super Admin.
            if (auth()->user()?->role === 'admin') {
                $query->where('role', '!=', 'super_admin');
            }

            return $query;
        })

        ->actions([
            Tables\Actions\EditAction::make()
                ->visible(fn (User $record): bool => auth()->user()?->role === 'super_admin' || $record->role !== 'super_admin'),
            Tables\Actions\DeleteAction::make()
                ->visible(fn (User $record): bool => auth()->user()?->role === 'super_admin' || $record->role !== 'super_admin'),

            Tables\Actions\Action::make('changePassword')
                ->label('Đổi mật khẩu')
                ->icon('heroicon-o-key')
                ->form([
                    Forms\Components\TextInput::make('password')
                        ->label('Mật khẩu mới')
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->maxLength(255)
                        ->confirmed()
                        ->helperText('Mật khẩu phải tối thiểu 6 ký tự'),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Xác nhận mật khẩu')
                        ->password()
                        ->required(),
                ])
                ->action(function (User $record, array $data) {
                    $record->update([
                        'password' => Hash::make($data['password']),
                    ]);

                    activity('Người dùng')
                        ->performedOn($record)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'action' => 'change_password',
                            'target_user_id' => $record->id,
                            'target_user_name' => $record->name,
                            'target_user_email' => $record->email,
                        ])
                        ->event('updated')
                        ->log('Admin đã đổi mật khẩu người dùng ' . $record->name);
                }),

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
                            'teamlead' => 'Teamlead',
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

                    $organizationName = Organization::query()->whereKey($data['organization_id'])->value('name');

                    activity('Người dùng')
                        ->performedOn($record)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'action' => 'assign_workspace',
                            'target_user_id' => $record->id,
                            'target_user_name' => $record->name,
                            'organization_id' => $data['organization_id'],
                            'organization_name' => $organizationName,
                            'workspace_role' => $data['role'],
                        ])
                        ->event('updated')
                        ->log('Admin đã gán phông cho người dùng ' . $record->name);
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

        // Restrict access to certain pages for limited global roles
        if (auth()->check() && in_array(auth()->user()->role, ['input_data', 'data_entry'], true)) {
            // Example: Remove access to specific pages
            // unset($pages['manage-users']);
        }

        return $pages;
    }
    
    public static function canViewAny(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['super_admin', 'admin'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['super_admin', 'admin'], true);
    }

    public static function canEdit(Model $record): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (auth()->user()->role === 'super_admin') {
            return true;
        }

        return $record->role !== 'super_admin';
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }
}
