<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use App\Services\StorageBackupService;
use Illuminate\Http\Response;

class StorageBackupController extends Controller
{
    public function download(Storage $storage, StorageBackupService $backupService): Response
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'super_admin', 403);

        $payload = $backupService->buildPayload($storage, $user);
        $fileName = $backupService->makeFileName($storage);

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            },
            $fileName,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }
}
