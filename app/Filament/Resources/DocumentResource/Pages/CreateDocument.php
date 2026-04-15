<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure doc_type_id is set before creating the record
        if (empty($data['doc_type_id'])) {
            $firstDocType = \App\Models\DocType::orderBy('id')->first();
            if (!$firstDocType) {
                $firstDocType = \App\Models\DocType::create([
                    'name' => 'Văn bản thường',
                    'description' => 'Loại tài liệu mặc định',
                ]);
            }
            $data['doc_type_id'] = $firstDocType->id;
        }

        if (empty($data['security_level'])) {
            $data['security_level'] = 'thường';
        }

        if (! isset($data['description']) || $data['description'] === null) {
            $data['description'] = '';
        }

        // Auto-calculate STT by session, reset to 1 for a new archive record
        $archiveRecordId = $data['archive_record_id'] ?? null;
        $previousArchiveId = session('document_form_draft.archive_record_id');
        $previousStt = session('document_form_draft.stt') ?? 0;

        if ($archiveRecordId) {
            if ($archiveRecordId === $previousArchiveId) {
                $data['stt'] = (int) $previousStt + 1;
            } else {
                $data['stt'] = 1;
            }
        }

        // Merge page_number_from and page_number_to
        $pageFrom = isset($data['page_number_from']) ? trim($data['page_number_from']) : null;
        $pageTo = isset($data['page_number_to']) ? trim($data['page_number_to']) : null;
        $hasPageFrom = $pageFrom !== null && $pageFrom !== '';
        $hasPageTo = $pageTo !== null && $pageTo !== '';

        if ($hasPageFrom) {
            $data['page_number_from'] = $pageFrom;
        }

        if ($hasPageTo) {
            $data['page_number_to'] = $pageTo;
        }

        if ($hasPageFrom && $hasPageTo) {
            $data['page_number'] = $pageFrom . '-' . $pageTo;
        } elseif ($hasPageFrom) {
            $data['page_number'] = $pageFrom;
        } elseif ($hasPageTo) {
            $data['page_number'] = $pageTo;
        }

        // Keep raw date in DB; use date_unverified flag for display.
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        session(['document_form_draft' => [
            'archive_record_id' => $record->archive_record_id,
            'doc_type_id' => $record->doc_type_id,
            'stt' => $record->stt,
            'document_code' => $record->document_code,
            'document_date' => $record->document_date,
            'date_unverified' => $record->date_unverified,
            'issuing_agency' => $record->issuing_agency,
            'description' => $record->description,
            'signer' => $record->signer,
            'author' => $record->author,
            'security_level' => $record->security_level ?: 'thường',
            'copy_type' => $record->copy_type,
            'page_number' => $record->page_number,
            'page_number_from' => $record->page_number_from,
            'page_number_to' => $record->page_number_to,
            'total_pages' => $record->total_pages,
            'file_count' => $record->file_count,
            'file_name' => $record->file_name,
            'keywords' => $record->keywords,
            'note' => $record->note,
        ]]);
    }
}

