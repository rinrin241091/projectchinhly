<?php

namespace App\Exports;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArchiveRecordsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected Builder $query;
    protected ?Organization $organization;

    public function __construct(Builder $query, ?Organization $organization = null)
    {
        $this->query = $query;
        $this->organization = $organization;
    }

    public function collection()
    {
        return (clone $this->query)
            ->with(['box', 'archiveRecordItem'])
            ->withCount('documents')
            ->get();
    }

    public function headings(): array
    {
        if ($this->isPartyOrganization()) {
            return [
                'STT',
                'Địa chỉ BQ',
                'Tên đơn vị bảo quản',
                'Ngày hồ sơ (BĐ - KT)',
                'THBQ',
                'Số trang',
                'Số tài liệu',
                'Số cặp',
                'Ghi chú',
            ];
        }

        return [
            'ID',
            'Hộp số',
            'Hồ sơ số',
            'Tiêu đề hồ sơ',
            'Ngày tháng bắt đầu và kết thúc',
            'Thời hạn bảo quản',
            'Số lượng tờ',
            'Mục lục',
            'Ghi chú',
        ];
    }

    public function map($record): array
    {
        if ($this->isPartyOrganization()) {
            return [
                $record->id,
                $record->code ?? '',
                $record->title ?? '',
                $this->formatDateRange($record->start_date, $record->end_date),
                $record->preservation_duration ?? '',
                $record->page_count ?? '',
                $record->documents_count ?? 0,
                $record->box?->code ?? '',
                $record->note ?? '',
            ];
        }

        return [
            $record->id,
            $record->box?->code ?? '',
            $record->code ?? '',
            $record->title ?? '',
            $this->formatDateRange($record->start_date, $record->end_date),
            $record->preservation_duration ?? '',
            $record->page_count ?? '',
            $record->archiveRecordItem?->title ?? '',
            $record->note ?? '',
        ];
    }

    private function isPartyOrganization(): bool
    {
        return $this->organization?->type === 'Đảng';
    }

    private function formatDateRange($startDate, $endDate): string
    {
        $start = $startDate ? Carbon::parse($startDate)->format('d/m/Y') : '';
        $end = $endDate ? Carbon::parse($endDate)->format('d/m/Y') : '';

        return trim($start . ($start || $end ? ' - ' : '') . $end);
    }
}
