<?php

namespace App\Http\Controllers;

use App\Exports\ProgressReportExport;
use App\Exports\RecordDocumentReportExport;
use App\Exports\RecordStatisticsReportExport;
use App\Exports\RoomDirectoryReportExport;
use App\Models\Activity;
use App\Models\ArchiveRecord;
use App\Models\ArchiveRecordItem;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Storage;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    protected ?array $resolvedOrganizationIds = null;

    public function progressPdf(Request $request)
    {
        $data = $this->buildProgressData($request);

        $pdf = Pdf::loadView('pdf.progress-report', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('bao-cao-tien-do-chinh-ly.pdf');
    }

    public function progressExcel(Request $request)
    {
        $data = $this->buildProgressData($request);

        return Excel::download(new ProgressReportExport($data['rows']), 'bao-cao-tien-do-chinh-ly.xlsx');
    }

    public function recordStatisticsPdf(Request $request)
    {
        $data = $this->buildRecordStatisticsData($request);

        $pdf = Pdf::loadView('pdf.record-statistics-report', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('bao-cao-thong-ke-ho-so.pdf');
    }

    public function recordStatisticsExcel(Request $request)
    {
        $data = $this->buildRecordStatisticsData($request);

        return Excel::download(new RecordStatisticsReportExport($data['rows']), 'bao-cao-thong-ke-ho-so.xlsx');
    }

    public function recordDocumentPdf()
    {
        $data = $this->buildRecordDocumentData();

        $pdf = Pdf::loadView('pdf.record-document-report', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('bao-cao-tai-lieu-trong-ho-so.pdf');
    }

    public function recordDocumentExcel()
    {
        $data = $this->buildRecordDocumentData();

        return Excel::download(new RecordDocumentReportExport($data['rows']), 'bao-cao-tai-lieu-trong-ho-so.xlsx');
    }

    public function roomDirectoryPdf()
    {
        $data = $this->buildRoomDirectoryData();

        $pdf = Pdf::loadView('pdf.room-directory-report', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('bao-cao-danh-muc-phong.pdf');
    }

    public function roomDirectoryExcel()
    {
        $data = $this->buildRoomDirectoryData();

        return Excel::download(new RoomDirectoryReportExport($data['rows']), 'bao-cao-danh-muc-phong.xlsx');
    }

    protected function buildProgressData(Request $request): array
    {
        $mode = (string) $request->input('mode', 'day');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $cacheKey = $this->reportCacheKey('progress', [
            'mode' => $mode,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($mode, $dateFrom, $dateTo) {
            $rows = match ($mode) {
                'month' => $this->buildProgressByMonth($dateFrom, $dateTo),
                'organization' => $this->buildProgressByOrganization($dateFrom, $dateTo),
                'user' => $this->buildProgressByUser($dateFrom, $dateTo),
                default => $this->buildProgressByDay($dateFrom, $dateTo),
            };

            return [
                'rows' => $rows->values(),
                'modeLabel' => $this->getProgressModeLabel($mode),
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'totalRecords' => (int) $rows->sum('records_count'),
                'totalDocuments' => (int) $rows->sum('documents_count'),
            ];
        });
    }

    protected function buildProgressByDay(?string $dateFrom, ?string $dateTo): Collection
    {
        $recordQuery = $this->baseArchiveRecordQuery($dateFrom, $dateTo)
            ->selectRaw('DATE(created_at) as bucket, COUNT(*) as records_count')
            ->groupBy('bucket')
            ->orderBy('bucket');

        $documentQuery = $this->baseDocumentQuery($dateFrom, $dateTo)
            ->selectRaw('DATE(archive_records.created_at) as bucket, COUNT(documents.id) as documents_count')
            ->groupBy('bucket');

        return $this->mergeBuckets(
            $recordQuery->get(),
            $documentQuery->get(),
            fn ($bucket) => $bucket ? date('d/m/Y', strtotime((string) $bucket)) : '-'
        );
    }

    protected function buildProgressByMonth(?string $dateFrom, ?string $dateTo): Collection
    {
        $recordQuery = $this->baseArchiveRecordQuery($dateFrom, $dateTo)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket');

        $documentQuery = $this->baseDocumentQuery($dateFrom, $dateTo)
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

    protected function buildProgressByOrganization(?string $dateFrom, ?string $dateTo): Collection
    {
        $recordRows = $this->baseArchiveRecordQuery($dateFrom, $dateTo)
            ->selectRaw('organization_id as bucket, COUNT(*) as records_count')
            ->whereNotNull('organization_id')
            ->groupBy('bucket')
            ->get();

        $documentRows = $this->baseDocumentQuery($dateFrom, $dateTo)
            ->selectRaw('archive_records.organization_id as bucket, COUNT(documents.id) as documents_count')
            ->whereNotNull('archive_records.organization_id')
            ->groupBy('bucket')
            ->get();

        // Only fetch orgs that exist in the data (prevent loading all orgs)
        $orgIds = $recordRows->pluck('bucket')->merge($documentRows->pluck('bucket'))->unique()->toArray();
        $orgNames = Organization::query()
            ->whereIn('id', $orgIds)
            ->pluck('name', 'id');

        return $this->mergeBuckets(
            $recordRows,
            $documentRows,
            fn ($bucket): string => (string) ($orgNames[(int) $bucket] ?? 'Không xác định')
        );
    }

    protected function buildProgressByUser(?string $dateFrom, ?string $dateTo): Collection
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

        if ($user->role !== 'admin') {
            $query->where('causer_id', $user->id);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $rows = $query->get();
        
        // Only load users that have activity (prevent loading all users)
        $userIds = $rows->pluck('bucket')->unique()->toArray();
        $userNames = User::query()
            ->whereIn('id', $userIds)
            ->pluck('name', 'id');

        return $rows->map(function ($row) use ($userNames): array {
            return [
                'label' => (string) ($userNames[(int) $row->bucket] ?? ('User #' . (int) $row->bucket)),
                'records_count' => (int) ($row->records_count ?? 0),
                'documents_count' => (int) ($row->documents_count ?? 0),
            ];
        });
    }

    protected function buildRecordStatisticsData(Request $request): array
    {
        $mode = (string) $request->input('mode', 'year');
        $cacheKey = $this->reportCacheKey('record_statistics', ['mode' => $mode]);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($mode) {
            $rows = match ($mode) {
                'item' => $this->buildRecordStatisticsByItem(),
                'preservation_duration' => $this->buildRecordStatisticsByPreservationDuration(),
                'condition' => $this->buildRecordStatisticsByCondition(),
                default => $this->buildRecordStatisticsByYear(),
            };

            return [
                'rows' => $rows->values(),
                'modeLabel' => $this->getRecordStatisticsModeLabel($mode),
                'totalRecords' => (int) $rows->sum('records_count'),
            ];
        });
    }

    protected function buildRecordStatisticsByYear(): Collection
    {
        return $this->baseRecordStatisticsQuery()
            ->selectRaw("COALESCE(YEAR(start_date), YEAR(created_at)) as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->bucket ? (string) $row->bucket : 'Không xác định',
                'records_count' => (int) ($row->records_count ?? 0),
            ]);
    }

    protected function buildRecordStatisticsByItem(): Collection
    {
        $rows = $this->baseRecordStatisticsQuery()
            ->selectRaw('archive_record_item_id as bucket, COUNT(*) as records_count')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        // Only load items that have records (prevent loading all items)
        $itemIds = $rows->pluck('bucket')->filter()->unique()->toArray();
        $itemNames = $itemIds ? ArchiveRecordItem::query()
            ->whereIn('id', $itemIds)
            ->pluck('title', 'id') : collect();

        return $rows->map(fn ($row): array => [
            'label' => (string) ($itemNames[(int) $row->bucket] ?? 'Chưa phân mục lục'),
            'records_count' => (int) ($row->records_count ?? 0),
        ]);
    }

    protected function buildRecordStatisticsByPreservationDuration(): Collection
    {
        return $this->baseRecordStatisticsQuery()
            ->selectRaw("COALESCE(NULLIF(TRIM(preservation_duration), ''), 'Chưa cập nhật') as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->bucket,
                'records_count' => (int) ($row->records_count ?? 0),
            ]);
    }

    protected function buildRecordStatisticsByCondition(): Collection
    {
        return $this->baseRecordStatisticsQuery()
            ->selectRaw("COALESCE(NULLIF(TRIM(`condition`), ''), 'Chưa cập nhật') as bucket, COUNT(*) as records_count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->bucket,
                'records_count' => (int) ($row->records_count ?? 0),
            ]);
    }

    protected function buildRecordDocumentData(): array
    {
        $cacheKey = $this->reportCacheKey('record_document', []);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $rows = $this->buildRecordDocumentRows();

            return [
                'rows' => $rows,
                'totalRecords' => $rows->count(),
                'totalDocuments' => (int) $rows->sum('documents_count'),
                'totalPages' => (int) $rows->sum('document_pages'),
            ];
        });
    }

    protected function buildRecordDocumentRows(): Collection
    {
        $user = auth()->user();
        
        // Cache org IDs to avoid repeated querying
        $organizationIds = $user->role === 'admin' 
            ? null 
            : $user->organizations()->pluck('organizations.id')->toArray();

        if ($organizationIds !== null && empty($organizationIds)) {
            return collect();
        }

        $query = ArchiveRecord::query()
            ->leftJoin('documents', 'documents.archive_record_id', '=', 'archive_records.id')
            ->selectRaw(
                "archive_records.id,
                archive_records.code,
                archive_records.reference_code,
                archive_records.title,
                archive_records.page_count,
                COUNT(documents.id) as documents_count,
                SUM(CAST(COALESCE(NULLIF(documents.total_pages, ''), NULLIF(documents.page_number, ''), 0) AS UNSIGNED)) as document_pages"
            )
            ->groupBy(
                'archive_records.id',
                'archive_records.code',
                'archive_records.reference_code',
                'archive_records.title',
                'archive_records.page_count'
            )
            ->orderBy('archive_records.id');

        if ($organizationIds !== null) {
            $query->whereIn('archive_records.organization_id', $organizationIds);
        }

        return $query->get()->map(function ($row): array {
            $recordPages = (int) ($row->page_count ?? 0);
            $documentPages = (int) ($row->document_pages ?? 0);
            $documentsCount = (int) ($row->documents_count ?? 0);

            return [
                'record_label' => $row->code ?: ($row->reference_code ?: ($row->title ?: ('Hồ sơ #' . $row->id))),
                'record_title' => $row->title ?: '-',
                'documents_count' => $documentsCount,
                'document_pages' => $documentPages,
                'status' => $this->resolveDocumentStatus($documentsCount, $recordPages, $documentPages),
            ];
        });
    }

    protected function buildRoomDirectoryData(): array
    {
        $cacheKey = $this->reportCacheKey('room_directory', []);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $rows = Storage::query()
                ->leftJoin('archive_records', 'archive_records.storage_id', '=', 'storages.id')
                ->selectRaw('
                    storages.id,
                    storages.name,
                    COUNT(archive_records.id) as records_count,
                    MIN(YEAR(archive_records.start_date)) as year_min,
                    MAX(COALESCE(YEAR(archive_records.end_date), YEAR(archive_records.start_date))) as year_max
                ')
                ->groupBy('storages.id', 'storages.name')
                ->orderBy('storages.name')
                ->get()
                ->map(function ($row): array {
                    $yearMin = $row->year_min;
                    $yearMax = $row->year_max;

                    if ($yearMin && $yearMax) {
                        $timeRange = ($yearMin === $yearMax) ? (string) $yearMin : "{$yearMin}-{$yearMax}";
                    } else {
                        $timeRange = '-';
                    }

                    return [
                        'id' => $row->id,
                        'name' => $row->name,
                        'records_count' => (int) $row->records_count,
                        'time_range' => $timeRange,
                    ];
                });

            return [
                'rows' => $rows,
                'totalRooms' => $rows->count(),
                'totalRecords' => (int) $rows->sum('records_count'),
            ];
        });
    }

    protected function baseArchiveRecordQuery(?string $dateFrom, ?string $dateTo): Builder
    {
        $query = ArchiveRecord::query();
        $organizationIds = $this->resolveScopedOrganizationIds();

        if ($organizationIds !== null) {
            if (empty($organizationIds)) {
                return $query->whereRaw('1 = 0');
            }
            $query->whereIn('organization_id', $organizationIds);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    protected function baseDocumentQuery(?string $dateFrom, ?string $dateTo)
    {
        $query = Document::query()->join('archive_records', 'archive_records.id', '=', 'documents.archive_record_id');
        $organizationIds = $this->resolveScopedOrganizationIds();

        if ($organizationIds !== null) {
            if (empty($organizationIds)) {
                return $query->whereRaw('1 = 0');
            }
            $query->whereIn('archive_records.organization_id', $organizationIds);
        }

        if ($dateFrom) {
            $query->whereDate('archive_records.created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('archive_records.created_at', '<=', $dateTo);
        }

        return $query;
    }

    protected function baseRecordStatisticsQuery(): Builder
    {
        $query = ArchiveRecord::query();
        $organizationIds = $this->resolveScopedOrganizationIds();

        if ($organizationIds !== null) {
            if (empty($organizationIds)) {
                return $query->whereRaw('1 = 0');
            }
            $query->whereIn('organization_id', $organizationIds);
        }

        return $query;
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

    protected function resolveDocumentStatus(int $documentsCount, int $recordPages, int $documentPages): string
    {
        if ($documentsCount === 0) {
            return 'Thiếu tài liệu';
        }

        if ($recordPages > 0 && $documentPages === 0) {
            return 'Thiếu số trang';
        }

        if ($recordPages > 0 && $documentPages !== $recordPages) {
            return 'Sai lệch số trang';
        }

        return 'Đủ';
    }

    protected function getProgressModeLabel(string $mode): string
    {
        return match ($mode) {
            'month' => 'Theo tháng',
            'organization' => 'Theo đơn vị thực hiện',
            'user' => 'Theo nhân sự',
            default => 'Theo ngày',
        };
    }

    protected function getRecordStatisticsModeLabel(string $mode): string
    {
        return match ($mode) {
            'item' => 'Hồ sơ theo mục lục',
            'preservation_duration' => 'Hồ sơ theo thời hạn bảo quản',
            'condition' => 'Hồ sơ theo trạng thái',
            default => 'Hồ sơ theo năm',
        };
    }

    protected function reportCacheKey(string $reportType, array $params): string
    {
        $user = auth()->user();
        $scope = $this->resolveScopedOrganizationIds();

        return 'report_export:' . $reportType . ':' . md5(json_encode([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'scope' => $scope,
            'params' => $params,
        ]));
    }

    protected function resolveScopedOrganizationIds(): ?array
    {
        if ($this->resolvedOrganizationIds !== null) {
            return $this->resolvedOrganizationIds;
        }

        $user = auth()->user();

        if (! $user || $user->role === 'admin') {
            $this->resolvedOrganizationIds = null;

            return $this->resolvedOrganizationIds;
        }

        $this->resolvedOrganizationIds = $user->organizations()
            ->pluck('organizations.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        return $this->resolvedOrganizationIds;
    }
}
