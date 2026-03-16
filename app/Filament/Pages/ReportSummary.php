<?php

namespace App\Filament\Pages;

use App\Models\ArchiveRecord;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ReportSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'BC tổng hợp KL chỉnh lý';

    protected static ?string $title = 'Báo cáo tổng hợp khối lượng chỉnh lý';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.report-summary';

    public ?array $data = [];

    public ?array $appliedFilters = [];

    /** @var array<int, array>|null */
    public ?array $reportRows = null;

    /** @var array<string, int|float> */
    public array $reportTotals = [];

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'teamlead'], true);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date_from')
                    ->label('Từ ngày chỉnh lý')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->native(),

                DatePicker::make('date_to')
                    ->label('Đến ngày chỉnh lý')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->native(),

                Select::make('org_id')
                    ->label('Phông lưu trữ')
                    ->options(fn () => $this->getOrgOptions())
                    ->placeholder('Tất cả phông')
                    ->searchable(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calculate')
                ->label('Xem báo cáo')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->action(function (): void {
                    $this->appliedFilters = $this->form->getState();
                    $this->buildReport();

                    Notification::make()
                        ->title('Đã cập nhật báo cáo')
                        ->success()
                        ->send();
                }),

            Action::make('exportExcel')
                ->label('Xuất Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn (): string => route('report.summary.excel', array_filter($this->appliedFilters ?? [])))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! empty($this->reportRows)),

            Action::make('exportPdf')
                ->label('In báo cáo (PDF)')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('report.summary.pdf', array_filter($this->appliedFilters ?? [])))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! empty($this->reportRows)),
        ];
    }

    protected function getOrgOptions(): array
    {
        $user = auth()->user();

        $query = Organization::query();

        if ($user->role !== 'admin') {
            $query->whereIn('id', $user->organizations()->pluck('organizations.id'));
        } else {
            $archivalId = session('archival_id');
            if ($archivalId) {
                $query->where('archival_id', $archivalId);
            }
        }

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected function buildReport(): void
    {
        $user = auth()->user();
        $dateFrom = $this->appliedFilters['date_from'] ?? null;
        $dateTo   = $this->appliedFilters['date_to']   ?? null;
        $orgId    = $this->appliedFilters['org_id']    ?? null;

        $orgQuery = Organization::query();

        if ($user->role !== 'admin') {
            $orgQuery->whereIn('id', $user->organizations()->pluck('organizations.id'));
        } else {
            $archivalId = session('archival_id');
            if ($archivalId) {
                $orgQuery->where('archival_id', $archivalId);
            }
        }

        if ($orgId) {
            $orgQuery->where('id', $orgId);
        }

        $organizations = $orgQuery->orderBy('name')->get();

        $rows = [];
        $stt  = 0;

        foreach ($organizations as $org) {
            $stt++;

            $q = ArchiveRecord::query()->where('organization_id', $org->id);

            if ($dateFrom) {
                $q->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $q->whereDate('created_at', '<=', $dateTo);
            }

            $records       = $q->withCount('documents')->get();
            $boxIds        = $records->pluck('box_id')->filter()->unique();
            $totalPages    = (int) $records->sum('page_count');
            $documentsCount = (int) $records->sum('documents_count');

            $rows[] = [
                'stt'             => $stt,
                'name'            => $org->name,
                'records_count'   => $records->count(),
                'documents_count' => $documentsCount,
                'boxes_count'     => $boxIds->count(),
                'total_pages'     => $totalPages,
                'met_gia'         => $totalPages > 0 ? round($totalPages / 1000, 3) : 0.0,
            ];
        }

        $this->reportRows   = $rows;
        $this->reportTotals = [
            'records_count'   => array_sum(array_column($rows, 'records_count')),
            'documents_count' => array_sum(array_column($rows, 'documents_count')),
            'boxes_count'     => array_sum(array_column($rows, 'boxes_count')),
            'total_pages'     => array_sum(array_column($rows, 'total_pages')),
            'met_gia'         => array_sum(array_column($rows, 'met_gia')),
        ];
    }
}
