<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Thành viên dự án';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'teamlead' && (int) $ownerRecord->team_lead_id === (int) $user->id;
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Vai trò dự án')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'member' ? 'Thành viên' : ($state ?? 'Thành viên')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('addMember')
                    ->label('Thêm member')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Người dùng')
                            ->options(function (): array {
                                return User::query()
                                    ->where('role', '!=', 'admin')
                                    ->orderBy('name')
                                    ->get(['id', 'name', 'email'])
                                    ->mapWithKeys(fn (User $user) => [
                                        $user->id => "{$user->name} ({$user->email})",
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->getOwnerRecord()->members()->syncWithoutDetaching([
                            $data['user_id'] => ['role' => 'member'],
                        ]);

                        Notification::make()
                            ->title('Đã thêm thành viên vào dự án')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Xóa member')
                    ->visible(fn () => auth()->user()?->role === 'admin'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->visible(fn () => auth()->user()?->role === 'admin'),
            ]);
    }
}
