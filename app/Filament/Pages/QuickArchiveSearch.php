<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ArchiveRecordResource;
use App\Models\ArchiveRecord;
use App\Models\RecordType;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuickArchiveSearch extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Tìm kiếm nhanh';

    protected static ?string $title = 'Tìm kiếm nhanh hồ sơ';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.quick-archive-search';

    public ?array $data = [];

    public ?array $appliedFilters = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Mã hồ sơ')
                    ->placeholder('Nhập mã hồ sơ...'),

                TextInput::make('title')
                    ->label('Tiêu đề hồ sơ')
                    ->placeholder('Nhập tiêu đề hồ sơ...'),

                DatePicker::make('date_from')
                    ->label('Giai đoạn từ ngày')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->native(),

                DatePicker::make('date_to')
                    ->label('Giai đoạn đến ngày')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->native(),

                Select::make('record_type_id')
                    ->label('Loại hồ sơ')
                    ->options(fn (): array => RecordType::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('search')
                ->label('Tìm kiếm')
                ->icon('heroicon-o-magnifying-glass')
                ->color('warning')
                ->action(function (): void {
                    $this->appliedFilters = $this->form->getState();
                    $this->resetPage();

                    Notification::make()
                        ->title('Đã áp dụng tiêu chí tìm kiếm')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function resetSearch(): void
    {
        $this->form->fill([
            'code' => null,
            'title' => null,
            'date_from' => null,
            'date_to' => null,
            'record_type_id' => null,
        ]);

        $this->appliedFilters = [];
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getSearchQuery())
            ->columns([
                TextColumn::make('reference_code')
                    ->label('Mã tham chiếu')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Mã hồ sơ')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Tiêu đề hồ sơ')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('organization.name')
                    ->label('Phông lưu trữ')
                    ->sortable(),

                TextColumn::make('recordType.name')
                    ->label('Loại hồ sơ')
                    ->default('-'),

                TextColumn::make('date_range')
                    ->label('Ngày hồ sơ')
                    ->state(function (ArchiveRecord $record): string {
                        $start = $record->start_date ? Carbon::parse($record->start_date)->format('d/m/Y') : '';
                        $end = $record->end_date ? Carbon::parse($record->end_date)->format('d/m/Y') : '';

                        return trim($start . ($end ? ' - ' . $end : ''));
                    })
                    ->wrap(),

                TextColumn::make('box.code')
                    ->label('Hộp số')
                    ->default('-')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Không tìm thấy hồ sơ phù hợp')
            ->emptyStateDescription('Thử đổi tiêu chí tìm kiếm để xem thêm kết quả.')
            ->actions([
                Tables\Actions\Action::make('openArchiveRecords')
                    ->label('Xem trong danh sách hồ sơ')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ArchiveRecord $record): string => ArchiveRecordResource::getUrl('index')),
            ])
            ->headerActions([])
            ->bulkActions([]);
    }

    protected function getSearchQuery(): Builder
    {
        $query = ArchiveRecord::query()
            ->with(['organization', 'recordType', 'box']);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->role !== 'admin') {
            $organizationIds = $user->organizations()->pluck('organizations.id');

            if ($organizationIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('organization_id', $organizationIds);
        }

        $data = $this->appliedFilters;

        return $query
            ->when($data['code'] ?? null, function (Builder $q, $value): Builder {
                $keyword = trim((string) $value);

                return $q->where(function (Builder $inner) use ($keyword): void {
                    $inner
                        ->where('code', 'like', "%{$keyword}%")
                        ->orWhere('reference_code', 'like', "%{$keyword}%");
                });
            })
            ->when($data['title'] ?? null, fn (Builder $q, $value): Builder => $q->where('title', 'like', '%' . trim((string) $value) . '%'))
            ->when($data['record_type_id'] ?? null, fn (Builder $q, $value): Builder => $q->where('record_type_id', $value))
            ->when($data['date_from'] ?? null, fn (Builder $q, $value): Builder => $q->whereDate('end_date', '>=', $value))
            ->when($data['date_to'] ?? null, fn (Builder $q, $value): Builder => $q->whereDate('start_date', '<=', $value));
    }
}
