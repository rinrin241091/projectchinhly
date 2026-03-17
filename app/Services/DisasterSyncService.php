<?php

namespace App\Services;

use App\Models\DisasterSyncSchedule;
use Illuminate\Support\Facades\Storage;

class DisasterSyncService
{
    public function simulate(DisasterSyncSchedule $schedule): string
    {
        $payload = [
            'meta' => [
                'simulated_at' => now()->toDateTimeString(),
                'target_ip' => $schedule->target_ip,
                'message' => 'Gia lap xuat va nhap du lieu sang he thong du phong',
            ],
            'status' => 'success',
        ];

        $path = 'backups/disaster-sync/sync-' . $schedule->id . '-' . now()->format('Ymd_His') . '.json';

        Storage::disk('local')->put(
            $path,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $path;
    }
}
