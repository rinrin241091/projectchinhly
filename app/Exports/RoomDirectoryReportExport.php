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

class RoomDirectoryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Danh mục phông';
    }

    public function collection(): Collection
    {
        $indexedRows = $this->rows->values()->map(function (array $row, int $index): array {
            return [
                'stt' => $index + 1,
                'name' => $row['name'],
                'records_count' => $row['records_count'],
                'time_range' => $row['time_range'],
            ];
        });

        $totals = [
            'stt' => '',
            'name' => 'TỔNG CỘNG',
            'records_count' => $indexedRows->sum('records_count'),
            'time_range' => '',
        ];

        return $indexedRows->concat([$totals]);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Phông',
            'Số hồ sơ',
            'Thời gian',
        ];
    }

    public function map($row): array
    {
        return [
            $row['stt'],
            $row['name'],
            $row['records_count'],
            $row['time_range'],
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);

        foreach (['A', 'C'] as $col) {
            $sheet->getStyle("{$col}:{$col}")->getAlignment()->setHorizontal('center');
        }

        $lastRow = $this->rows->count() + 2;
        $sheet->getStyle("A{$lastRow}:D{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:D{$lastRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFDE7');
    }
}
