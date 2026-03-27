<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BorrowingResource\Pages;
use App\Models\ArchiveRecord;
use App\Models\Borrowing;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Traits\RoleBasedPermissions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BorrowingResource extends Resource
{
    use RoleBasedPermissions;

    protected static ?string $model = Borrowing::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Mượn trả hồ sơ';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 99;

    private static function isAdmin(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    private static function isManager(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'teamlead'], true);
    }

    private static function isHandlingTeamMember(int $userId, int $organizationId): bool
    {
        return Project::query()
            ->whereIn('id', \DB::table('project_organization')->where('organization_id', $organizationId)->select('project_id'))
            ->where(function (Builder $query) use ($userId) {
                $query
                    ->where('team_lead_id', $userId)
                    ->orWhereIn('id', \DB::table('project_user')->where('user_id', $userId)->select('project_id'));
            })
            ->exists();
    }

    private static function resolveDueDate(Borrowing $record): ?Carbon
    {
        if ($record->due_at) {
            return Carbon::parse($record->due_at);
        }

        if ($record->borrowed_at) {
            return Carbon::parse($record->borrowed_at)->addDays(30);
        }

        return null;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return false;
        }

        $orgId = session('selected_archival_id');

        return $orgId !== null && $user->hasOrganization((int) $orgId);
    }

    public static function canEdit($record): bool
    {
        return static::isAdmin();
    }

    public static function canDelete($record): bool
    {
        return static::isAdmin();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (static::isAdmin()) {
            $count = Borrowing::query()
                ->where('approval_status', 'pending')
                ->count();

            return $count > 0 ? (string) $count : null;
        }

        $count = static::getEloquentQuery()
            ->where('approval_status', 'approved')
            ->whereNull('returned_at')
            ->whereDate('due_at', '<', now()->toDateString())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::isAdmin() ? 'danger' : 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['archiveRecord.organization', 'user']);

        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->role === 'admin') {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('archive_record_id')
                    ->label('Hồ sơ mượn')
                    ->relationship(
                        name: 'archiveRecord',
                        titleAttribute: 'title',
                        modifyQueryUsing: function (Builder $query) {
                            if (! auth()->check()) {
                                $query->whereRaw('1 = 0');
                            }
                        }
                    )
                    ->getOptionLabelFromRecordUsing(function (ArchiveRecord $record): string {
                        $code = $record->reference_code ?: $record->code;
                        return trim(($code ? "{$code} - " : '') . $record->title);
                    })
                    ->searchable(['reference_code', 'code', 'title'])
                    ->preload()
                    ->required()
                    ->rules([
                        function (?Model $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($record): void {
                                if (! $value) {
                                    return;
                                }

                                $existsOpenBorrowing = Borrowing::query()
                                    ->where('archive_record_id', $value)
                                    ->where('approval_status', 'approved')
                                    ->whereNull('returned_at')
                                    ->when($record?->id, fn (Builder $query) => $query->whereKeyNot($record->id))
                                    ->exists();

                                if ($existsOpenBorrowing) {
                                    $fail('Hồ sơ này đang được mượn và chưa trả.');
                                }

                                $user = auth()->user();
                                if (! $user || $user->role === 'admin') {
                                    return;
                                }

                                $archiveRecord = ArchiveRecord::query()->find($value);
                                if (! $archiveRecord) {
                                    return;
                                }

                                if (static::isHandlingTeamMember($user->id, (int) $archiveRecord->organization_id)) {
                                    $fail('Bạn thuộc team xử lý hồ sơ này, không được tạo phiếu mượn ngoài thẩm quyền.');
                                }
                            };
                        },
                    ]),

                Forms\Components\Select::make('user_id')
                    ->label('Người mượn')
                    ->options(function () {
                        return User::query()
                            ->where('active', true)
                            ->orderBy('name')
                            ->get(['id', 'name', 'email'])
                            ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} ({$user->email})"])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn (): bool => static::isAdmin()),

                Forms\Components\Placeholder::make('borrower_preview')
                    ->label('Người mượn')
                    ->content(fn (): string => auth()->user() ? (auth()->user()->name . ' (' . auth()->user()->email . ')') : '-')
                    ->visible(fn (): bool => ! static::isAdmin()),

                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(fn (): bool => ! static::isAdmin())
                    ->visible(fn (): bool => ! static::isAdmin()),

                Forms\Components\DatePicker::make('borrowed_at')
                    ->label('Ngày mượn')
                    ->native()
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->default(now()->toDateString())
                    ->required(),

                Forms\Components\Select::make('duration_preset')
                    ->label('Thời hạn mượn')
                    ->options([
                        '7' => '1 tuần',
                        '30' => '1 tháng',
                        '90' => '3 tháng',
                        'custom' => 'Tự chọn hạn',
                    ])
                    ->default('7')
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                        if ($state === 'custom') {
                            return;
                        }

                        $borrowedAt = $get('borrowed_at') ?: now()->toDateString();
                        $set('due_at', Carbon::parse($borrowedAt)->addDays((int) $state)->toDateString());
                    }),

                Forms\Components\DatePicker::make('due_at')
                    ->label('Hạn trả')
                    ->native()
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->required()
                    ->helperText('Hệ thống sẽ cảnh báo khi quá hạn để quản lý theo dõi và hoàn lại hồ sơ.')
                    ->afterStateHydrated(function ($state, callable $set, callable $get): void {
                        if ($state) {
                            return;
                        }

                        $borrowedAt = $get('borrowed_at') ?: now()->toDateString();
                        $set('due_at', Carbon::parse($borrowedAt)->addDays(7)->toDateString());
                    }),

                Forms\Components\DatePicker::make('returned_at')
                    ->label('Ngày trả')
                    ->native()
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->helperText('Để trống nếu hồ sơ chưa trả.')
                    ->visible(fn (): bool => static::isAdmin()),

                Forms\Components\Textarea::make('purpose')
                    ->label('Mục đích mượn')
                    ->rows(3)
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('approval_note')
                    ->label('Ghi chú duyệt')
                    ->rows(2)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->visible(fn (): bool => static::isAdmin()),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll(static::isAdmin() ? '5s' : null)
            ->modifyQueryUsing(fn (Builder $query) => $query->latest('id'))
            ->columns([
                Tables\Columns\TextColumn::make('archiveRecord.reference_code')
                    ->label('Mã hồ sơ')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereIn('archive_record_id', ArchiveRecord::where('reference_code', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->select('id')->toBase());
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('archiveRecord.title')
                    ->label('Tên hồ sơ')
                    ->searchable()
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('archiveRecord.organization.name')
                    ->label('Phông lưu trữ')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người mượn')
                    ->searchable(),

                Tables\Columns\TextColumn::make('borrowed_at')
                    ->label('Ngày mượn')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('returned_at')
                    ->label('Ngày trả')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('return_requested_at')
                    ->label('User yêu cầu trả')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Hạn trả')
                    ->formatStateUsing(function ($state, Borrowing $record): string {
                        $dueDate = static::resolveDueDate($record);
                        return $dueDate ? $dueDate->format('d/m/Y') : '-';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Admin duyệt')
                    ->default('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->state(function (Borrowing $record): string {
                        if ($record->approval_status === 'pending') {
                            return 'Chờ duyệt';
                        }

                        if ($record->approval_status === 'rejected') {
                            return 'Từ chối';
                        }

                        if ($record->returned_at) {
                            return 'Đã trả';
                        }

                        if ($record->return_requested_at) {
                            return 'Chờ admin xác nhận trả';
                        }

                        $dueDate = static::resolveDueDate($record);

                        if ($dueDate && $dueDate->lt(now()->startOfDay())) {
                            return 'Quá hạn';
                        }

                        return 'Đang mượn';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Chờ duyệt' => 'gray',
                        'Từ chối' => 'danger',
                        'Chờ admin xác nhận trả' => 'info',
                        'Đã trả' => 'success',
                        'Quá hạn' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'cho_duyet' => 'Chờ duyệt',
                        'tu_choi' => 'Từ chối',
                        'cho_xac_nhan_tra' => 'Chờ admin xác nhận trả',
                        'dang_muon' => 'Đang mượn',
                        'qua_han' => 'Quá hạn',
                        'da_tra' => 'Đã trả',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'cho_duyet' => $query->where('approval_status', 'pending'),
                            'tu_choi' => $query->where('approval_status', 'rejected'),
                            'cho_xac_nhan_tra' => $query
                                ->where('approval_status', 'approved')
                                ->whereNull('returned_at')
                                ->whereNotNull('return_requested_at'),
                            'dang_muon' => $query
                                ->where('approval_status', 'approved')
                                ->whereNull('returned_at')
                                ->whereNull('return_requested_at')
                                ->where(function (Builder $q) {
                                    $q->whereNull('due_at')
                                        ->orWhereDate('due_at', '>=', now()->toDateString());
                                }),
                            'qua_han' => $query
                                ->where('approval_status', 'approved')
                                ->whereNull('returned_at')
                                ->whereNull('return_requested_at')
                                ->whereDate('due_at', '<', now()->toDateString()),
                            'da_tra' => $query->where('approval_status', 'approved')->whereNotNull('returned_at'),
                            default => $query,
                        };
                    }),

                Filter::make('borrowed_date_range')
                    ->label('Thời gian mượn')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Từ ngày'),
                        Forms\Components\DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('borrowed_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('borrowed_at', '<=', $date));
                    }),

                SelectFilter::make('organization')
                    ->label('Phông lưu trữ')
                    ->options(function () {
                        return Organization::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->visible(fn (): bool => static::isManager())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when($value, function (Builder $q) use ($value) {
                            $q->whereIn('archive_record_id', ArchiveRecord::where('organization_id', $value)->select('id')->toBase());
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('requestReturn')
                    ->label('Trả hồ sơ')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(function (Borrowing $record): bool {
                        $user = auth()->user();

                        if (! $user || static::isAdmin()) {
                            return false;
                        }

                        return (int) $record->user_id === (int) $user->id
                            && $record->approval_status === 'approved'
                            && $record->returned_at === null
                            && $record->return_requested_at === null;
                    })
                    ->requiresConfirmation()
                    ->modalDescription('Sau khi gửi yêu cầu trả, quản lý sẽ xác nhận hoàn trả hồ sơ.')
                    ->action(function (Borrowing $record): void {
                        $record->update([
                            'return_requested_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Đã gửi yêu cầu trả hồ sơ')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('approveBorrowing')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Borrowing $record): bool => static::isAdmin() && $record->approval_status === 'pending')
                    ->requiresConfirmation()
                    ->successRedirectUrl(static::getUrl('index'))
                    ->action(function (Borrowing $record): void {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_note' => null,
                        ]);

                        Notification::make()
                            ->title('Đã duyệt phiếu mượn')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('rejectBorrowing')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Borrowing $record): bool => static::isAdmin() && $record->approval_status === 'pending')
                    ->successRedirectUrl(static::getUrl('index'))
                    ->form([
                        Forms\Components\Textarea::make('approval_note')
                            ->label('Lý do từ chối')
                            ->rows(3)
                            ->maxLength(255),
                    ])
                    ->action(function (Borrowing $record, array $data): void {
                        $record->update([
                            'approval_status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_note' => $data['approval_note'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Đã từ chối phiếu mượn')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('markReturned')
                    ->label('Xác nhận trả')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Borrowing $record): bool => static::isAdmin() && $record->approval_status === 'approved' && $record->returned_at === null && $record->return_requested_at !== null)
                    ->requiresConfirmation()
                    ->successRedirectUrl(static::getUrl('index'))
                    ->action(function (Borrowing $record): void {
                        $record->update([
                            'returned_at' => now()->toDateString(),
                            'return_requested_at' => null,
                        ]);

                        Notification::make()
                            ->title('Đã cập nhật trả hồ sơ')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => static::isAdmin()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => static::isAdmin()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => static::canCreate()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => static::isAdmin()),
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
        return [
            'index' => Pages\ListBorrowings::route('/'),
            'create' => Pages\CreateBorrowing::route('/create'),
            'edit' => Pages\EditBorrowing::route('/{record}/edit'),
        ];
    }
}
