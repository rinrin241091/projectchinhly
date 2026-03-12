<?php

namespace App\Exports;

use App\Models\ArchiveRecord;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentListExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $archiveRecord;
    protected int $rowNumber = 0;

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
                $document->document_number ?: $document->document_code ?: 'N/A',
                $document->document_symbol ?: $document->document_code ?: 'N/A',
                $this->formatDate($document->document_date),
                $document->issuing_agency ?? 'N/A',
                $document->docType->name ?? 'N/A',
                $document->description ?? 'N/A',
                $document->signer ?: $document->author ?: 'N/A',
                $document->security_level ?? 'N/A',
                $document->copy_type ?? 'N/A',
                $document->page_number ?? 'N/A',
                $document->total_pages ?? 'N/A',
                $document->file_count ?? 'N/A',
                $document->file_name ?? 'N/A',
                $document->document_duration ?? 'N/A',
                $document->usage_mode ?? 'N/A',
                $document->keywords ?? 'N/A',
                $document->note ?? 'N/A',
                $document->language ?? 'N/A',
                $document->handwritten ?? 'N/A',
                $document->topic ?? 'N/A',
                $document->information_code ?? 'N/A',
                $document->reliability_level ?? 'N/A',
                $document->physical_condition ?? 'N/A',
            ];
        }

        return [
            $document->document_code ?? 'N/A',
            $this->formatDate($document->document_date),
            $document->description ?? 'N/A',
            $document->author ?: $document->signer ?: 'N/A',
            $document->page_number ?? 'N/A',
            $document->note ?? 'N/A',
        ];
    }

    private function isPartyOrganization(): bool
    {
        return $this->archiveRecord->organization?->type === 'Đảng';
    }

    private function formatDate($value): string
    {
        if (empty($value)) {
            return 'N/A';
        }

        return Carbon::parse($value)->format('d/m/Y');
    }
}
