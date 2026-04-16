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
            'Trích yếu',
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
        $documents = $this->archiveRecord->documents->sort(function ($a, $b) {
            $aVal = is_numeric($a->page_number_from) ? (int)$a->page_number_from : PHP_INT_MAX;
            $bVal = is_numeric($b->page_number_from) ? (int)$b->page_number_from : PHP_INT_MAX;
            if ($aVal === $bVal) {
                return 0;
            }
            return $aVal <=> $bVal;
        })->values();

        $rowNumber = 0;
        foreach ($documents as $document) {
            $rowNumber++;
            $soKyHieu = ($document->document_code === '(Không số)')
                ? ''
                : ($document->document_code ?: trim(collect([$document->document_number, $document->document_symbol])->filter()->implode('/')));
            $tenLoaiTrichYeu = $document->description ?? '';
            $rows[] = [
                $rowNumber,
                $soKyHieu,
                $this->formatDate($document->document_date, $document->date_unverified ?? false),
                $tenLoaiTrichYeu,
                $document->issuing_agency ?? '',
                $document->signer ?: $document->author ?: '',
                $document->security_level ?? '',
                $document->copy_type ?? '',
                $this->getExportPageNumber($document),
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
        $documents = $this->archiveRecord->documents->sort(function ($a, $b) {
            $aVal = is_numeric($a->page_number_from) ? (int)$a->page_number_from : PHP_INT_MAX;
            $bVal = is_numeric($b->page_number_from) ? (int)$b->page_number_from : PHP_INT_MAX;
            if ($aVal === $bVal) {
                return 0;
            }
            return $aVal <=> $bVal;
        })->values();

        foreach ($documents as $document) {
            $rows[] = [
                ($document->document_code === '(Không số)') ? '' : ($document->document_code ?? ''),
                $this->formatDate($document->document_date, $document->date_unverified ?? false),
                $document->description ?? '',
                $document->author ?: $document->signer ?: '',
                $this->getExportPageNumber($document),
                $document->note ?? '',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman');
                $sheet->getStyle($sheet->calculateWorksheetDimension())->getFont()->setName('Times New Roman');

                $docCount = $this->archiveRecord->documents->count();

                if (! $this->isPartyOrganization()) {
                    // Normal org: date column is B, data starts at row 2
                    $lastRow = max(2, 1 + $docCount);
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $cell = $sheet->getCell("B{$row}");
                        $cell->setValueExplicit($cell->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    return;
                }

                $lastRow = max(5, 4 + $docCount);

                // Force date column (C) values to text so Excel won't auto-format as date
                for ($row = 5; $row <= $lastRow; $row++) {
                    $cell = $sheet->getCell("C{$row}");
                    $cell->setValueExplicit($cell->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }

                // Merge and style title row
                $sheet->mergeCells('A1:N1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
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

    private function formatDate($value, bool $unverified = false): string
    {
        if (empty($value)) {
            return '';
        }

        $formatted = Carbon::parse($value)->format('d/m/Y');

        return $unverified ? '[' . $formatted . ']' : $formatted;
    }

    private function getExportPageNumber($document): string
    {
        $from = trim((string) ($document->page_number_from ?? ''));
        $to = trim((string) ($document->page_number_to ?? ''));

        if ($from !== '' && $to !== '') {
            return $from . ' - ' . $to;
        }

        if ($from !== '') {
            return $from;
        }

        if ($to !== '') {
            return $to;
        }

        $pageNumber = trim((string) ($document->page_number ?? ''));
        if ($pageNumber !== '' && strpos($pageNumber, '-') !== false) {
            return preg_replace('/\s*-\s*/', ' - ', $pageNumber);
        }

        return $pageNumber;
    }
}
