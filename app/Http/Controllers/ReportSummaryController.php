<?php

namespace App\Http\Controllers;

use App\Exports\ReportSummaryExport;
use App\Models\ArchiveRecord;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportSummaryController extends Controller
{
    public function pdf(Request $request)
    {
        $data = $this->buildReportData($request);

        $pdf = Pdf::loadView('pdf.report-summary', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('bao-cao-tong-hop-chinh-ly.pdf');
    }

    public function excel(Request $request)
    {
        $data = $this->buildReportData($request);

        $export = new ReportSummaryExport(
            $data['rows'],
            $data['dateFrom'],
            $data['dateTo']
        );

        return Excel::download($export, 'bao-cao-tong-hop-chinh-ly.xlsx');
    }

    protected function buildReportData(Request $request): array
    {
        $user     = auth()->user();
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $orgId    = $request->input('org_id');

        $orgQuery = Organization::query();

        if ($user->role !== 'admin') {
            $orgQuery->whereIn('id', $user->organizations()->pluck('organizations.id'));
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

        $rows = collect();
        $stt  = 0;

        foreach ($organizations as $org) {
            $stt++;

            $q = ArchiveRecord::query()->where('organization_id', $org->id);

            if ($dateFrom) {
                $q->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $q->whereDate('created_at', '<=', $dateTo);
            }

            $records        = $q->withCount('documents')->get();
            $boxIds         = $records->pluck('box_id')->filter()->unique();
            $totalPages     = (int) $records->sum('page_count');
            $documentsCount = (int) $records->sum('documents_count');

            $rows->push([
                'stt'             => $stt,
                'name'            => $org->name,
                'records_count'   => $records->count(),
                'documents_count' => $documentsCount,
                'boxes_count'     => $boxIds->count(),
                'total_pages'     => $totalPages,
                'met_gia'         => $totalPages > 0 ? round($totalPages / 1000, 3) : 0.0,
            ]);
        }

        return [
            'rows'           => $rows,
            'dateFrom'       => $dateFrom,
            'dateTo'         => $dateTo,
            'totalRecords'   => $rows->sum('records_count'),
            'totalDocuments' => $rows->sum('documents_count'),
            'totalBoxes'     => $rows->sum('boxes_count'),
            'totalPages'     => $rows->sum('total_pages'),
            'totalMetGia'    => $rows->sum('met_gia'),
        ];
    }
}
