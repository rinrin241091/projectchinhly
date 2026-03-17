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

class RecordStatisticsReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Thống kê hồ sơ';
    }

    public function collection(): Collection
    {
        $totals = [
            'label' => 'TỔNG CỘNG',
            'records_count' => $this->rows->sum('records_count'),
        ];

        return $this->rows->concat([$totals]);
    }

    public function headings(): array
    {
        return [
            'Tiêu chí',
            'Số hồ sơ',
        ];
    }

    public function map($row): array
    {
        return [
            $row['label'],
            $row['records_count'],
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal('center');

        $lastRow = $this->rows->count() + 2;
        $sheet->getStyle("A{$lastRow}:B{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:B{$lastRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFDE7');
    }
}
