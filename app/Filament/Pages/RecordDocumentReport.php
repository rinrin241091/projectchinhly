<?php

namespace App\Filament\Pages;

use App\Models\ArchiveRecord;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordDocumentReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Báo cáo tài liệu trong hồ sơ';

    protected static ?string $title = 'Báo cáo tài liệu trong hồ sơ';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.record-document-report';

    /** @var array<int, array>|null */
    public ?array $reportRows = null;

    public int $totalRecords = 0;

    public int $totalDocuments = 0;

    public int $totalPages = 0;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['super_admin', 'admin', 'teamlead'], true);
    }

    public function mount(): void
    {
        $rows = $this->buildReportRows();

        $this->reportRows = $rows->toArray();
        $this->totalRecords = $rows->count();
        $this->totalDocuments = (int) $rows->sum('documents_count');
        $this->totalPages = (int) $rows->sum('document_pages');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Xuất Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn (): string => route('report.record-document.excel'))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! empty($this->reportRows)),

            Action::make('exportPdf')
                ->label('In báo cáo (PDF)')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('report.record-document.pdf'))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! empty($this->reportRows)),
        ];
    }

    protected function buildReportRows(): Collection
    {
        $query = ArchiveRecord::query()
            ->leftJoin('documents', 'documents.archive_record_id', '=', 'archive_records.id')
            ->selectRaw(
                "archive_records.id,
                archive_records.code,
                archive_records.reference_code,
                archive_records.title,
                archive_records.page_count,
                COUNT(documents.id) as documents_count,
                SUM(CAST(COALESCE(NULLIF(documents.total_pages, ''), NULLIF(documents.page_number, ''), 0) AS UNSIGNED)) as document_pages"
            )
            ->groupBy(
                'archive_records.id',
                'archive_records.code',
                'archive_records.reference_code',
                'archive_records.title',
                'archive_records.page_count'
            )
            ->orderBy('archive_records.id');

        $user = auth()->user();

        if (! in_array($user->role, ['super_admin', 'admin'], true)) {
            $organizationIds = $user->organizations()->pluck('organizations.id');

            if ($organizationIds->isEmpty()) {
                return collect();
            }

            $query->whereIn('archive_records.organization_id', $organizationIds);
        }

        return $query->get()->map(function ($row): array {
            $recordPages = (int) ($row->page_count ?? 0);
            $documentPages = (int) ($row->document_pages ?? 0);
            $documentsCount = (int) ($row->documents_count ?? 0);

            return [
                'record_label' => $row->code ?: ($row->reference_code ?: ($row->title ?: ('Hồ sơ #' . $row->id))),
                'record_title' => $row->title ?: '-',
                'documents_count' => $documentsCount,
                'document_pages' => $documentPages,
                'status' => $this->resolveStatus($documentsCount, $recordPages, $documentPages),
            ];
        });
    }

    protected function resolveStatus(int $documentsCount, int $recordPages, int $documentPages): string
    {
        if ($documentsCount === 0) {
            return 'Thiếu tài liệu';
        }

        if ($recordPages > 0 && $documentPages === 0) {
            return 'Thiếu số trang';
        }

        if ($recordPages > 0 && $documentPages !== $recordPages) {
            return 'Sai lệch số trang';
        }

        return 'Đủ';
    }
}
