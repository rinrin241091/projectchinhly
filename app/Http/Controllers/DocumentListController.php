<?php

namespace App\Http\Controllers;

use App\Models\ArchiveRecord;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\DocumentListExport;
use Maatwebsite\Excel\Facades\Excel;

class DocumentListController extends Controller
{
    public function show($id)
    {
        $archiveRecord = ArchiveRecord::with(['documents.docType', 'organization', 'box'])->findOrFail($id);
        
        return view('document-list', [
            'archiveRecord' => $archiveRecord,
            'documents' => $archiveRecord->documents
        ]);
    }

    public function exportPdf($id)
    {
        $archiveRecord = ArchiveRecord::with(['documents.docType', 'organization'])->findOrFail($id);
        
        $pdf = Pdf::loadView('pdf.document-list', [
            'archiveRecord' => $archiveRecord,
            'documents' => $archiveRecord->documents
        ]);
        
        // Configure PDF options for Vietnamese font support
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'Arial', // Use Arial for Vietnamese character support
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'font_size' => 12, // Set a larger font size
            'isRemoteEnabled' => true,
        ]);
        
        return $pdf->download("danh-sach-tai-lieu-{$archiveRecord->code}.pdf");
    }

    public function exportExcel($id)
    {
        $archiveRecord = ArchiveRecord::with(['documents.docType', 'organization', 'box'])->findOrFail($id);
        
        return Excel::download(new DocumentListExport($archiveRecord), "danh-sach-tai-lieu-{$archiveRecord->code}.xlsx");
    }
}
