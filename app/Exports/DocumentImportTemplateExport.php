<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DocumentImportTemplateExport implements FromArray, ShouldAutoSize, WithTitle, WithEvents
{
    protected bool $isParty;

    public function __construct(bool $isParty = false)
    {
        $this->isParty = $isParty;
    }

    public function array(): array
    {
        if ($this->isParty) {
            return $this->getPartyTemplate();
        }

        return $this->getNormalTemplate();
    }

    private function getPartyTemplate(): array
    {
        return [
            // Header row - matches export format + extra columns for import
            [
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
                'Mã hồ sơ',
                'Loại văn bản',
            ],
            // Example row
            [
                '556/TTr-UBND',
                '15/03/2026',
                'Tờ trình về việc phê duyệt kế hoạch',
                'UBND phường',
                'Nguyễn Văn A',
                '',
                'Bản chính',
                '1 - 5',
                '5',
                'kế hoạch, phê duyệt',
                '',
                '1',
                'tep_tai_lieu.pdf',
                '1656',
                'Công văn',
            ],
        ];
    }

    private function getNormalTemplate(): array
    {
        return [
            // Header row - matches export format + extra columns for import
            [
                'Số, Ký hiệu',
                'Ngày tháng văn bản',
                'Trích yếu nội dung văn bản',
                'Tác giả văn bản',
                'Tờ số',
                'Ghi chú',
                'Mã hồ sơ',
                'Loại văn bản',
            ],
            // Example row
            [
                '556/TTr-UBND',
                '15/03/2026',
                'Tờ trình về việc phê duyệt kế hoạch',
                'UBND phường',
                '1 - 5',
                '',
                '1656',
                'Công văn',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

                $lastCol = $this->isParty ? 'O' : 'H';

                // Style header row
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9E1F2'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Highlight required import columns (Mã hồ sơ, Loại văn bản)
                $importColStart = $this->isParty ? 'N' : 'G';
                $importColEnd = $this->isParty ? 'O' : 'H';
                $sheet->getStyle("{$importColStart}1:{$importColEnd}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF2CC'],
                    ],
                ]);

                // Style example row
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['argb' => 'FF808080'], 'name' => 'Times New Roman'],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Set row height
                $sheet->getRowDimension(1)->setRowHeight(30);
            },
        ];
    }

    public function title(): string
    {
        return 'Mẫu import tài liệu';
    }
}
