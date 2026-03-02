<?php

namespace App\Filament\Resources\ArchiveRecordItemResource\Pages;

use App\Filament\Resources\ArchiveRecordItemResource;
use Filament\Resources\Pages\Page;

class ViewArchivalRecord extends Page
{
    protected static string $resource = ArchiveRecordItemResource::class;

    protected static string $view = 'archive_record';

    public $record;

    public function mount($record)
    {
        $this->record = $record;
    }
    
    // Thêm phương thức này để loại bỏ layout mặc định (sidebar, topbar, ...)
    protected function getLayoutView(): ?string
    {
        return null;
    }

    protected function getViewData(): array
    {
        $archiveRecordItem = \App\Models\ArchiveRecordItem::with('organization', 'records')->findOrFail($this->record);
        return [
            'archiveRecordItem' => $archiveRecordItem,
            'records' => $archiveRecordItem->records,
        ];
    }
}