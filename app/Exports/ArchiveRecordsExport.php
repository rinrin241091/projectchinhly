<?php

namespace App\Exports;

use App\Models\ArchiveRecord;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class ArchiveRecordsExport implements FromQuery, WithHeadings, WithEvents, WithMapping
{
    use Exportable;

    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Hộp số',
            'Số hồ sơ',
            'Tiêu đề hồ sơ',
            'Ngày tháng bắt đầu và kết thúc',
            'Thời hạn bảo quản',
            'Số tờ',
            'Tình trạng tài liệu',
            'Ghi chú',
        ];
    }

    public function map($record): array
    {
        return [
            'Hộp số' => $record->box?->description ?? '',
            'Số hồ sơ' => $record->reference_code ?? '',
            'Tiêu đề hồ sơ' => $record->title ?? '',
            'Ngày tháng bắt đầu và kết thúc' => ($record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d/m/Y') : '') 
                . ' - ' . 
            ($record->end_date ? \Carbon\Carbon::parse($record->end_date)->format('d/m/Y') : ''),
            'Thời hạn bảo quản' => $record->preservation_duration ?? '',
            'Số tờ' => $record->page_count ?? '',
            'Tình trạng tài liệu' => $record->condition ?? '',
            'Ghi chú' => $record->note ?? '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

            // Cover page
            $sheet->mergeCells('A1:H5');
            $sheet->setCellValue('A1', 'TÊN CƠ QUAN LƯU TRỮ');
            $sheet->setCellValue('A2', 'MỤC LỤC HỒ SƠ');
            $sheet->setCellValue('A3', 'Phông lưu trữ: ' . 'Tên Cơ Quan Lưu Trữ'); // Placeholder for organization name
            $sheet->setCellValue('A4', 'Phông số: ' . 'Mã Phông'); // Placeholder for organization code
            $sheet->setCellValue('A5', 'Mục lục số: ' . 'Mã Mục Lục'); // Placeholder for archival record item code
            $sheet->setCellValue('A6', 'Số trang: ' . $this->query->count());

            // Style cover page
            $sheet->getStyle('A1:H5')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1:H5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:H5')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getRowDimension('1')->setRowHeight(30);
            $sheet->getRowDimension('2')->setRowHeight(30);
            $sheet->getRowDimension('3')->setRowHeight(30);
            $sheet->getRowDimension('4')->setRowHeight(30);
            $sheet->getRowDimension('5')->setRowHeight(30);
            $sheet->getRowDimension('6')->setRowHeight(30);
            
            // Add a blank row after the cover page
            $sheet->insertNewRowBefore(7, 1);
            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A1', 'MỤC LỤC HỒ SƠ');

            // Style main title
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set the column headings in row 2 (A2 to H2)
            $headings = $this->headings();
            $col = 'A';
            foreach ($headings as $heading) {
                $sheet->setCellValue($col . '2', $heading);
                $col++;
            }

            // Style header row
            $sheet->getStyle('A2:H2')->getFont()->setBold(true);
            $sheet->getStyle('A2:H2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Fill data starting from row 3
            $row = 3;
            foreach ($this->query->get() as $record) {
                $col = 'A';
                $data = $this->map($record);
                foreach ($data as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(40);
            $sheet->getColumnDimension('D')->setWidth(25);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(10);
            $sheet->getColumnDimension('G')->setWidth(20);
            $sheet->getColumnDimension('H')->setWidth(30);
        },
    ];
}
}
