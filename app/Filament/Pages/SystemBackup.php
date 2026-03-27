<?php

namespace App\Filament\Pages;

use App\Models\DisasterSyncSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class SystemBackup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Sao lưu hệ thống';

    protected static ?string $title = 'Sao lưu hệ thống';

    protected static string $view = 'filament.pages.system-backup';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('target_ip')
                    ->label('IP máy dự phòng')
                    ->placeholder('Ví dụ: 192.168.1.20')
                    ->required(),

                TimePicker::make('sync_time')
                    ->label('Giờ xuất/nhập sang hệ thống dự phòng')
                    ->seconds(false)
                    ->format('H:i')
                    ->displayFormat('H:i')
                    ->required(),

                Toggle::make('sync_is_active')
                    ->label('Bật lịch dự phòng')
                    ->default(true),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importDataFile')
                ->label('Nạp dữ liệu từ file')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('data_file')
                        ->label('File dữ liệu')
                        ->disk('local')
                        ->directory('imports/manual')
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/csv',
                            'application/json',
                            'text/plain',
                            'application/sql',
                            'application/x-sql',
                            'application/zip',
                            'application/x-zip-compressed',
                        ])
                        ->required()
                        ->helperText('Hỗ trợ: .sql, .csv, .json, .txt, .zip (định dạng JSON array trong file được tự nhận diện).'),
                ])
                ->action(function (array $data): void {
                    $relativePath = $data['data_file'] ?? null;

                    if (! is_string($relativePath) || trim($relativePath) === '') {
                        Notification::make()
                            ->title('Vui lòng chọn file dữ liệu')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        $message = $this->importUploadedData($relativePath);

                        Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Nạp dữ liệu thất bại: ' . $exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('downloadLatestSqlBackup')
                ->label('Tải SQL gần nhất')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('database-backup.download-latest'))
                ->openUrlInNewTab(),

            Action::make('downloadFreshSqlBackup')
                ->label('Tạo và tải SQL ngay')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->url(route('database-backup.download-fresh'))
                ->openUrlInNewTab(),

            Action::make('saveDisasterSchedule')
                ->label('Lưu lịch hệ thống dự phòng')
                ->icon('heroicon-o-server-stack')
                ->color('warning')
                ->action(function (): void {
                    $targetIp = trim((string) ($this->data['target_ip'] ?? ''));
                    $syncTime = $this->data['sync_time'] ?? null;
                    $isActive = (bool) ($this->data['sync_is_active'] ?? true);

                    if ($targetIp === '' || ! $syncTime) {
                        Notification::make()
                            ->title('Vui lòng nhập IP máy dự phòng và giờ chạy')
                            ->warning()
                            ->send();

                        return;
                    }

                    DisasterSyncSchedule::query()->updateOrCreate(
                        ['target_ip' => $targetIp],
                        [
                            'sync_time' => date('H:i:s', strtotime((string) $syncTime)),
                            'is_active' => $isActive,
                        ]
                    );

                    Notification::make()
                        ->title('Đã lưu lịch hệ thống dự phòng thành công')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function importUploadedData(string $relativePath): string
    {
        $fullPath = Storage::disk('local')->path($relativePath);

        if (! File::exists($fullPath)) {
            throw new RuntimeException('Không tìm thấy file đã tải lên.');
        }

        $extension = strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($extension === 'sql') {
            $this->importSqlFile($fullPath);

            return 'Đã nạp file SQL vào database thành công.';
        }

        if ($extension === 'zip') {
            $imported = $this->importZipFile($fullPath);

            return 'Đã nạp dữ liệu từ file ZIP thành công (' . $imported . ' file).';
        }

        if (in_array($extension, ['csv', 'json', 'txt'], true)) {
            $this->importLegacyJsonArrayFile($fullPath);

            return 'Đã nạp dữ liệu từ file thành công.';
        }

        throw new RuntimeException('Định dạng file chưa được hỗ trợ.');
    }

    private function importZipFile(string $zipPath): int
    {
        $extractPath = storage_path('app/imports/runtime/zip_' . now()->format('Ymd_His_u'));
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);

        if ($opened !== true) {
            throw new RuntimeException('Không thể mở file ZIP.');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $files = collect(File::allFiles($extractPath))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['csv', 'json', 'txt', 'sql'], true))
            ->values();

        if ($files->isEmpty()) {
            throw new RuntimeException('Không tìm thấy file dữ liệu hợp lệ trong ZIP.');
        }

        $imported = 0;

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());

            if ($extension === 'sql') {
                $this->importSqlFile($file->getPathname());
            } else {
                $this->importLegacyJsonArrayFile($file->getPathname());
            }

            $imported++;
        }

        return $imported;
    }

    private function importLegacyJsonArrayFile(string $filePath): void
    {
        $rows = $this->readJsonRows($filePath);

        if (count($rows) === 0) {
            throw new RuntimeException('File dữ liệu không có bản ghi hợp lệ.');
        }

        $targetFileName = $this->resolveLegacyTargetFileName($rows);
        $runtimeDir = storage_path('app/imports/runtime/manual_' . now()->format('Ymd_His_u'));
        File::ensureDirectoryExists($runtimeDir);

        $targetPath = $runtimeDir . DIRECTORY_SEPARATOR . $targetFileName;
        File::copy($filePath, $targetPath);

        $args = [
            '--path' => $runtimeDir,
        ];

        if ($targetFileName === 'archive_records.csv') {
            $args['--archive-records-file'] = $targetPath;
        }

        $exitCode = Artisan::call('legacy:import-zalo', $args);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Lệnh import dữ liệu trả về lỗi.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function resolveLegacyTargetFileName(array $rows): string
    {
        $first = $rows[0] ?? [];

        if (array_key_exists('reference_code', $first) && array_key_exists('organization_id', $first)) {
            return 'archive_records.csv';
        }

        if (array_key_exists('archive_record_item_code', $first) && array_key_exists('organization_id', $first)) {
            return 'archive_records_items.csv';
        }

        if (array_key_exists('shelf_id', $first) && array_key_exists('description', $first)) {
            return 'boxes.csv';
        }

        if (array_key_exists('storage_id', $first) && array_key_exists('code', $first) && ! array_key_exists('shelf_id', $first)) {
            return 'shelves.csv';
        }

        if (array_key_exists('archival_id', $first) && array_key_exists('location', $first)) {
            return 'storages.csv';
        }

        if (array_key_exists('archival_id', $first) && array_key_exists('archivals_time', $first)) {
            return 'organizations.csv';
        }

        if (array_key_exists('identifier', $first) && array_key_exists('manager', $first)) {
            return 'archivals.csv';
        }

        throw new RuntimeException('Không nhận diện được cấu trúc file dữ liệu.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJsonRows(string $filePath): array
    {
        if (! File::exists($filePath)) {
            return [];
        }

        $content = trim((string) File::get($filePath));
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('File không đúng định dạng JSON array.');
        }

        return array_values(array_filter($decoded, fn ($row) => is_array($row)));
    }

    private function importSqlFile(string $sqlFilePath): void
    {
        $mysqlBinary = $this->resolveMysqlBinary();
        if ($mysqlBinary === null) {
            throw new RuntimeException('Không tìm thấy mysql client. Hãy cấu hình DB_MYSQL_BINARY trong .env');
        }

        $connection = config('database.default');
        $db = (array) config('database.connections.' . $connection);

        $database = (string) ($db['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('DB_DATABASE đang trống.');
        }

        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (string) ($db['port'] ?? '3306');
        $username = (string) ($db['username'] ?? 'root');
        $password = (string) ($db['password'] ?? '');

        $command = [
            $mysqlBinary,
            '-h' . $host,
            '-P' . $port,
            '-u' . $username,
        ];

        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $command[] = $database;

        $sqlContent = File::get($sqlFilePath);
        $process = new Process($command, null, null, $sqlContent, 600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'Import SQL thất bại.'));
        }
    }

    private function resolveMysqlBinary(): ?string
    {
        $fromEnv = env('DB_MYSQL_BINARY');
        if (is_string($fromEnv) && $fromEnv !== '' && File::exists($fromEnv)) {
            return $fromEnv;
        }

        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'mysql',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysql') {
                return $candidate;
            }

            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function getDisasterSchedulesProperty(): Collection
    {
        return DisasterSyncSchedule::query()
            ->orderBy('sync_time')
            ->get();
    }

    public function editDisasterSchedule(int $scheduleId): void
    {
        $schedule = DisasterSyncSchedule::query()->find($scheduleId);

        if (! $schedule) {
            Notification::make()
                ->title('Không tìm thấy lịch hệ thống dự phòng')
                ->danger()
                ->send();

            return;
        }

        $this->data['target_ip'] = $schedule->target_ip;
        $this->data['sync_time'] = optional($schedule->sync_time)->format('H:i');
        $this->data['sync_is_active'] = (bool) $schedule->is_active;

        Notification::make()
            ->title('Đã nạp lịch hệ thống dự phòng lên form')
            ->success()
            ->send();
    }

    public function deleteDisasterSchedule(int $scheduleId): void
    {
        $schedule = DisasterSyncSchedule::query()->find($scheduleId);

        if (! $schedule) {
            Notification::make()
                ->title('Lịch hệ thống dự phòng không tồn tại')
                ->warning()
                ->send();

            return;
        }

        $schedule->delete();

        Notification::make()
            ->title('Đã xóa lịch hệ thống dự phòng')
            ->success()
            ->send();
    }
}
