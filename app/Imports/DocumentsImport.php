<?php

namespace App\Imports;

use App\Models\Document;
use App\Models\ArchiveRecord;
use App\Models\DocType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class DocumentsImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $organizationId;

    public function __construct($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    public function model(array $row)
    {
        // Log the incoming row data for debugging
        Log::info('Importing row data: ', $row);
        
        // Tìm archive record theo reference_code
        $archiveRecord = ArchiveRecord::where('id', $row['archive_record_reference'])
            ->where('organization_id', $this->organizationId)
            ->first();

        if (!$archiveRecord) {
            Log::warning("Không tìm thấy hồ sơ: " . $row['archive_record_reference']);
            return null;
        }

        // Tìm hoặc tạo doc type
        $docType = DocType::firstOrCreate(
            ['name' => $row['doc_type_name']],
            ['name' => $row['doc_type_name']]
        );

        return new Document([
            'document_code' => $row['document_code'],
            'document_date' => isset($row['document_date']) ? \Carbon\Carbon::createFromFormat('m/d/Y', $row['document_date'])->format('Y-m-d') : null,
            'description' => $row['description'],
            'signer' => $row['signer'] ?? null,
            'author' => $row['author'] ?? null,
            'page_number' => $row['page_number'] ?? null,
            'note' => $row['note'] ?? null,
            'archive_record_id' => $archiveRecord->id,
            'doc_type_id' => $docType->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'document_code' => 'required|string|max:255',
            'document_date' => 'nullable|date',
            'description' => 'required|string',
            'signer' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'page_number' => 'nullable|integer|min:0',
            'note' => 'nullable|string',
            'archive_record_reference' => 'required|integer|min:1',
            'doc_type_name' => 'required|string|max:255',
        ];
    }
}
