<?php

namespace App\Exports;

use App\Models\ArchiveRecord;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DocumentListExport implements FromArray, ShouldAutoSize, WithTitle, WithEvents
{
    protected $archiveRecord;

    public function __construct(ArchiveRecord $archiveRecord)
    {
        $this->archiveRecord = $archiveRecord->load('archiveRecordItem', 'documents.docType');
    }

    public function array(): array
    {
        if ($this->isPartyOrganization()) {
            return $this->getPartyArray();
        }

        return $this->getNormalArray();
    }

    private function getPartyArray(): array
    {
        $rows = [];

        // Row 1: Main title = Tên hồ sơ
        $rows[] = [$this->archiveRecord->title ?: 'Tên hồ sơ'];

        // Row 2-3: hồ sơ info
        $rows[] = ["Mã hồ sơ: {$this->archiveRecord->code}"];
        $rows[] = ["Tên hồ sơ: {$this->archiveRecord->title}"];

        // Row 4: Column headings (match Phục lục số 01)
        $rows[] = [
            'Số TT',
            'Số, ký hiệu',
            'Ngày, tháng, năm văn bản',
            'Tên loại và trích yếu',
            'Tác giả',
            'Người ký',
            'Độ mật',
            'Loại bản',
            'Trang số',
            'Số trang',
            'Từ khóa',
            'Ghi chú',
            'Số lượng tệp (file)',
            'Tên tệp tài liệu',
        ];

        // Data rows
        $rowNumber = 0;
        foreach ($this->archiveRecord->documents as $document) {
            $rowNumber++;
            // Use merged field; fallback for legacy rows before migration
            $soKyHieu = $document->document_code
                ?: trim(collect([$document->document_number, $document->document_symbol])->filter()->implode('/'));
            // Merge tên loại + trích yếu
            $tenLoaiTrichYeu = trim(collect([$document->docType->name ?? '', $document->description ?? ''])->filter()->implode(' - '));
            $rows[] = [
                $rowNumber,
                $soKyHieu,
                $this->formatDate($document->document_date),
                $tenLoaiTrichYeu,
                $document->issuing_agency ?? '',
                $document->signer ?: $document->author ?: '',
                $document->security_level ?? '',
                $document->copy_type ?? '',
                $document->page_number ?? '',
                $document->total_pages ?? '',
                $document->keywords ?? '',
                $document->note ?? '',
                $document->file_count ?? 1,
                $document->file_name ?? '',
            ];
        }

        return $rows;
    }

    private function getNormalArray(): array
    {
        $rows = [];

        // Column headings
        $rows[] = [
            'Số, Ký hiệu',
            'Ngày tháng văn bản',
            'Trích yếu nội dung văn bản',
            'Tác giả văn bản',
            'Tờ số',
            'Ghi chú',
        ];

        // Data rows
        foreach ($this->archiveRecord->documents as $document) {
            $rows[] = [
                $document->document_code ?? '',
                $this->formatDate($document->document_date),
                $document->description ?? '',
                $document->author ?: $document->signer ?: '',
                $document->page_number ?? '',
                $document->note ?? '',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (! $this->isPartyOrganization()) {
                    return;
                }

                $sheet = $event->sheet->getDelegate();
                $lastRow = max(5, 4 + $this->archiveRecord->documents->count());

                // Merge and style title row
                $sheet->mergeCells('A1:N1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Bold hồ sơ info labels + header row
                $sheet->getStyle('A2:A3')->getFont()->setBold(true);
                $sheet->getStyle('A4:N4')->getFont()->setBold(true);
                $sheet->getStyle('A4:N4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A4:N4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A4:N4')->getAlignment()->setWrapText(true);

                // Draw borders for header + data table
                $sheet->getStyle("A4:N{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Improve readability for long columns
                $sheet->getStyle("D5:D{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("L5:L{$lastRow}")->getAlignment()->setWrapText(true);
            },
        ];
    }

    public function title(): string
    {
        return Str::of($this->archiveRecord->title ?? 'Danh sách tài liệu')
            ->replaceMatches('/[\\\\\/*?:\[\]]/', ' ')
            ->trim()
            ->substr(0, 31)
            ->toString();
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
