<?php

namespace App\Exports;

use App\Models\ArchiveRecord;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentListExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $archiveRecord;
    protected int $rowNumber = 0;

    public function __construct(ArchiveRecord $archiveRecord)
    {
        $this->archiveRecord = $archiveRecord;
    }

    public function query()
    {
        return $this->archiveRecord->documents()->select('documents.id', 'documents.document_number', 'documents.doc_key', 'documents.issued_date', 'documents.issuing_organization', 'documents.language', 'documents.description', 'documents.note', 'documents.doc_type_id', 'documents.archive_record_id')->with(['docType:id,name']);
    }

    public function headings(): array
    {
        if ($this->isPartyOrganization()) {
            return [
                'Số TT',
                'Số của văn bản',
                'Ký hiệu của văn bản',
                'Ngày, tháng, năm văn bản',
                'Tên cơ quan, tổ chức ban hành văn bản',
                'Tên loại văn bản',
                'Trích yếu nội dung',
                'Người ký',
                'Độ mật',
                'Loại bản',
                'Trang số',
                'Số trang',
                'Số lượng tệp (file)',
                'Tên tệp',
                'Thời gian tài liệu',
                'Chế độ sử dụng',
                'Từ khóa',
                'Ghi chú',
                'Ngôn ngữ',
                'Bút tích',
                'Chuyên đề',
                'Ký hiệu thông tin',
                'Mức độ tin cậy',
                'Tình trạng vật lý',
            ];
        }

        return [
            'Số, Ký hiệu',
            'Ngày tháng văn bản',
            'Trích yếu nội dung văn bản',
            'Tác giả văn bản',
            'Tờ số',
            'Ghi chú',
        ];
    }

    public function map($document): array
    {
        if ($this->isPartyOrganization()) {
            $this->rowNumber++;

            return [
                $this->rowNumber,
                $document->document_number ?: $document->document_code ?: '',
                $document->document_symbol ?: $document->document_code ?: '',
                $this->formatDate($document->document_date),
                $document->issuing_agency ?? '',
                $document->docType->name ?? '',
                $document->description ?? '',
                $document->signer ?: $document->author ?: '',
                $document->security_level ?? '',
                $document->copy_type ?? '',
                $document->page_number ?? '',
                $document->total_pages ?? '',
                $document->file_count ?? '',
                $document->file_name ?? '',
                $document->document_duration ?? '',
                $document->usage_mode ?? '',
                $document->keywords ?? '',
                $document->note ?? '',
                $document->language ?? '',
                $document->handwritten ?? '',
                $document->topic ?? '',
                $document->information_code ?? '',
                $document->reliability_level ?? '',
                $document->physical_condition ?? '',
            ];
        }

        return [
            $document->document_code ?? '',
            $this->formatDate($document->document_date),
            $document->description ?? '',
            $document->author ?: $document->signer ?: '',
            $document->page_number ?? '',
            $document->note ?? '',
        ];
    }

    private function isPartyOrganization(): bool
    {
        return $this->archiveRecord->organization?->type === 'Đảng';
    }

    private function formatDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        return Carbon::parse($value)->format('d/m/Y');
    }
}
