<?php

namespace App\Filament\Resources\ArchiveRecordResource\Pages;

use App\Filament\Resources\ArchiveRecordResource;
use App\Models\ArchiveRecord;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListArchiveRecords extends ListRecords
{
    protected static string $resource = ArchiveRecordResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->autoSyncOldRecords();
    }

    protected function autoSyncOldRecords(): void
    {
        $organizationId = session('selected_archival_id');

        if (!$organizationId) {
            return;
        }

        $total = ArchiveRecord::query()
            ->where('organization_id', $organizationId)
            ->whereHas('documents')
            ->where(function (Builder $q) {
                $q->whereNull('start_date')
                  ->orWhereNull('end_date')
                  ->orWhereNull('page_count')
                  ->orWhere('page_count', 0);
            })
            ->count();

        if ($total === 0) {
            return;
        }

        $synced = 0;
        ArchiveRecord::query()
            ->where('organization_id', $organizationId)
            ->whereHas('documents')
            ->where(function (Builder $q) {
                $q->whereNull('start_date')
                  ->orWhereNull('end_date')
                  ->orWhereNull('page_count')
                  ->orWhere('page_count', 0);
            })
            ->chunk(50, function ($records) use (&$synced) {
                foreach ($records as $record) {
                    $record->syncFromDocuments();
                    $synced++;
                }
            });

        \Filament\Notifications\Notification::make()
            ->title('Tự động đồng bộ hoàn tất')
            ->body("Đã đồng bộ {$synced}/{$total} hồ sơ thiếu dữ liệu.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (session()->has('selected_archival_id')) {
            $query->where('organization_id', session('selected_archival_id'));
        }

        return $query;
    }
        public function getTitle(): string
    {
        return 'Danh sách hồ sơ lưu trữ';
    }
}
