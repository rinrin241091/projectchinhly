<?php

namespace App\Exports;

use App\Models\ArchiveRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentListExport implements FromCollection, WithHeadings, WithMapping
{
    protected $archiveRecord;

    public function __construct(ArchiveRecord $archiveRecord)
    {
        $this->archiveRecord = $archiveRecord;
    }

    public function collection()
    {
        return $this->archiveRecord->documents()->with('docType')->get();
    }

    public function headings(): array
    {
        return [
            'Mã tài liệu',
            'Loại tài liệu',
            'Mô tả',
            'Tác giả',
            'Số trang',
            'Ngày tài liệu'
        ];
    }

    public function map($document): array
    {
        return [
            $document->document_code ?? 'N/A',
            $document->docType->name ?? 'N/A',
            $document->description,
            $document->author ?? 'N/A',
            $document->page_number ?? 'N/A',
            $document->document_date ?? 'N/A'
        ];
    }
}
