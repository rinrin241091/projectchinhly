<?php

namespace App\Filament\Pages;

use App\Models\Storage;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoomDirectoryReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Báo cáo danh mục phông';

    protected static ?string $title = 'Báo cáo danh mục phông';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.room-directory-report';

    /** @var array<int, array>|null */
    public ?array $reportRows = null;

    public int $totalRooms = 0;

    public int $totalRecords = 0;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'teamlead'], true);
    }

    public function mount(): void
    {
        $rows = $this->buildReportRows();
        $this->reportRows = $rows->toArray();
        $this->totalRooms = $rows->count();
        $this->totalRecords = (int) $rows->sum('records_count');
    }

    protected function buildReportRows(): Collection
    {
        $rows = Storage::query()
            ->leftJoin('archive_records', 'archive_records.storage_id', '=', 'storages.id')
            ->selectRaw('
                storages.id,
                storages.name,
                COUNT(archive_records.id) as records_count,
                MIN(YEAR(archive_records.start_date)) as year_min,
                MAX(COALESCE(YEAR(archive_records.end_date), YEAR(archive_records.start_date))) as year_max
            ')
            ->groupBy('storages.id', 'storages.name')
            ->orderBy('storages.name')
            ->get();

        return $rows->map(function ($row) {
            $yearMin = $row->year_min;
            $yearMax = $row->year_max;

            if ($yearMin && $yearMax) {
                $timeRange = ($yearMin === $yearMax) ? (string) $yearMin : "{$yearMin}–{$yearMax}";
            } else {
                $timeRange = '—';
            }

            return [
                'id'            => $row->id,
                'name'          => $row->name,
                'records_count' => (int) $row->records_count,
                'time_range'    => $timeRange,
            ];
        });
    }
}
