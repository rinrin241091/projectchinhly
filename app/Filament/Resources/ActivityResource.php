<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\RelationManagers;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationLabel = 'Nhật kí hoạt động';

    private static array $subjectNameCache = [];

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canViewAny(): bool
{
    return auth()->check() && auth()->user()->role === 'admin';
}

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'subject']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('log_name')
                    ->label('Loại log')
                    ->formatStateUsing(function (?string $state, Activity $record): string {
                        return static::getSubjectTypeLabel($record) ?: (string) $state;
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Mô tả')
                    ->formatStateUsing(function (?string $state, Activity $record): string {
                        $description = trim((string) $state);
                        $subjectLabel = static::getSubjectNoun($record);
                        $subjectName = static::resolveSubjectDisplayName($record);

                        if ($subjectLabel !== null) {
                            $description = (string) preg_replace('/\bhồ sơ\b/ui', $subjectLabel, $description);
                        }

                        if (! empty($subjectName)) {
                            $pattern = $subjectLabel
                                ? '/(' . preg_quote($subjectLabel, '/') . ')(?:\s+.+)?$/u'
                                : '/(đã\s+\w+)(?:\s+.+)?$/u';

                            $replaced = preg_replace($pattern, '$1 ' . $subjectName, $description, 1);

                            if (is_string($replaced) && $replaced !== $description) {
                                return trim($replaced);
                            }

                            if (! str_contains($description, $subjectName)) {
                                return trim($description . ' ' . $subjectName);
                            }
                        }

                        return $description;
                    })
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người thực hiện')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('details')
                    ->label('Chi tiết')
                    ->state(function (Activity $record): string {
                        return static::formatDeviceDetails($record);
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Hành động')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(function ($state, Activity $record): string {
                        return static::getSubjectTypeLabel($record) ?: class_basename((string) $state);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->actions([])
            ->bulkActions([]);
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
            'index' => Pages\ListActivities::route('/'),
            // 'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }

    private static function getSubjectTypeLabel(Activity $record): ?string
    {
        return match ($record->subject_type) {
            \App\Models\User::class => 'Người dùng',
            \App\Models\ArchiveRecord::class => 'Hồ sơ lưu trữ',
            \App\Models\ArchiveRecordItem::class => 'Mục lục hồ sơ',
            \App\Models\Borrowing::class => 'Mượn trả hồ sơ',
            \App\Models\Box::class => 'Hộp lưu trữ',
            \App\Models\Shelf::class => 'Kệ lưu trữ',
            \App\Models\Storage::class => 'Kho lưu trữ',
            \App\Models\Document::class => 'Văn bản, tài liệu',
            \App\Models\DocType::class => 'Loại tài liệu',
            \App\Models\RecordType::class => 'Loại hồ sơ',
            \App\Models\Organization::class => 'Phông lưu trữ',
            \App\Models\Archival::class => 'Đơn vị lưu trữ',
            default => null,
        };
    }

    private static function getSubjectNoun(Activity $record): ?string
    {
        return match ($record->subject_type) {
            \App\Models\User::class => 'người dùng',
            \App\Models\ArchiveRecord::class => 'hồ sơ',
            \App\Models\ArchiveRecordItem::class => 'mục lục hồ sơ',
            \App\Models\Borrowing::class => 'mượn trả hồ sơ',
            \App\Models\Box::class => 'hộp',
            \App\Models\Shelf::class => 'kệ',
            \App\Models\Storage::class => 'kho',
            \App\Models\Document::class => 'văn bản',
            \App\Models\DocType::class => 'loại tài liệu',
            \App\Models\RecordType::class => 'loại hồ sơ',
            \App\Models\Organization::class => 'phông',
            \App\Models\Archival::class => 'đơn vị lưu trữ',
            default => null,
        };
    }

    private static function resolveSubjectDisplayName(Activity $record): ?string
    {
        $title = trim((string) (
            $record->subject?->title
            ?? static::getProperty($record, 'attributes.title')
            ?? static::getProperty($record, 'old.title')
            ?? ''
        ));

        $name = trim((string) (
            $record->subject?->name
            ?? static::getProperty($record, 'attributes.name')
            ?? static::getProperty($record, 'old.name')
            ?? ''
        ));

        $description = trim((string) (
            $record->subject?->description
            ?? static::getProperty($record, 'attributes.description')
            ?? static::getProperty($record, 'old.description')
            ?? ''
        ));

        $code = trim((string) (
            $record->subject?->code
            ?? $record->subject?->reference_code
            ?? static::getProperty($record, 'attributes.code')
            ?? static::getProperty($record, 'old.code')
            ?? static::getProperty($record, 'attributes.reference_code')
            ?? static::getProperty($record, 'old.reference_code')
            ?? ''
        ));

        if ($description !== '' && $code !== '') {
            return "{$description} ({$code})";
        }

        $historicalName = static::resolveHistoricSubjectName($record);

        if (! empty($historicalName)) {
            return $historicalName;
        }

        return $title !== ''
            ? $title
            : ($name !== ''
                ? $name
                : ($description !== ''
                    ? $description
                    : ($code !== '' ? $code : ($record->subject_id ? ('#' . $record->subject_id) : null))));
    }

    private static function resolveHistoricSubjectName(Activity $record): ?string
    {
        if (empty($record->subject_type) || empty($record->subject_id)) {
            return null;
        }

        $cacheKey = $record->subject_type . ':' . $record->subject_id;

        if (array_key_exists($cacheKey, static::$subjectNameCache)) {
            return static::$subjectNameCache[$cacheKey];
        }

        $relatedLogs = Activity::query()
            ->where('subject_type', $record->subject_type)
            ->where('subject_id', $record->subject_id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['properties']);

        foreach ($relatedLogs as $log) {
            $historyTitle = trim((string) (
                data_get($log->properties, 'attributes.title')
                ?? data_get($log->properties, 'old.title')
                ?? ''
            ));

            $historyName = trim((string) (
                data_get($log->properties, 'attributes.name')
                ?? data_get($log->properties, 'old.name')
                ?? ''
            ));

            $historyDescription = trim((string) (
                data_get($log->properties, 'attributes.description')
                ?? data_get($log->properties, 'old.description')
                ?? ''
            ));

            $historyCode = trim((string) (
                data_get($log->properties, 'attributes.code')
                ?? data_get($log->properties, 'old.code')
                ?? data_get($log->properties, 'attributes.reference_code')
                ?? data_get($log->properties, 'old.reference_code')
                ?? ''
            ));

            if ($historyDescription !== '' && $historyCode !== '') {
                return static::$subjectNameCache[$cacheKey] = "{$historyDescription} ({$historyCode})";
            }

            if ($historyTitle !== '') {
                return static::$subjectNameCache[$cacheKey] = $historyTitle;
            }

            if ($historyName !== '') {
                return static::$subjectNameCache[$cacheKey] = $historyName;
            }

            if ($historyDescription !== '') {
                return static::$subjectNameCache[$cacheKey] = $historyDescription;
            }

            if ($historyCode !== '') {
                return static::$subjectNameCache[$cacheKey] = $historyCode;
            }
        }

        return static::$subjectNameCache[$cacheKey] = null;
    }

    private static function formatDeviceDetails(Activity $record): string
    {
        $ip = static::readDetailValue($record, ['ip_address', 'ip']);
        $mac = static::readDetailValue($record, ['mac_address', 'mac', 'device_mac']);
        $os = static::readDetailValue($record, ['os', 'operating_system', 'platform']);
        $deviceType = static::readDetailValue($record, ['device_type', 'device']);
        $browser = static::readDetailValue($record, ['browser']);

        $parts = [];

        if ($ip) {
            $parts[] = 'IP: ' . $ip;
        }

        if ($mac && strtoupper($mac) !== 'N/A') {
            $parts[] = 'MAC: ' . $mac;
        }

        if ($os && $os !== 'Unknown') {
            $parts[] = 'OS: ' . $os;
        }

        if ($deviceType && $deviceType !== 'Unknown') {
            $parts[] = 'Thiết bị: ' . $deviceType;
        }

        if ($browser && $browser !== 'Unknown') {
            $parts[] = 'Trình duyệt: ' . $browser;
        }

        return empty($parts) ? 'Chưa có dữ liệu thiết bị' : implode(' | ', $parts);
    }

    private static function readDetailValue(Activity $record, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) (
                static::getProperty($record, $key)
                ?? static::getProperty($record, 'device.' . $key)
                ?? ''
            ));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function getProperty(Activity $record, string $path): mixed
    {
        return data_get($record->properties, $path);
    }
}
