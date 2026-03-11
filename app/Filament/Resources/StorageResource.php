<?php

namespace App\Filament\Resources;

use App\Traits\RoleBasedPermissions;
use App\Filament\Resources\StorageResource\Pages;
use App\Models\Storage;
use App\Models\Organization;
use App\Models\Archival;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StorageResource extends Resource
{
    use RoleBasedPermissions;

    protected static ?string $model = Storage::class;

    protected static ?string $navigationLabel = 'Danh sách Kho';

    protected static ?string $navigationGroup = 'Khai thác - Thống kê';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([

                Forms\Components\Card::make()
                    ->schema([

                        Forms\Components\Select::make('archival_id')
                            ->label('Đơn vị lưu trữ')
                            ->options(function () {

                                $user = auth()->user();

                                // ADMIN: thấy tất cả cơ quan
                                if ($user->role === 'admin') {
                                    return Archival::pluck('name', 'id');
                                }

                                // NON-ADMIN: lấy cơ quan theo phông đã chọn
                                $orgId = session('selected_archival_id');

                                if ($orgId && $user->hasOrganization($orgId)) {

                                    $organization = Organization::with('archival')->find($orgId);

                                    if ($organization && $organization->archival) {
                                        return [
                                            $organization->archival->id => $organization->archival->name
                                        ];
                                    }
                                }

                                return [];
                            })
                            ->default(function () {

                                $user = auth()->user();

                                $orgId = session('selected_archival_id');

                                if ($orgId && $user && $user->hasOrganization($orgId)) {
                                    return Organization::find($orgId)?->archival_id;
                                }

                                return null;
                            })
                            ->disabled(fn () => auth()->user()->role !== 'admin')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('code')
                            ->label('Mã kho')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('name')
                            ->label('Tên kho')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('location')
                            ->label('Vị trí')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),

                    ])
                    ->extraAttributes(['class' => 'w-1/2']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->modifyQueryUsing(function (Builder $query) {

                $user = auth()->user();

                // ADMIN: không filter
                if ($user->role === 'admin') {
                    return $query;
                }

                // NON-ADMIN: filter theo phông đã chọn
                $orgId = session('selected_archival_id');

                if ($orgId && $user->hasOrganization($orgId)) {

                    $organization = Organization::find($orgId);

                    if ($organization) {
                        return $query->where('archival_id', $organization->archival_id);
                    }
                }

                return $query->whereRaw('1 = 0');
            })

            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Mã kho')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Tên kho')
                    ->searchable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Mô tả vị trí')
                    ->searchable(),

                Tables\Columns\TextColumn::make('archival.name')
                    ->label('Đơn vị lưu trữ')
                    ->sortable()
                    ->searchable(),

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

            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tạo mới')
                    ->modalHeading('Thêm mới Kho')
                    ->modalWidth('lg')
                    ->slideOver()
                    ->button()
                    ->visible(fn () => static::canCreateStorage()),
            ])

            ->emptyStateActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStorages::route('/'),
            'create' => Pages\CreateStorage::route('/create'),
            'edit' => Pages\EditStorage::route('/{record}/edit'),
        ];
    }
}