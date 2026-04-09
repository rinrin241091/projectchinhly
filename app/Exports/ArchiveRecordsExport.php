<?php

namespace App\Exports;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArchiveRecordsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected Builder $query;
    protected ?Organization $organization;

    public function __construct(Builder $query, ?Organization $organization = null)
    {
        $this->query = $query;
        $this->organization = $organization;
    }

    public function query()
    {
        return (clone $this->query)
            ->with(['box', 'archiveRecordItem', 'organization'])
            ->withCount('documents');
    }

    public function headings(): array
    {
        if ($this->isPartyOrganization()) {
            return [
                'STT',
                'Phông số',
                'Số cặp (hộp)',
                'Mục lục số',
                'Hồ sơ số',
                'Tên nhóm và tên hồ sơ',
                'Từ khóa',
                'Chú giải',
                'Thời gian bắt đầu và kết thúc',
                'Thời hạn bảo quản',
                'Số trang',
                'Số tài liệu',
                'Độ mật',
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
                $record->organization?->code ?? '',
                $record->box?->code ?? '',
                $record->archiveRecordItem?->archive_record_item_code ?? '',
                $record->code ?? '',
                $record->title ?? '',
                $record->symbols_code ?? '',
                $record->description ?? '',
                $this->formatDateRange($record->start_date, $record->end_date),
                $record->preservation_duration ?? '',
                $record->page_count ?? '',
                $record->documents_count ?? 0,
                $record->usage_mode ?? '',
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
