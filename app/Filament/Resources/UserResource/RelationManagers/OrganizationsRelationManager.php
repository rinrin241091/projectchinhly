<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Organization;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrganizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'organizations';

    protected static ?string $title = 'Phòng';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role')
                    ->label('Vai trò trong phông')
                    ->options([
                        'teamlead' => 'Teamlead',
                        'editor' => 'Nhập liệu',
                        'viewer' => 'Người xem',
                    ])
                    ->required()
                    ->default('viewer'),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Phòng làm việc')
                    ->searchable(),

                BadgeColumn::make('role')
                    ->label('Vai trò')
                    ->getStateUsing(fn ($record) => $record->pivot?->role ?? 'viewer')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'teamlead' => 'Teamlead',
                        'editor', 'input_data' => 'Nhập liệu',
                        'viewer' => 'Người xem',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'teamlead',
                        'warning' => 'editor',
                        'success' => 'viewer',
                    ]),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'teamlead' => 'Teamlead',
                        'editor' => 'Nhập liệu',
                        'viewer' => 'Người xem',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['role'])) {
                            return $query->wherePivot('role', $data['role']);
                        }
                        return $query;
                    }),
            ])

            ->headerActions([
                Tables\Actions\Action::make('attachOrganization')
                    ->label('Thêm phông')
                    ->form([
                        Forms\Components\Select::make('recordId')
                            ->label('Phòng làm việc')
                            ->options(Organization::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('role')
                            ->label('Vai trò')
                            ->options([
                                'teamlead' => 'Teamlead',
                                'editor' => 'Nhập liệu',
                                'viewer' => 'Người xem',
                            ])
                            ->required()
                            ->default('viewer'),
                    ])
                    ->action(function (array $data): void {
                        /** @var User $user */
                        $user = $this->getOwnerRecord();

                        $user->organizations()->syncWithoutDetaching([
                            $data['recordId'] => ['role' => $data['role']],
                        ]);

                        $organizationName = Organization::query()->whereKey($data['recordId'])->value('name');

                        activity('Người dùng')
                            ->performedOn($user)
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'action' => 'attach_organization',
                                'organization_id' => $data['recordId'],
                                'organization_name' => $organizationName,
                                'workspace_role' => $data['role'],
                            ])
                            ->event('updated')
                            ->log('Admin đã thêm phông ' . $organizationName . ' cho người dùng ' . $user->name);
                    })
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),
            ])

            ->actions([
                Tables\Actions\Action::make('editRole')
                    ->label('Sửa vai trò')
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Vai trò trong phông')
                            ->options([
                                'teamlead' => 'Teamlead',
                                'editor' => 'Nhập liệu',
                                'viewer' => 'Người xem',
                            ])
                            ->required(),
                    ])
                    ->fillForm(fn ($record): array => [
                        'role' => $record->pivot?->role ?? 'viewer',
                    ])
                    ->action(function ($record, array $data): void {
                        /** @var User $user */
                        $user = $this->getOwnerRecord();

                        $oldRole = (string) ($record->pivot?->role ?? 'viewer');
                        $user->organizations()->updateExistingPivot($record->id, ['role' => $data['role']]);

                        activity('Người dùng')
                            ->performedOn($user)
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'action' => 'update_organization_role',
                                'organization_id' => $record->id,
                                'organization_name' => $record->name,
                                'old_role' => $oldRole,
                                'new_role' => $data['role'],
                            ])
                            ->event('updated')
                            ->log('Admin đã cập nhật vai trò phông ' . $record->name . ' cho người dùng ' . $user->name);
                    })
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),

                Tables\Actions\Action::make('detachOrganization')
                    ->label('Xóa')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        /** @var User $user */
                        $user = $this->getOwnerRecord();

                        $user->organizations()->detach($record->id);

                        activity('Người dùng')
                            ->performedOn($user)
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'action' => 'detach_organization',
                                'organization_id' => $record->id,
                                'organization_name' => $record->name,
                            ])
                            ->event('updated')
                            ->log('Admin đã xóa phông ' . $record->name . ' khỏi người dùng ' . $user->name);
                    })
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),
            ])

            ->bulkActions([
                Tables\Actions\BulkAction::make('detachSelectedOrganizations')
                    ->label('Xóa phông đã chọn')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        /** @var User $user */
                        $user = $this->getOwnerRecord();

                        $organizationIds = $records->pluck('id')->all();
                        $organizationNames = $records->pluck('name')->values()->all();

                        $user->organizations()->detach($organizationIds);

                        activity('Người dùng')
                            ->performedOn($user)
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'action' => 'detach_organizations_bulk',
                                'organization_ids' => $organizationIds,
                                'organization_names' => $organizationNames,
                            ])
                            ->event('updated')
                            ->log('Admin đã xóa nhiều phông khỏi người dùng ' . $user->name);
                    })
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),
            ]);
    }
}