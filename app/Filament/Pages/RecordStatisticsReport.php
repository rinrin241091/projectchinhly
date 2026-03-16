<?php

namespace App\Filament\Pages;

use App\Models\ArchiveRecord;
use App\Models\ArchiveRecordItem;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordStatisticsReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Báo cáo thống kê hồ sơ';

    protected static ?string $title = 'Báo cáo thống kê hồ sơ';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.record-statistics-report';

    public ?array $data = [];

    public ?array $appliedFilters = [];

    /** @var array<int, array>|null */
    public ?array $reportRows = null;

    public int $totalRecords = 0;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'teamlead'], true);
    }

    public function mount(): void
    {
        $this->form->fill([
            'mode' => 'year',
        ]);

        $this->refreshReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('mode')
                    ->label('Các tiêu chí')
                    ->options([
                        'year' => 'Hồ sơ theo năm',
                        'item' => 'Hồ sơ theo mục lục',
                        'preservation_duration' => 'Hồ sơ theo thời hạn bảo quản',
                        'condition' => 'Hồ sơ theo trạng thái',
                    ])
                    ->required()
                    ->inline(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->refreshReport())
                    ->default('year'),
            ])
            ->statePath('data');
    }

    public function refreshReport(): void
    {
        $this->appliedFilters = $this->form->getState();
        $this->buildReport();
    }

    protected function buildReport(): void
    {
        $mode = (string) ($this->appliedFilters['mode'] ?? 'year');

        $rows = match ($mode) {
            'item' => $this->buildByItem(),
            'preservation_duration' => $this->buildByPreservationDuration(),
            'condition' => $this->buildByCondition(),
            default => $this->buildByYear(),
        };

        $this->reportRows = $rows->values()->toArray();
        $this->totalRecords = (int) $rows->sum('records_count');
    }

    protected function buildByYear(): Collection
    {
        return $this->baseQuery()
            ->selectRaw("COALESCE(YEAR(start_date), YEAR(created_at)) as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->bucket ? (string) $row->bucket : 'Không xác định',
                'records_count' => (int) ($row->records_count ?? 0),
            ]);
    }

    protected function buildByItem(): Collection
    {
        $rows = $this->baseQuery()
            ->selectRaw('archive_record_item_id as bucket, COUNT(*) as records_count')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $itemNames = ArchiveRecordItem::query()->pluck('title', 'id');

        return $rows->map(fn ($row): array => [
            'label' => (string) ($itemNames[(int) $row->bucket] ?? 'Chưa phân mục lục'),
            'records_count' => (int) ($row->records_count ?? 0),
        ]);
    }

    protected function buildByPreservationDuration(): Collection
    {
        return $this->baseQuery()
            ->selectRaw("COALESCE(NULLIF(TRIM(preservation_duration), ''), 'Chưa cập nhật') as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->bucket,
                'records_count' => (int) ($row->records_count ?? 0),
            ]);
    }

    protected function buildByCondition(): Collection
    {
        return $this->baseQuery()
            ->selectRaw("COALESCE(NULLIF(TRIM(`condition`), ''), 'Chưa cập nhật') as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->bucket,
                'records_count' => (int) ($row->records_count ?? 0),
            ]);
    }

    protected function baseQuery(): Builder
    {
        $query = ArchiveRecord::query();
        $user = auth()->user();

        if ($user->role !== 'admin') {
            $organizationIds = $user->organizations()->pluck('organizations.id');

            if ($organizationIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('organization_id', $organizationIds);
        }

        return $query;
    }

    public function getModeLabel(): string
    {
        return match ((string) ($this->appliedFilters['mode'] ?? 'year')) {
            'item' => 'Hồ sơ theo mục lục',
            'preservation_duration' => 'Hồ sơ theo thời hạn bảo quản',
            'condition' => 'Hồ sơ theo trạng thái',
            default => 'Hồ sơ theo năm',
        };
    }
}
