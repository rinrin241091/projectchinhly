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

class ReportSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    protected Collection $rows;

    protected ?string $dateFrom;

    protected ?string $dateTo;

    public function __construct(Collection $rows, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->rows     = $rows;
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
    }

    public function title(): string
    {
        return 'Tổng hợp chỉnh lý';
    }

    /**
     * Returns data rows plus a totals row appended at the end.
     */
    public function collection(): Collection
    {
        $totals = [
            'stt'             => '',
            'name'            => 'TỔNG CỘNG',
            'records_count'   => $this->rows->sum('records_count'),
            'documents_count' => $this->rows->sum('documents_count'),
            'boxes_count'     => $this->rows->sum('boxes_count'),
            'total_pages'     => $this->rows->sum('total_pages'),
            'met_gia'         => $this->rows->sum('met_gia'),
        ];

        return $this->rows->concat([$totals]);
    }

    public function headings(): array
    {
        return [
            'STT',
            'Tên phông lưu trữ',
            'Số hồ sơ chỉnh lý',
            'Số văn bản / tài liệu',
            'Số hộp',
            'Tổng số trang',
            'Mét giá (m)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['stt'],
            $row['name'],
            $row['records_count'],
            $row['documents_count'],
            $row['boxes_count'],
            $row['total_pages'],
            number_format((float) $row['met_gia'], 3, '.', ''),
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        // Bold heading row
        $sheet->getStyle('1')->getFont()->setBold(true);

        // Center numeric columns
        foreach (['C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getStyle("{$col}:{$col}")->getAlignment()->setHorizontal('center');
        }

        // Bold + yellow background for totals row
        $lastRow = $this->rows->count() + 2; // 1 heading + N data rows + 1 totals row
        $sheet->getStyle("A{$lastRow}:G{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:G{$lastRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFDE7');
    }
}
