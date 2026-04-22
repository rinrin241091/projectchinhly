<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Imports\DocumentsImport;
use App\Exports\DocumentImportTemplateExport;
use App\Traits\RoleBasedPermissions;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Http\RedirectResponse;

class ImportDocuments extends Page
{
    use RoleBasedPermissions;
    protected static string $resource = DocumentResource::class;

    protected static string $view = 'filament.resources.document-resource.pages.import-documents';

    public ?array $data = [];

    public function mount(): RedirectResponse|null
    {
        // Check if user has permission to access this page
        if (!static::canImport()) {
            Notification::make()
                ->title('Bị từ chối')
                ->body('Bạn không có quyền truy cập trang import. Chỉ Quản trị viên, Teamlead hoặc Người chỉnh sửa mới có thể import.')
                ->danger()
                ->send();
            return redirect()->route('filament.dashboard.resources.documents.index');
        }
        
        $this->form->fill();
        return null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Import Tài Liệu')
                    ->description('Tải lên file Excel (.xlsx) hoặc CSV với các cột giống mẫu xuất Excel')
                    ->schema([
                        Forms\Components\FileUpload::make('files')
                            ->label('File Excel hoặc CSV (có thể chọn nhiều file)')
                            ->required()
                            ->multiple()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'application/csv',
                                'text/plain',
                            ])
                            ->maxSize(10240)
                            ->disk('local')
                            ->directory('imports'),

                        Forms\Components\Select::make('organization_id')
                            ->label('Chọn Phông Lưu Trữ')
                            ->options(function () {
                                return \App\Models\Organization::pluck('name', 'id');
                            })
                            ->required()
                            ->disabled(fn () => !session()->has('selected_archival_id'))
                            ->default(fn () => session('selected_archival_id'))
                            ->live(),

                        Forms\Components\Select::make('archive_record_id')
                            ->label('Chọn Hồ Sơ (nếu file không có cột "Mã hồ sơ")')
                            ->placeholder('-- Không chọn (dùng cột Mã hồ sơ trong file) --')
                            ->options(function (callable $get) {
                                $orgId = $get('organization_id');
                                if (!$orgId) return [];
                                return \App\Models\ArchiveRecord::where('organization_id', $orgId)
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn ($r) => [$r->id => $r->code . ' - ' . \Illuminate\Support\Str::limit($r->title, 60)])
                                    ->toArray();
                            })
                            ->searchable()
                            ->helperText('Chọn hồ sơ nếu import file Excel đã xuất từ hệ thống (không có cột Mã hồ sơ).'),
                    ]),
            ])
            ->statePath('data');
    }

    public function downloadTemplate()
    {
        $orgId = session('selected_archival_id');
        $isParty = false;

        if ($orgId) {
            $org = \App\Models\Organization::find($orgId);
            $isParty = $org?->type === 'Đảng';
        }

        return Excel::download(
            new DocumentImportTemplateExport($isParty),
            'mau_import_tai_lieu.xlsx'
        );
    }

    public function import()
    {
        // Check if user has import permission
        if (!static::canImport()) {
            Notification::make()
                ->title('Bị từ chối')
                ->body('Bạn không có quyền import tài liệu. Chỉ Quản trị viên, Teamlead hoặc Người chỉnh sửa mới có thể import.')
                ->danger()
                ->send();
            return redirect()->route('filament.dashboard.resources.documents.index');
        }
        
        $data = $this->form->getState();
        
        try {
            $files = (array) $data['files'];
            $organizationId = $data['organization_id'];
            $archiveRecordId = $data['archive_record_id'] ?? null;
            
            $totalImported = 0;
            $totalSkipped = 0;
            $errors = [];

            $allSkipReasons = [];

            foreach ($files as $file) {
                try {
                    $filePath = storage_path('app/' . $file);

                    // Pre-detect file format: heading row + archive record from metadata
                    [$headingRow, $detectedRecordId] = DocumentsImport::detectFormat($filePath, $organizationId);

                    // Priority: form selection > detected from file
                    $recordId = $archiveRecordId ?: $detectedRecordId;

                    $importer = new DocumentsImport($organizationId, $recordId, $headingRow);
                    if ($detectedRecordId) {
                        $importer->setDetectedArchiveRecordId($detectedRecordId);
                    }
                    Excel::import($importer, $filePath);
                    $totalImported += $importer->getImportedCount();
                    $totalSkipped += $importer->getSkippedCount();

                    // Collect skip reasons
                    $skipReasons = $importer->getSkipReasons();
                    if (!empty($skipReasons)) {
                        $prefix = count($files) > 1 ? '[' . basename($file) . '] ' : '';
                        foreach ($skipReasons as $reason) {
                            $allSkipReasons[] = $prefix . $reason;
                        }
                    }
                } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                    $failures = $e->failures();
                    $fileErrors = collect($failures)->take(5)->map(function ($failure) {
                        return "Dòng {$failure->row()}: " . implode(', ', $failure->errors());
                    })->toArray();
                    $prefix = count($files) > 1 ? '[' . basename($file) . '] ' : '';
                    foreach ($fileErrors as $err) {
                        $errors[] = $prefix . $err;
                    }
                } catch (\Exception $e) {
                    $errors[] = (count($files) > 1 ? '[' . basename($file) . '] ' : '') . $e->getMessage();
                }
            }

            // Show success notification
            if ($totalImported > 0) {
                $msg = "Đã import thành công {$totalImported} tài liệu từ " . count($files) . " file.";
                Notification::make()
                    ->title('Import thành công')
                    ->body($msg)
                    ->success()
                    ->duration(8000)
                    ->send();
            }

            // Show skip reasons
            if (!empty($allSkipReasons)) {
                $skipMsg = "Bỏ qua {$totalSkipped} dòng:\n" . implode("\n", array_slice($allSkipReasons, 0, 10));
                if (count($allSkipReasons) > 10) {
                    $skipMsg .= "\n... và " . (count($allSkipReasons) - 10) . " dòng khác.";
                }
                Notification::make()
                    ->title('Một số dòng bị bỏ qua')
                    ->body($skipMsg)
                    ->warning()
                    ->duration(15000)
                    ->send();
            }

            // Show file-level errors
            if (!empty($errors)) {
                Notification::make()
                    ->title('Lỗi dữ liệu')
                    ->body(implode("\n", array_slice($errors, 0, 8)))
                    ->danger()
                    ->duration(15000)
                    ->send();
            }

            if ($totalImported > 0) {
                return redirect()->route('filament.dashboard.resources.documents.index');
            }

            // If nothing imported and no errors shown yet
            if ($totalImported === 0 && empty($errors) && empty($allSkipReasons)) {
                Notification::make()
                    ->title('Không có dữ liệu')
                    ->body('Không tìm thấy dòng nào hợp lệ trong file. Vui lòng kiểm tra lại file và đảm bảo có dòng tiêu đề (header) ở dòng đầu tiên.')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi import')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function save()
    {
        $data = $this->form->getState();
        
        try {
            $files = (array) $data['files'];
            $organizationId = $data['organization_id'];
            $fileCount = count($files);
            
            // Store import details in session for later use
            session()->put('pending_import', [
                'file_count' => $fileCount,
                'organization_id' => $organizationId,
            ]);
            
            Notification::make()
                ->title('Lưu thành công')
                ->body("{$fileCount} file đã được lưu thành công! Sẵn sàng để import dữ liệu.")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi lưu')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Quay lại')
                ->url(route('filament.dashboard.resources.documents.index'))
                ->color('gray'),
        ];
    }
}
