<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ImportLegacyZaloData extends Command
{
    protected $signature = 'legacy:import-zalo
        {--path=D:\\zalo : Folder path containing legacy data files}
        {--archive-records-file= : Absolute path to archive records file (JSON content in .csv)}
        {--fresh : Truncate related tables before import}';

    protected $description = 'Import legacy archival data files (JSON content in .csv files) into current schema in correct FK order';

    public function handle(): int
    {
        $basePath = rtrim((string) $this->option('path'), "\\/");

        if (! is_dir($basePath)) {
            $this->error('Folder not found: ' . $basePath);

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            if ((bool) $this->option('fresh')) {
                $this->truncateRelatedTables();
            }

            $archivals = $this->importArchivals($basePath . DIRECTORY_SEPARATOR . 'archivals.csv');
            $this->importStorages($basePath . DIRECTORY_SEPARATOR . 'storages.csv');
            $this->importOrganizations($basePath . DIRECTORY_SEPARATOR . 'organizations.csv');
            $this->importShelves($basePath . DIRECTORY_SEPARATOR . 'shelves.csv');
            $this->importBoxes($basePath . DIRECTORY_SEPARATOR . 'boxes.csv');
            $this->importArchiveRecordItems(
                $basePath . DIRECTORY_SEPARATOR . 'archive_records_items.csv',
                $basePath . DIRECTORY_SEPARATOR . 'archive_records.csv'
            );
            $archiveRecordsFile = (string) ($this->option('archive-records-file') ?: ($basePath . DIRECTORY_SEPARATOR . 'archive_records.csv'));
            $this->importArchiveRecords($archiveRecordsFile, $archivals > 0);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Legacy import completed.');

        return self::SUCCESS;
    }

    private function truncateRelatedTables(): void
    {
        foreach ([
            'archive_records',
            'archive_record_items',
            'boxes',
            'shelves',
            'organizations',
            'storages',
            'archivals',
        ] as $table) {
            DB::table($table)->truncate();
        }

        $this->line('Truncated related tables.');
    }

    private function importArchivals(string $file): int
    {
        $rows = $this->readJsonRows($file);

        $payload = collect($rows)->map(function (array $row) {
            return [
                'id' => (int) Arr::get($row, 'id'),
                'identifier' => trim((string) Arr::get($row, 'identifier', '')),
                'name' => trim((string) Arr::get($row, 'name', '')),
                'address' => $this->nullableText(Arr::get($row, 'address')),
                'phone' => $this->nullableText(Arr::get($row, 'phone')),
                'email' => $this->nullableText(Arr::get($row, 'email')),
                'manager' => $this->nullableText(Arr::get($row, 'manager')),
                'created_at' => $this->nullableText(Arr::get($row, 'created_at')) ?? now(),
                'updated_at' => $this->nullableText(Arr::get($row, 'updated_at')) ?? now(),
            ];
        })->filter(fn (array $row) => $row['id'] > 0 && $row['identifier'] !== '' && $row['name'] !== '')
          ->values()
          ->all();

        $this->upsert('archivals', $payload);
        $this->line('Imported archivals: ' . count($payload));

        return count($payload);
    }

    private function importStorages(string $file): void
    {
        $rows = $this->readJsonRows($file);

        $payload = collect($rows)->map(function (array $row) {
            return [
                'id' => (int) Arr::get($row, 'id'),
                'code' => trim((string) Arr::get($row, 'code', '')),
                'name' => trim((string) Arr::get($row, 'name', '')),
                'location' => $this->nullableText(Arr::get($row, 'location')),
                'archival_id' => $this->nullableInt(Arr::get($row, 'archival_id')),
                'created_at' => $this->nullableText(Arr::get($row, 'created_at')) ?? now(),
                'updated_at' => $this->nullableText(Arr::get($row, 'updated_at')) ?? now(),
            ];
        })->filter(fn (array $row) => $row['id'] > 0 && $row['code'] !== '' && $row['name'] !== '' && $row['archival_id'] !== null)
          ->values()
          ->all();

        $this->upsert('storages', $payload);
        $this->line('Imported storages: ' . count($payload));
    }

    private function importOrganizations(string $file): void
    {
        $rows = $this->readJsonRows($file);

        $payload = collect($rows)->map(function (array $row) {
            $type = $this->normalizeOrganizationType(Arr::get($row, 'type'));

            return [
                'id' => (int) Arr::get($row, 'id'),
                'code' => trim((string) Arr::get($row, 'code', '')),
                'archival_id' => $this->nullableInt(Arr::get($row, 'archival_id')),
                'name' => trim((string) Arr::get($row, 'name', '')),
                'type' => $type ?? 'Chính quyền',
                'archivals_time' => $this->nullableText(Arr::get($row, 'archivals_time')) ?? 'N/A',
                'key_groups' => json_encode(Arr::get($row, 'key_groups', []), JSON_UNESCAPED_UNICODE),
                'created_at' => $this->nullableText(Arr::get($row, 'created_at')) ?? now(),
                'updated_at' => $this->nullableText(Arr::get($row, 'updated_at')) ?? now(),
            ];
        })->filter(fn (array $row) => $row['id'] > 0 && $row['code'] !== '' && $row['name'] !== '' && $row['archival_id'] !== null)
          ->values()
          ->all();

        $this->upsert('organizations', $payload);
        $this->line('Imported organizations: ' . count($payload));
    }

    private function normalizeOrganizationType(mixed $value): ?string
    {
        $type = $this->nullableText($value);

        if ($type === null) {
            return null;
        }

        if (str_contains($type, 'Đảng')) {
            return 'Đảng';
        }

        if (str_contains($type, 'Chính quyền')) {
            return 'Chính quyền';
        }

        return $type;
    }

    private function importShelves(string $file): void
    {
        $rows = $this->readJsonRows($file);

        $payload = collect($rows)->map(function (array $row) {
            return [
                'id' => (int) Arr::get($row, 'id'),
                'storage_id' => $this->nullableInt(Arr::get($row, 'storage_id')),
                'code' => trim((string) Arr::get($row, 'code', '')),
                'description' => $this->nullableText(Arr::get($row, 'description')),
                'created_at' => $this->nullableText(Arr::get($row, 'created_at')) ?? now(),
                'updated_at' => $this->nullableText(Arr::get($row, 'updated_at')) ?? now(),
            ];
        })->filter(fn (array $row) => $row['id'] > 0 && $row['storage_id'] !== null && $row['code'] !== '')
          ->values()
          ->all();

        $this->upsert('shelves', $payload);
        $this->line('Imported shelves: ' . count($payload));
    }

    private function importBoxes(string $file): void
    {
        $rows = $this->readJsonRows($file);

        $payload = collect($rows)->map(function (array $row) {
            return [
                'id' => (int) Arr::get($row, 'id'),
                'shelf_id' => $this->nullableInt(Arr::get($row, 'shelf_id')),
                'code' => trim((string) Arr::get($row, 'code', '')),
                'description' => trim((string) Arr::get($row, 'description', '')),
                'type' => $this->nullableText(Arr::get($row, 'type')),
                'record_count' => $this->nullableInt(Arr::get($row, 'record_count')),
                'page_count' => $this->nullableInt(Arr::get($row, 'page_count')),
                'location' => $this->nullableText(Arr::get($row, 'location')),
                'status' => $this->nullableText(Arr::get($row, 'status')),
                'created_at' => $this->nullableText(Arr::get($row, 'created_at')) ?? now(),
                'updated_at' => $this->nullableText(Arr::get($row, 'updated_at')) ?? now(),
            ];
        })->filter(fn (array $row) => $row['id'] > 0 && $row['shelf_id'] !== null && $row['code'] !== '' && $row['description'] !== '')
          ->values()
          ->all();

        $this->upsert('boxes', $payload);
        $this->line('Imported boxes: ' . count($payload));
    }

    private function importArchiveRecordItems(string $primaryFile, string $fallbackFile): void
    {
        $rows = [];

        if (is_file($primaryFile)) {
            $rows = $this->readJsonRows($primaryFile);
        }

        if (count($rows) === 0 && is_file($fallbackFile)) {
            $possibleRows = $this->readJsonRows($fallbackFile);

            if ($this->isArchiveRecordItemShape($possibleRows)) {
                $rows = $possibleRows;
            }
        }

        $payload = collect($rows)->map(function (array $row) {
            return [
                'id' => (int) Arr::get($row, 'id'),
                'archive_record_item_code' => trim((string) Arr::get($row, 'archive_record_item_code', '')),
                'organization_id' => $this->nullableInt(Arr::get($row, 'organization_id')),
                'title' => trim((string) Arr::get($row, 'title', '')),
                'description' => $this->nullableText(Arr::get($row, 'description')),
                'page_num' => $this->nullableInt(Arr::get($row, 'page_num')),
                'document_date' => $this->nullableText(Arr::get($row, 'document_date')),
                'created_at' => $this->nullableText(Arr::get($row, 'created_at')) ?? now(),
                'updated_at' => $this->nullableText(Arr::get($row, 'updated_at')) ?? now(),
            ];
        })->filter(fn (array $row) => $row['id'] > 0 && $row['organization_id'] !== null && $row['archive_record_item_code'] !== '' && $row['title'] !== '')
          ->values()
          ->all();

        $this->upsert('archive_record_items', $payload);
        $this->line('Imported archive_record_items: ' . count($payload));
    }

    private function importArchiveRecords(string $file, bool $hasArchivals): void
    {
        if (! is_file($file)) {
            $this->warn('Skipped archive_records import (file not found).');

            return;
        }

        $rows = $this->readJsonRows($file);

        if (! $this->isArchiveRecordShape($rows)) {
            $this->warn('Skipped archive_records import (file shape does not match archive_records).');

            return;
        }

        $orgCodes = Organization::query()->pluck('code', 'id')->all();

        $payload = collect($rows)->map(function (array $row) use ($orgCodes, $hasArchivals) {
            $id = (int) Arr::get($row, 'id');
            $organizationId = $this->nullableInt(Arr::get($row, 'organization_id'));
            $code = $this->nullableText(Arr::get($row, 'code'));
            $startDate = $this->nullableText(Arr::get($row, 'start_date'));
            $titleAndDescription = $this->normalizeArchiveRecordTitleAndDescription(
                (string) Arr::get($row, 'title', ''),
                Arr::get($row, 'description')
            );

            $referenceCode = $this->nullableText(Arr::get($row, 'reference_code'));

            if ($referenceCode === null && $code !== null && $organizationId !== null) {
                $orgCode = $orgCodes[$organizationId] ?? 'ORG';
                $year = $startDate ? date('Y', strtotime($startDate)) : date('Y');
                $referenceCode = $orgCode . '-' . $year . '-' . $code;
            }

            return [
                'id' => $id,
                'reference_code' => $referenceCode,
                'code' => $code,
                'organization_id' => $this->nullableForeignId(Arr::get($row, 'organization_id')),
                'box_id' => $this->nullableForeignId(Arr::get($row, 'box_id')),
                'storage_id' => $this->nullableForeignId(Arr::get($row, 'storage_id')),
                'archive_record_item_id' => $this->nullableForeignId(Arr::get($row, 'archive_record_item_id')),
                'symbols_code' => $this->nullableInt(Arr::get($row, 'symbols_code')),
                'title' => $titleAndDescription['title'],
                'description' => $titleAndDescription['description'],
                'start_date' => $startDate,
                'end_date' => $this->nullableText(Arr::get($row, 'end_date')),
                'language' => $this->nullableText(Arr::get($row, 'language')),
                'handwritten' => $this->nullableText(Arr::get($row, 'handwritten')),
                'usage_mode' => $this->nullableText(Arr::get($row, 'usage_mode')),
                'record_type_id' => $this->nullableForeignId(Arr::get($row, 'record_type_id')),
                'work_area_id' => $this->nullableForeignId(Arr::get($row, 'work_area_id')),
                'department_id' => $this->nullableForeignId(Arr::get($row, 'department_id')),
                'preservation_duration' => $this->nullableText(Arr::get($row, 'preservation_duration')),
                'page_count' => $this->nullableInt(Arr::get($row, 'page_count')),
                'condition' => $this->nullableText(Arr::get($row, 'condition')),
                'note' => $this->nullableText(Arr::get($row, 'note')),
                'created_at' => $this->nullableText(Arr::get($row, 'created_at')) ?? now(),
                'updated_at' => $this->nullableText(Arr::get($row, 'updated_at')) ?? now(),
            ];
        })->filter(function (array $row) {
            return $row['id'] > 0
                && $row['organization_id'] !== null
                && ! empty($row['title'])
                && ! empty($row['reference_code']);
        })->values()->all();

        if (count($payload) === 0 && $hasArchivals) {
            $this->warn('Skipped archive_records import (no valid rows after mapping).');

            return;
        }

        $this->upsert('archive_records', $payload);
        $this->line('Imported archive_records: ' . count($payload));
    }

    /**
     * @return array{title:string,description:?string}
     */
    private function normalizeArchiveRecordTitleAndDescription(string $title, mixed $description): array
    {
        $cleanTitle = trim($title);
        $cleanDescription = $this->nullableText($description);

        if (mb_strlen($cleanTitle) <= 255) {
            return [
                'title' => $cleanTitle,
                'description' => $cleanDescription,
            ];
        }

        $truncatedTitle = trim(mb_substr($cleanTitle, 0, 255));
        $overflow = trim(mb_substr($cleanTitle, 255));
        $extra = $overflow !== '' ? '[Tiêu đề bổ sung] ' . $overflow : null;

        return [
            'title' => $truncatedTitle,
            'description' => trim(implode(' ', array_filter([$cleanDescription, $extra]))),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJsonRows(string $file): array
    {
        if (! is_file($file)) {
            $this->warn('File not found, skipping: ' . $file);

            return [];
        }

        $content = trim((string) file_get_contents($file));

        if ($content === '') {
            $this->warn('File is empty, skipping: ' . $file);

            return [];
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON content in file: ' . $file);
        }

        return array_values(array_filter($decoded, fn ($row) => is_array($row)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function isArchiveRecordItemShape(array $rows): bool
    {
        if (count($rows) === 0) {
            return false;
        }

        $first = $rows[0];

        return array_key_exists('archive_record_item_code', $first)
            && array_key_exists('organization_id', $first)
            && ! array_key_exists('reference_code', $first);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function isArchiveRecordShape(array $rows): bool
    {
        if (count($rows) === 0) {
            return false;
        }

        $first = $rows[0];

        return array_key_exists('reference_code', $first)
            || (array_key_exists('code', $first) && array_key_exists('title', $first) && array_key_exists('organization_id', $first));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function upsert(string $table, array $rows): void
    {
        if (count($rows) === 0) {
            return;
        }

        $columns = array_keys($rows[0]);

        foreach (array_chunk($rows, 150) as $batch) {
            DB::table($table)->upsert($batch, ['id'], $columns);
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function nullableForeignId(mixed $value): ?int
    {
        $id = $this->nullableInt($value);

        if ($id === null || $id <= 0) {
            return null;
        }

        return $id;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
