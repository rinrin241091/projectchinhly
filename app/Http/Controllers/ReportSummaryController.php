<?php

namespace App\Http\Controllers;

use App\Exports\ReportSummaryExport;
use App\Models\ArchiveRecord;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportSummaryController extends Controller
{
    const CACHE_DURATION = 3600; // 1 hour

    public function index(Request $request)
    {
        $user = auth()->user();

        // Get organizations with only needed columns
        $orgQuery = Organization::query()->select('id', 'name', 'archival_id');
        
        if ($user->role !== 'admin') {
            $orgIds = $user->organizations()->pluck('organizations.id')->toArray();
            $orgQuery->whereIn('id', $orgIds);
        } else {
            $archivalId = session('archival_id');
            if ($archivalId) {
                $orgQuery->where('archival_id', $archivalId);
            }
        }

        $organizations = $orgQuery->orderBy('name')->get()->map(fn ($org) => [
            'id' => $org->id,
            'name' => $org->name,
        ])->values();

        // Build report if filters are provided
        $reportRows = null;
        $reportTotals = [];
        $appliedFilters = [];

        if ($request->filled('date_from') || $request->filled('date_to') || $request->filled('org_id')) {
            $appliedFilters = $request->only('date_from', 'date_to', 'org_id');
            $cacheKey = 'report_summary:' . hash('sha256', json_encode($appliedFilters) . $user->id);
            
            // Check cache first
            if ($request->input('cache', 1)) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    $reportRows = $cached['rows'];
                    $reportTotals = $cached['totals'];
                    return Inertia::render('Reports/ReportSummary', [
                        'organizations' => $organizations,
                        'reportRows' => $reportRows,
                        'reportTotals' => $reportTotals,
                        'appliedFilters' => $appliedFilters,
                        'cached' => true,
                    ]);
                }
            }

            $result = $this->buildReportDataOptimized($request, $user);
            $reportRows = $result['rows'];
            $reportTotals = $result['totals'];
            
            // Cache the result
            Cache::put($cacheKey, [
                'rows' => $reportRows,
                'totals' => $reportTotals,
            ], self::CACHE_DURATION);
        }

        return Inertia::render('Reports/ReportSummary', [
            'organizations' => $organizations,
            'reportRows' => $reportRows,
            'reportTotals' => $reportTotals,
            'appliedFilters' => $appliedFilters,
            'cached' => false,
        ]);
    }

    public function pdf(Request $request)
    {
        $user = auth()->user();
        $data = $this->buildReportDataOptimized($request, $user);

        $pdf = Pdf::loadView('pdf.report-summary', [
            'rows' => $data['rows'],
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
            'totalRecords' => collect($data['rows'])->sum('records_count'),
            'totalDocuments' => collect($data['rows'])->sum('documents_count'),
            'totalBoxes' => collect($data['rows'])->sum('boxes_count'),
            'totalPages' => collect($data['rows'])->sum('total_pages'),
            'totalMetGia' => collect($data['rows'])->sum('met_gia'),
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('bao-cao-tong-hop-chinh-ly.pdf');
    }

    public function excel(Request $request)
    {
        $user = auth()->user();
        $data = $this->buildReportDataOptimized($request, $user);

        $export = new ReportSummaryExport(
            collect($data['rows']),
            $request->input('date_from'),
            $request->input('date_to')
        );

        return Excel::download($export, 'bao-cao-tong-hop-chinh-ly.xlsx');
    }

    /**
     * Optimized report data builder using raw queries to prevent N+1
     */
    protected function buildReportDataOptimized(Request $request, $user): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $orgId = $request->input('org_id');

        // Get organization IDs with proper filtering
        $orgQuery = Organization::query()->select('id', 'name');
        
        if ($user->role !== 'admin') {
            $userOrgIds = $user->organizations()->pluck('organizations.id')->toArray();
            $orgQuery->whereIn('id', $userOrgIds);
        } else {
            $archivalId = session('archival_id');
            if ($archivalId) {
                $orgQuery->where('archival_id', $archivalId);
            }
        }

        if ($orgId) {
            $orgQuery->where('id', $orgId);
        }

        $organizations = $orgQuery->orderBy('name')->get();
        
        if ($organizations->isEmpty()) {
            return ['rows' => [], 'totals' => []];
        }

        $orgIds = $organizations->pluck('id')->toArray();

        // Use raw SQL to get aggregated data in one query
        $rawData = DB::table('archive_records as ar')
            ->select([
                'ar.organization_id',
                DB::raw('COUNT(DISTINCT ar.id) as records_count'),
                DB::raw('COALESCE(SUM(d.count), 0) as documents_count'),
                DB::raw('COUNT(DISTINCT ar.box_id) as boxes_count'),
                DB::raw('COALESCE(SUM(ar.page_count), 0) as total_pages'),
            ])
            ->leftJoinSub(
                DB::table('documents')->select('archive_record_id', DB::raw('COUNT(*) as count'))->groupBy('archive_record_id'),
                'd',
                'ar.id',
                '=',
                'd.archive_record_id'
            )
            ->whereIn('ar.organization_id', $orgIds);

        // Apply date filters
        if ($dateFrom) {
            $rawData->whereDate('ar.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $rawData->whereDate('ar.created_at', '<=', $dateTo);
        }

        $aggregatedData = $rawData->groupBy('ar.organization_id')
            ->get()
            ->keyBy('organization_id');

        // Build rows with proper indexing
        $rows = [];
        $stt = 0;

        foreach ($organizations as $org) {
            $stt++;
            $data = $aggregatedData->get($org->id);
            
            $recordsCount = (int) ($data->records_count ?? 0);
            $documentsCount = (int) ($data->documents_count ?? 0);
            $boxesCount = (int) ($data->boxes_count ?? 0);
            $totalPages = (int) ($data->total_pages ?? 0);
            
            $rows[] = [
                'stt' => $stt,
                'name' => $org->name,
                'records_count' => $recordsCount,
                'documents_count' => $documentsCount,
                'boxes_count' => $boxesCount,
                'total_pages' => $totalPages,
                'met_gia' => $totalPages > 0 ? round($totalPages / 1000, 3) : 0.0,
            ];
        }

        // Calculate totals from rows
        $totals = [
            'records_count' => array_sum(array_column($rows, 'records_count')),
            'documents_count' => array_sum(array_column($rows, 'documents_count')),
            'boxes_count' => array_sum(array_column($rows, 'boxes_count')),
            'total_pages' => array_sum(array_column($rows, 'total_pages')),
            'met_gia' => array_sum(array_column($rows, 'met_gia')),
        ];

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}

