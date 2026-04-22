<?php

namespace App\Imports;

use App\Models\Document;
use App\Models\ArchiveRecord;
use App\Models\DocType;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DocumentsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithCalculatedFormulas
{
    protected $organizationId;
    protected $defaultArchiveRecordId;
    protected $headingRowNumber;
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $skipReasons = [];
    protected $currentRow = 0;
    protected $detectedArchiveRecordId = null;

    /**
     * Map Vietnamese headers (from export) to internal keys.
     * Both old CSV-style English keys and Vietnamese export headers are supported.
     */
    private const HEADER_MAP = [
        // ── Vietnamese: Normal org format ──
        'so_ky_hieu'                        => 'document_code',
        'ngay_thang_van_ban'                => 'document_date',
        'trich_yeu_noi_dung_van_ban'        => 'description',
        'tac_gia_van_ban'                   => 'author',
        'to_so'                             => 'page_number',
        'ghi_chu'                           => 'note',
        // ── Vietnamese: Party (Đảng) format ──
        'so_tt'                             => 'stt',
        'ngay_thang_nam_van_ban'            => 'document_date',
        'trich_yeu'                         => 'description',
        'tac_gia'                           => 'issuing_agency',
        'nguoi_ky'                          => 'signer',
        'do_mat'                            => 'security_level',
        'loai_ban'                          => 'copy_type',
        'trang_so'                          => 'page_number',
        'so_trang'                          => 'total_pages',
        'tu_khoa'                           => 'keywords',
        'so_luong_tep_file'                 => 'file_count',
        'ten_tep_tai_lieu'                  => 'file_name',
        // ── Extra columns for import ──
        'ma_ho_so'                          => 'archive_record_reference',
        'loai_van_ban'                      => 'doc_type_name',
        // ── English headers (CSV backward compatible) ──
        'document_code'                     => 'document_code',
        'document_date'                     => 'document_date',
        'description'                       => 'description',
        'author'                            => 'author',
        'signer'                            => 'signer',
        'page_number'                       => 'page_number',
        'note'                              => 'note',
        'archive_record_reference'          => 'archive_record_reference',
        'doc_type_name'                     => 'doc_type_name',
        'security_level'                    => 'security_level',
        'copy_type'                         => 'copy_type',
        'total_pages'                       => 'total_pages',
        'keywords'                          => 'keywords',
        'file_count'                        => 'file_count',
        'file_name'                         => 'file_name',
        'issuing_agency'                    => 'issuing_agency',
    ];

    public function __construct($organizationId, $defaultArchiveRecordId = null, $headingRowNumber = 1)
    {
        $this->organizationId = $organizationId;
        $this->defaultArchiveRecordId = $defaultArchiveRecordId;
        $this->headingRowNumber = $headingRowNumber;
    }

    /**
     * Tell Maatwebsite which row contains the column headers.
     */
    public function headingRow(): int
    {
        return $this->headingRowNumber;
    }

    /**
     * Pre-scan the file to detect heading row and extract metadata (Mã hồ sơ).
     * Returns [headingRow, detectedArchiveRecordId].
     */
    public static function detectFormat(string $filePath, int $organizationId): array
    {
        $headingRow = 1;
        $detectedRecordId = null;

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Check first 5 rows to find the header
            for ($row = 1; $row <= min(5, $sheet->getHighestRow()); $row++) {
                $cellA = trim((string) $sheet->getCell("A{$row}")->getValue());
                $cellB = trim((string) $sheet->getCell("B{$row}")->getValue());

                // Detect "Mã hồ sơ: XXX" metadata row
                if (str_starts_with($cellA, 'Mã hồ sơ:')) {
                    $code = trim(str_replace('Mã hồ sơ:', '', $cellA));
                    $record = ArchiveRecord::where('organization_id', $organizationId)
                        ->where(function ($q) use ($code) {
                            $q->where('code', $code)
                              ->orWhere('reference_code', $code)
                              ->orWhere('id', is_numeric($code) ? (int) $code : 0);
                        })->first();
                    if ($record) {
                        $detectedRecordId = $record->id;
                    }
                }

                // Detect header row by known column names
                $cellALower = mb_strtolower($cellA);
                $cellBLower = mb_strtolower($cellB);
                if (
                    str_contains($cellALower, 'số tt') ||
                    str_contains($cellALower, 'số, ký hiệu') ||
                    str_contains($cellALower, 'ký hiệu') ||
                    str_contains($cellALower, 'document_code') ||
                    str_contains($cellBLower, 'số, ký hiệu') ||
                    str_contains($cellBLower, 'ký hiệu')
                ) {
                    $headingRow = $row;
                    break;
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Exception $e) {
            // If detection fails, default to row 1
            \Illuminate\Support\Facades\Log::warning("Format detection failed: " . $e->getMessage());
        }

        return [$headingRow, $detectedRecordId];
    }

    /**
     * Normalize row keys: map Vietnamese/English headers to internal keys.
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $cleanKey = trim(mb_strtolower((string) $key));
            // Replace spaces, commas, dots, diacritics-related chars with underscores
            $cleanKey = preg_replace('/[\s,\.()\/]+/u', '_', $cleanKey);
            $cleanKey = preg_replace('/_+/', '_', $cleanKey);
            $cleanKey = trim($cleanKey, '_');

            if (isset(self::HEADER_MAP[$cleanKey])) {
                $internalKey = self::HEADER_MAP[$cleanKey];
                if (!isset($normalized[$internalKey])) {
                    $normalized[$internalKey] = $value;
                }
            } else {
                // Try without diacritics
                $asciiKey = $this->removeDiacritics($cleanKey);
                if (isset(self::HEADER_MAP[$asciiKey])) {
                    $internalKey = self::HEADER_MAP[$asciiKey];
                    if (!isset($normalized[$internalKey])) {
                        $normalized[$internalKey] = $value;
                    }
                } else {
                    $normalized[$cleanKey] = $value;
                }
            }
        }

        return $normalized;
    }

    private function removeDiacritics(string $str): string
    {
        $map = [
            'á'=>'a','à'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a','â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
            'đ'=>'d',
            'é'=>'e','è'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
            'í'=>'i','ì'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
            'ó'=>'o','ò'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
            'ú'=>'u','ù'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
            'ý'=>'y','ỳ'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
        ];
        return strtr($str, $map);
    }

    public function collection(Collection $rows)
    {
        // Phase 1: Parse all rows into prepared document data, grouped by archive_record_id
        $grouped = []; // archive_record_id => [prepared documents]

        foreach ($rows as $row) {
            $this->currentRow++;
            $row = $this->normalizeRow($row->toArray());

            // Skip empty/example rows
            if (empty($row['document_code'] ?? null) && empty($row['description'] ?? null)) {
                continue;
            }

            // Determine archive record
            $archiveRecordRef = $row['archive_record_reference'] ?? null;
            $archiveRecord = null;

            if ($archiveRecordRef) {
                $archiveRecord = ArchiveRecord::where('organization_id', $this->organizationId)
                    ->where(function ($query) use ($archiveRecordRef) {
                        $query->where('id', $archiveRecordRef)
                              ->orWhere('reference_code', $archiveRecordRef)
                              ->orWhere('code', $archiveRecordRef);
                    })
                    ->first();
                if (!$archiveRecord) {
                    $this->addSkipReason("Không tìm thấy hồ sơ có mã \"{$archiveRecordRef}\"");
                    continue;
                }
            } elseif ($this->detectedArchiveRecordId) {
                $archiveRecord = ArchiveRecord::find($this->detectedArchiveRecordId);
            } elseif ($this->defaultArchiveRecordId) {
                $archiveRecord = ArchiveRecord::find($this->defaultArchiveRecordId);
            }

            if (!$archiveRecord) {
                $this->addSkipReason('Thiếu Mã hồ sơ và chưa chọn hồ sơ mặc định');
                continue;
            }

            // Find or create doc type
            $docTypeName = $row['doc_type_name'] ?? null;
            if ($docTypeName) {
                $docType = DocType::firstOrCreate(['name' => $docTypeName]);
            } else {
                $docType = DocType::first();
            }

            // Parse page number
            $pageNumberRaw = $row['page_number'] ?? null;
            $pageNumberFrom = null;
            $pageNumberTo = null;

            if ($pageNumberRaw !== null) {
                $pageNumberRaw = trim((string) $pageNumberRaw);
                if (preg_match('/(\d+)\s*-\s*(\d+)/', $pageNumberRaw, $matches)) {
                    $pageNumberFrom = $matches[1];
                    $pageNumberTo = $matches[2];
                } elseif (is_numeric($pageNumberRaw)) {
                    $pageNumberFrom = $pageNumberRaw;
                }
            }

            // Parse date
            $documentDate = null;
            $dateRaw = $row['document_date'] ?? null;
            if ($dateRaw) {
                $documentDate = $this->parseDate($dateRaw);
            }

            $prepared = [
                'document_code'     => $this->cleanString($row['document_code'] ?? ''),
                'document_date'     => $documentDate,
                'description'       => $this->cleanString($row['description'] ?? ''),
                'signer'            => $this->cleanString($row['signer'] ?? null),
                'author'            => $this->cleanString($row['author'] ?? null),
                'issuing_agency'    => $this->cleanString($row['issuing_agency'] ?? null),
                'page_number'       => $pageNumberRaw,
                'page_number_from'  => $this->cleanInt($pageNumberFrom),
                'page_number_to'    => $this->cleanInt($pageNumberTo),
                'total_pages'       => $this->cleanInt($row['total_pages'] ?? null),
                'security_level'    => $this->normalizeOption($row['security_level'] ?? null, ['Thường', 'Mật', 'Tuyệt mật', 'Tối mật']),
                'copy_type'         => $this->normalizeOption($row['copy_type'] ?? null, ['Bản chính', 'Bản sao']),
                'keywords'          => $this->cleanString($row['keywords'] ?? null),
                'file_count'        => $this->cleanInt($row['file_count'] ?? null),
                'file_name'         => $this->cleanString($row['file_name'] ?? null),
                'note'              => $this->cleanString($row['note'] ?? null),
                'archive_record_id' => $archiveRecord->id,
                'doc_type_id'       => $docType?->id,
            ];

            $grouped[$archiveRecord->id][] = $prepared;
        }

        // Phase 2: For each archive record, sort by page_number_from and assign stt
        foreach ($grouped as $archiveRecordId => $docs) {
            // Get current max stt for this archive record
            $maxStt = (int) Document::where('archive_record_id', $archiveRecordId)->max('stt');

            // Sort by page_number_from ascending
            usort($docs, function ($a, $b) {
                $aVal = is_numeric($a['page_number_from']) ? (int) $a['page_number_from'] : PHP_INT_MAX;
                $bVal = is_numeric($b['page_number_from']) ? (int) $b['page_number_from'] : PHP_INT_MAX;
                return $aVal <=> $bVal;
            });

            // Create documents with correct stt
            foreach ($docs as $docData) {
                $maxStt++;
                $docData['stt'] = $maxStt;
                Document::create($docData);
                $this->importedCount++;
            }
        }
    }

    /**
     * Clean a value for integer columns - strip Excel formulas and non-numeric chars.
     */
    private function cleanInt($value): ?int
    {
        if ($value === null || $value === '') return null;
        $str = trim((string) $value);
        // Skip Excel formulas like =J6, =SUM(...)
        if (str_starts_with($str, '=')) return null;
        // Extract numeric value
        if (is_numeric($str)) return (int) $str;
        // Try to extract first number
        if (preg_match('/(\d+)/', $str, $m)) return (int) $m[1];
        return null;
    }

    /**
     * Clean a string value - strip Excel formulas.
     */
    private function cleanString($value): ?string
    {
        if ($value === null || $value === '') return null;
        $str = trim((string) $value);
        if (str_starts_with($str, '=')) return null;
        return $str;
    }

    /**
     * Normalize a value to match one of the allowed options (case-insensitive).
     */
    private function normalizeOption($value, array $options): ?string
    {
        if ($value === null || $value === '') return null;
        $str = trim((string) $value);
        if (str_starts_with($str, '=')) return null;
        // Exact match first
        if (in_array($str, $options, true)) return $str;
        // Case-insensitive match
        foreach ($options as $option) {
            if (mb_strtolower($str) === mb_strtolower($option)) {
                return $option;
            }
        }
        return $str;
    }

    /**
     * Parse date from various formats (d/m/Y, m/d/Y, Y-m-d, Excel serial).
     */
    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim((string) $value);

        // Remove brackets from unverified dates like [01/02/2024]
        $value = trim($value, '[]');

        // Excel serial date number
        if (is_numeric($value) && (int) $value > 30000) {
            try {
                return Carbon::createFromTimestamp(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((int) $value)
                )->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }

        // Try d/m/Y (Vietnamese standard)
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12) {
                return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
            }
        }

        // Try Y-m-d
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Không parse được ngày: {$value}");
            return null;
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getSkipReasons(): array
    {
        return $this->skipReasons;
    }

    public function setDetectedArchiveRecordId(?int $id): void
    {
        $this->detectedArchiveRecordId = $id;
    }

    private function addSkipReason(string $reason): void
    {
        $this->skippedCount++;
        $this->skipReasons[] = "Dòng {$this->currentRow}: {$reason}";
        Log::warning("Import skip - Dòng {$this->currentRow}: {$reason}");
    }
}
