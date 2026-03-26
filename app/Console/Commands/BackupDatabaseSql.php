<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseSql extends Command
{
    protected $signature = 'db:backup-sql
        {--output= : Absolute output file path (.sql)}
        {--prune-days=14 : Delete local SQL backups older than N days}';

    protected $description = 'Create full MySQL SQL backup (schema + data) using mysqldump';

    public function handle(): int
    {
        $connection = config('database.default');
        $db = (array) config("database.connections.{$connection}");

        if (($db['driver'] ?? null) !== 'mysql') {
            $this->error('This command currently supports MySQL only.');

            return self::FAILURE;
        }

        $database = (string) ($db['database'] ?? '');
        if ($database === '') {
            $this->error('Database name is empty. Check DB_DATABASE in .env');

            return self::FAILURE;
        }

        $dumpBinary = $this->resolveMysqldumpBinary();
        if ($dumpBinary === null) {
            $this->error('mysqldump not found. Set DB_DUMP_BINARY in .env or install MySQL client tools.');

            return self::FAILURE;
        }

        $outputPath = $this->resolveOutputPath($database);
        File::ensureDirectoryExists(dirname($outputPath));

        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (string) ($db['port'] ?? '3306');
        $username = (string) ($db['username'] ?? 'root');
        $password = (string) ($db['password'] ?? '');

        $this->line('Creating SQL backup...');

        $command = [
            $dumpBinary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--skip-extended-insert',
            '--add-drop-database',
            '--databases',
            $database,
            '--default-character-set=utf8mb4',
            '--result-file=' . $outputPath,
            '-h' . $host,
            '-P' . $port,
            '-u' . $username,
        ];

        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $process = new Process($command, null, null, null, 600);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($outputPath) || File::size($outputPath) === 0) {
            $this->error('Backup failed.');

            $error = trim($process->getErrorOutput() ?: $process->getOutput());
            if ($error !== '') {
                $this->line($error);
            }

            return self::FAILURE;
        }

        $this->pruneOldBackups((int) $this->option('prune-days'));

        $this->info('Backup created: ' . $outputPath);
        $this->line('Restore command:');
        $this->line('  mysql -u ' . $username . ' -p ' . $database . ' < "' . $outputPath . '"');

        return self::SUCCESS;
    }

    private function resolveMysqldumpBinary(): ?string
    {
        $fromEnv = env('DB_DUMP_BINARY');
        if (is_string($fromEnv) && $fromEnv !== '' && File::exists($fromEnv)) {
            return $fromEnv;
        }

        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysqldump') {
                return $candidate;
            }

            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveOutputPath(string $database): string
    {
        $option = $this->option('output');
        if (is_string($option) && trim($option) !== '') {
            return $option;
        }

        $timestamp = now()->format('Ymd_His');

        return storage_path('app/backups/database/' . $database . '_full_' . $timestamp . '.sql');
    }

    private function pruneOldBackups(int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $dir = storage_path('app/backups/database');
        if (! File::isDirectory($dir)) {
            return;
        }

        $cutoff = now()->subDays($days)->getTimestamp();

        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'sql') {
                continue;
            }

            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }
}
