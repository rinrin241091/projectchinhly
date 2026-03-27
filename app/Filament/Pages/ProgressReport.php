<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\ArchiveRecord;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgressReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Báo cáo tiến độ chỉnh lý';

    protected static ?string $title = 'Báo cáo tiến độ chỉnh lý';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.progress-report';

    public ?array $data = [];

    public ?array $appliedFilters = [];

    /** @var array<int, array>|null */
    public ?array $reportRows = null;

    /** @var array<string, int> */
    public array $reportTotals = [];

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['super_admin', 'admin', 'teamlead'], true);
    }

    public function mount(): void
    {
        $this->form->fill([
            'mode' => 'day',
        ]);

        $this->refreshReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('mode')
                    ->label('Kiểu thống kê')
                    ->options([
                        'day' => 'Theo ngày',
                        'month' => 'Theo tháng',
                        'organization' => 'Theo đơn vị thực hiện',
                        'user' => 'Theo nhân sự',
                    ])
                    ->inline()
                    ->inlineLabel(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->refreshReport())
                    ->required()
                    ->default('day'),

                DatePicker::make('date_from')
                    ->label('Từ ngày')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->live()
                    ->afterStateUpdated(fn () => $this->refreshReport())
                    ->native(),

                DatePicker::make('date_to')
                    ->label('Đến ngày')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->live()
                    ->afterStateUpdated(fn () => $this->refreshReport())
                    ->native(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function refreshReport(): void
    {
        $this->appliedFilters = $this->form->getState();
        $this->buildReport();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Xuất Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn (): string => route('report.progress.excel', array_filter($this->appliedFilters ?? [])))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! empty($this->reportRows)),

            Action::make('exportPdf')
                ->label('In báo cáo (PDF)')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('report.progress.pdf', array_filter($this->appliedFilters ?? [])))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! empty($this->reportRows)),
        ];
    }

    protected function buildReport(): void
    {
        $mode = (string) ($this->appliedFilters['mode'] ?? 'day');

        $rows = match ($mode) {
            'month' => $this->buildByMonth(),
            'organization' => $this->buildByOrganization(),
            'user' => $this->buildByUser(),
            default => $this->buildByDay(),
        };

        $this->reportRows = $rows->values()->toArray();
        $this->reportTotals = [
            'records_count' => (int) $rows->sum('records_count'),
            'documents_count' => (int) $rows->sum('documents_count'),
        ];
    }

    protected function buildByDay(): Collection
    {
        $recordQuery = $this->baseArchiveRecordQuery()
            ->selectRaw('DATE(created_at) as bucket, COUNT(*) as records_count')
            ->groupBy('bucket')
            ->orderBy('bucket');

        $documentQuery = $this->baseDocumentQuery()
            ->selectRaw('DATE(archive_records.created_at) as bucket, COUNT(documents.id) as documents_count')
            ->groupBy('bucket');

        return $this->mergeBuckets($recordQuery->get(), $documentQuery->get(), fn ($bucket) => $bucket ? date('d/m/Y', strtotime((string) $bucket)) : '-');
    }

    protected function buildByMonth(): Collection
    {
        $recordQuery = $this->baseArchiveRecordQuery()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket');

        $documentQuery = $this->baseDocumentQuery()
            ->selectRaw("DATE_FORMAT(archive_records.created_at, '%Y-%m') as bucket, COUNT(documents.id) as documents_count")
            ->groupBy('bucket');

        return $this->mergeBuckets($recordQuery->get(), $documentQuery->get(), function ($bucket): string {
            if (! $bucket) {
                return '-';
            }

            [$year, $month] = explode('-', (string) $bucket);

            return sprintf('%s/%s', $month, $year);
        });
    }

    protected function buildByOrganization(): Collection
    {
        $recordRows = $this->baseArchiveRecordQuery()
            ->selectRaw('organization_id as bucket, COUNT(*) as records_count')
            ->whereNotNull('organization_id')
            ->groupBy('bucket')
            ->get();

        $documentRows = $this->baseDocumentQuery()
            ->selectRaw('archive_records.organization_id as bucket, COUNT(documents.id) as documents_count')
            ->whereNotNull('archive_records.organization_id')
            ->groupBy('bucket')
            ->get();

        $orgNames = Organization::query()->pluck('name', 'id');

        return $this->mergeBuckets($recordRows, $documentRows, fn ($bucket): string => (string) ($orgNames[(int) $bucket] ?? 'Không xác định'));
    }

    protected function buildByUser(): Collection
    {
        $user = auth()->user();

        $query = Activity::query()
            ->selectRaw(
                "causer_id as bucket,
                SUM(CASE WHEN subject_type = ? THEN 1 ELSE 0 END) as records_count,
                SUM(CASE WHEN subject_type = ? THEN 1 ELSE 0 END) as documents_count",
                [ArchiveRecord::class, Document::class]
            )
            ->where('event', 'created')
            ->whereNotNull('causer_id')
            ->whereIn('subject_type', [ArchiveRecord::class, Document::class])
            ->groupBy('bucket')
            ->orderBy('bucket');

        if (! in_array($user->role, ['super_admin', 'admin'], true)) {
            $query->where('causer_id', $user->id);
        }

        $dateFrom = $this->appliedFilters['date_from'] ?? null;
        $dateTo = $this->appliedFilters['date_to'] ?? null;

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $rows = $query->get();

        $userNames = User::query()->pluck('name', 'id');

        return $rows->map(function ($row) use ($userNames): array {
            return [
                'label' => (string) ($userNames[(int) $row->bucket] ?? ('User #' . (int) $row->bucket)),
                'records_count' => (int) ($row->records_count ?? 0),
                'documents_count' => (int) ($row->documents_count ?? 0),
            ];
        });
    }

    protected function mergeBuckets(Collection $recordRows, Collection $documentRows, callable $labelFormatter): Collection
    {
        $docMap = $documentRows
            ->mapWithKeys(fn ($row) => [(string) $row->bucket => (int) ($row->documents_count ?? 0)]);

        return $recordRows->map(function ($row) use ($docMap, $labelFormatter): array {
            $bucket = (string) $row->bucket;

            return [
                'label' => $labelFormatter($row->bucket),
                'records_count' => (int) ($row->records_count ?? 0),
                'documents_count' => (int) ($docMap[$bucket] ?? 0),
            ];
        });
    }

    protected function baseArchiveRecordQuery(): Builder
    {
        $query = ArchiveRecord::query();
        $user = auth()->user();

        if (! in_array($user->role, ['super_admin', 'admin'], true)) {
            $organizationIds = $user->organizations()->pluck('organizations.id');
            if ($organizationIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('organization_id', $organizationIds);
        }

        $dateFrom = $this->appliedFilters['date_from'] ?? null;
        $dateTo = $this->appliedFilters['date_to'] ?? null;

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    protected function baseDocumentQuery()
    {
        $query = Document::query()->join('archive_records', 'archive_records.id', '=', 'documents.archive_record_id');
        $user = auth()->user();

        if (! in_array($user->role, ['super_admin', 'admin'], true)) {
            $organizationIds = $user->organizations()->pluck('organizations.id');
            if ($organizationIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('archive_records.organization_id', $organizationIds);
        }

        $dateFrom = $this->appliedFilters['date_from'] ?? null;
        $dateTo = $this->appliedFilters['date_to'] ?? null;

        if ($dateFrom) {
            $query->whereDate('archive_records.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('archive_records.created_at', '<=', $dateTo);
        }

        return $query;
    }

    public function getModeLabel(): string
    {
        $mode = (string) ($this->appliedFilters['mode'] ?? 'day');

        return match ($mode) {
            'month' => 'Theo tháng',
            'organization' => 'Theo đơn vị thực hiện',
            'user' => 'Theo nhân sự',
            default => 'Theo ngày',
        };
    }
}
