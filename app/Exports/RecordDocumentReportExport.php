<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecordDocumentReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Tài liệu trong hồ sơ';
    }

    public function collection(): Collection
    {
        $totals = [
            'record_label' => 'TỔNG CỘNG',
            'record_title' => '',
            'documents_count' => $this->rows->sum('documents_count'),
            'document_pages' => $this->rows->sum('document_pages'),
            'status' => '',
        ];

        return $this->rows->concat([$totals]);
    }

    public function headings(): array
    {
        return [
            'Hồ sơ',
            'Tiêu đề',
            'Tài liệu',
            'Trang',
            'Kiểm tra',
        ];
    }

    public function map($row): array
    {
        return [
            $row['record_label'],
            $row['record_title'],
            $row['documents_count'],
            $row['document_pages'],
            $row['status'],
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);

        foreach (['C', 'D'] as $col) {
            $sheet->getStyle("{$col}:{$col}")->getAlignment()->setHorizontal('center');
        }

        $lastRow = $this->rows->count() + 2;
        $sheet->getStyle("A{$lastRow}:E{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:E{$lastRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFDE7');
    }
}
