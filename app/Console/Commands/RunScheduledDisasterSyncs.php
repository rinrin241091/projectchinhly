<?php

namespace App\Console\Commands;

use App\Models\DisasterSyncSchedule;
use App\Services\DisasterSyncService;
use Illuminate\Console\Command;

class RunScheduledDisasterSyncs extends Command
{
    protected $signature = 'disaster-sync:run-scheduled';

    protected $description = 'Run simulated export/import sync jobs to disaster recovery system';

    public function handle(DisasterSyncService $disasterSyncService): int
    {
        $currentTime = now()->format('H:i:00');

        $schedules = DisasterSyncSchedule::query()
            ->where('is_active', true)
            ->where('sync_time', $currentTime)
            ->get();

        if ($schedules->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($schedules as $schedule) {
            if ($schedule->last_run_at && $schedule->last_run_at->isToday()) {
                continue;
            }

            $path = $disasterSyncService->simulate($schedule);

            $schedule->update([
                'last_run_at' => now(),
            ]);

            $this->info('Disaster sync simulated: ' . $path);
        }

        return self::SUCCESS;
    }
}
