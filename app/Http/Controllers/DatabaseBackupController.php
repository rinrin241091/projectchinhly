<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function downloadLatest(): BinaryFileResponse
    {
        $this->ensureAdmin();

        $directory = storage_path('app/backups/database');
        File::ensureDirectoryExists($directory);

        $latest = collect(File::files($directory))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'sql')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->first();

        if (! $latest) {
            return $this->downloadFresh();
        }

        return response()->download($latest->getPathname(), $latest->getFilename(), [
            'Content-Type' => 'application/sql; charset=UTF-8',
        ]);
    }

    public function downloadFresh(): BinaryFileResponse
    {
        $this->ensureAdmin();

        $database = (string) config('database.connections.' . config('database.default') . '.database', 'database');
        $filename = $database . '_full_' . now()->format('Ymd_His') . '.sql';
        $fullPath = storage_path('app/backups/database/' . $filename);

        $exitCode = Artisan::call('db:backup-sql', [
            '--output' => $fullPath,
        ]);

        if ($exitCode !== 0 || ! File::exists($fullPath) || File::size($fullPath) === 0) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Không thể tạo file sao lưu SQL.');
        }

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
        ]);
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'super_admin') {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}
