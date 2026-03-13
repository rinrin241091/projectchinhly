<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Organization;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

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
                        'editor' => 'Người chỉnh sửa',
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
                        'editor' => 'Người chỉnh sửa',
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
                        'editor' => 'Người chỉnh sửa',
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
                Tables\Actions\AttachAction::make()
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
                                'editor' => 'Người chỉnh sửa',
                                'viewer' => 'Người xem',
                            ])
                            ->required()
                            ->default('viewer'),
                    ])
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Sửa vai trò')
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),

                Tables\Actions\DetachAction::make()
                    ->label('Xóa')
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),
            ])

            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->visible(fn() => \App\Traits\RoleBasedPermissions::canManageMembers()),
            ]);
    }
}