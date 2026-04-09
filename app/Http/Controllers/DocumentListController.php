<?php

namespace App\Http\Controllers;

use App\Models\ArchiveRecord;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\DocumentListExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Illuminate\Support\Str;

class DocumentListController extends Controller
{
    public function show($id)
    {
        $archiveRecord = ArchiveRecord::select('id', 'title', 'code', 'organization_id', 'box_id')
            ->with([
                'documents' => fn($q) => $q->select('id', 'archive_record_id', 'document_number', 'document_symbol', 'document_code', 'document_date', 'issuing_agency', 'doc_type_id', 'description', 'signer', 'author', 'security_level', 'copy_type', 'page_number'),
                'documents.docType' => fn($q) => $q->select('id', 'name'),
                'organization' => fn($q) => $q->select('id', 'type', 'name'),
                'box' => fn($q) => $q->select('id', 'code')
            ])
            ->findOrFail($id);

        return Inertia::render('DocumentList', [
            'archiveRecord' => [
                'id' => $archiveRecord->id,
                'title' => $archiveRecord->title,
                'code' => $archiveRecord->code,
                'organization_type' => $archiveRecord->organization?->type,
                'box_code' => $archiveRecord->box?->code,
            ],
            'documents' => $archiveRecord->documents
                ->map(fn ($document) => [
                    'id' => $document->id,
                    'document_number' => $document->document_number,
                    'document_symbol' => $document->document_symbol,
                    'document_code' => $document->document_code,
                    'document_date' => $document->document_date,
                    'issuing_agency' => $document->issuing_agency,
                    'doc_type_name' => $document->docType?->name,
                    'description' => $document->description,
                    'signer' => $document->signer,
                    'author' => $document->author,
                    'security_level' => $document->security_level,
                    'copy_type' => $document->copy_type,
                    'page_number' => $document->page_number,
                    'total_pages' => $document->total_pages,
                    'file_count' => $document->file_count,
                    'file_name' => $document->file_name,
                    'document_duration' => $document->document_duration,
                    'usage_mode' => $document->usage_mode,
                    'keywords' => $document->keywords,
                    'note' => $document->note,
                    'language' => $document->language,
                    'handwritten' => $document->handwritten,
                    'topic' => $document->topic,
                    'information_code' => $document->information_code,
                    'reliability_level' => $document->reliability_level,
                    'physical_condition' => $document->physical_condition,
                ])
                ->values()
                ->all(),
            'links' => [
                'export_excel' => route('archive-records.documents.export-excel', $archiveRecord->id),
            ],
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
            'font_size' => 12, // Set a larger font size
        ]);
        
        return $pdf->download("danh-sach-tai-lieu-{$archiveRecord->code}.pdf");
    }

    public function exportExcel($id)
    {
        $archiveRecord = ArchiveRecord::with(['documents.docType', 'organization', 'archiveRecordItem', 'box'])->findOrFail($id);

        $fileName = Str::of($archiveRecord->title ?? 'Danh sach tai lieu')
            ->replaceMatches('/[\\\\\/:*?"<>|]/', ' ')
            ->trim()
            ->toString();

        return Excel::download(new DocumentListExport($archiveRecord), "{$fileName}.xlsx");
    }
}
