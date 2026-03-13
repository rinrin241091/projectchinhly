<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Dự án';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'teamlead'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'teamlead' && (int) $record->team_lead_id === (int) $user->id;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin dự án')
                ->disabled(fn () => auth()->user()?->role !== 'admin')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Mã dự án')
                        ->maxLength(50)
                        ->unique(table: Project::class, column: 'code', ignoreRecord: true),

                    Forms\Components\TextInput::make('name')
                        ->label('Tên dự án')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('target_meters')
                        ->label('Khối lượng được giao (mét hồ sơ)')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->required()
                        ->default(0),

                    Forms\Components\Placeholder::make('target_pages_preview')
                        ->label('Tổng số tờ quy đổi')
                        ->content(function (callable $get, ?Project $record): string {
                            $meters = $get('target_meters');
                            if ($meters === null && $record) {
                                $meters = $record->target_meters;
                            }

                            $pages = max(0, (int) round(((float) ($meters ?? 0)) * 1000));

                            return number_format($pages, 0, ',', '.') . ' tờ';
                        }),

                    Forms\Components\Select::make('organizations')
                        ->label('Phông thuộc dự án')
                        ->relationship('organizations', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->required()
                        ->helperText('Một dự án có thể có nhiều phông.'),

                    Forms\Components\Select::make('team_lead_id')
                        ->label('Teamlead phụ trách')
                        ->options(function () {
                            return User::query()
                                ->where('role', 'teamlead')
                                ->orderBy('name')
                                ->get(['id', 'name', 'email'])
                                ->mapWithKeys(fn (User $user) => [
                                    $user->id => "{$user->name} ({$user->email})",
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Chọn người có vai trò Teamlead trong các phông của dự án.'),

                    Forms\Components\Textarea::make('description')
                        ->label('Mô tả')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
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

                if ($user->role === 'admin') {
                    return $query->with(['teamLead', 'organizations']);
                }

                if ($user->role !== 'teamlead') {
                    return $query->whereRaw('1 = 0');
                }

                return $query
                    ->with(['teamLead', 'organizations'])
                    ->where('team_lead_id', $user->id);
            })
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Mã dự án')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Tên dự án')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teamLead.name')
                    ->label('Teamlead')
                    ->default('-'),

                Tables\Columns\TextColumn::make('organizations.name')
                    ->label('Phông')
                    ->badge()
                    ->separator(',')
                    ->wrap(),

                Tables\Columns\TextColumn::make('target_meters')
                    ->label('Khối lượng (m)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Tiến độ')
                    ->html()
                    ->state(fn (Project $record): float => $record->progress_percent)
                    ->formatStateUsing(function (float $state, Project $record): HtmlString {
                        $percent = (int) round(max(0, min(100, $state)));
                        $completed = number_format($record->completed_pages, 0, ',', '.');
                        $target = number_format($record->target_pages, 0, ',', '.');

                        $html = "<div style=\"min-width:220px\">"
                            . "<div style=\"display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;color:#374151\">"
                            . "<span>{$completed}/{$target}</span><span>{$percent}%</span>"
                            . "</div>"
                            . "<div style=\"height:10px;background:#e5e7eb;border-radius:999px;overflow:hidden\">"
                            . "<div style=\"height:10px;width:{$percent}%;background:linear-gradient(90deg,#16a34a,#22c55e)\"></div>"
                            . "</div>"
                            . "</div>";

                        return new HtmlString($html);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->role === 'admin'),
                Tables\Actions\Action::make('manageMembers')
                    ->label('Thành viên')
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Project $record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(function (Project $record): bool {
                        $user = auth()->user();

                        return $user?->role === 'teamlead' && (int) $record->team_lead_id === (int) $user->id;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Project $record) => static::canDelete($record)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => static::canCreate()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->role === 'admin'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
