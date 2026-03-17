<?php

namespace App\Console\Commands;

use App\Models\StorageBackupSchedule;
use App\Services\StorageBackupService;
use Illuminate\Console\Command;

class RunScheduledStorageBackups extends Command
{
    protected $signature = 'backups:run-scheduled';

    protected $description = 'Run storage backups based on configured schedules';

    public function handle(StorageBackupService $backupService): int
    {
        $currentTime = now()->format('H:i:00');

        $schedules = StorageBackupSchedule::query()
            ->with('storage')
            ->where('is_active', true)
            ->where('backup_time', $currentTime)
            ->get();

        if ($schedules->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($schedules as $schedule) {
            if ($schedule->last_run_at && $schedule->last_run_at->isToday()) {
                continue;
            }

            if (! $schedule->storage) {
                continue;
            }

            $path = $backupService->storeBackupFile($schedule->storage);

            $schedule->update([
                'last_run_at' => now(),
            ]);

            $this->info('Backup created: ' . $path);
        }

        return self::SUCCESS;
    }
}
