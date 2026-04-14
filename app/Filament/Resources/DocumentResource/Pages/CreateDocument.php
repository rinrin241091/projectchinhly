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

        // Auto-calculate STT from DB
        $archiveRecordId = $data['archive_record_id'] ?? null;
        if ($archiveRecordId) {
            $currentMaxStt = \App\Models\Document::query()
                ->where('archive_record_id', $archiveRecordId)
                ->max('stt') ?: 0;
            $data['stt'] = (int) $currentMaxStt + 1;
        }

        // Merge page_number_from and page_number_to
        $pageFrom = $data['page_number_from'] ?? null;
        $pageTo = $data['page_number_to'] ?? null;

        if ($pageFrom && $pageTo) {
            $data['page_number'] = $pageFrom . '-' . $pageTo;
        } elseif ($pageFrom) {
            $data['page_number'] = $pageFrom;
        } elseif ($pageTo) {
            $data['page_number'] = $pageTo;
        }

        unset($data['page_number_from'], $data['page_number_to']);

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
            'issuing_agency' => $record->issuing_agency,
            'description' => $record->description,
            'signer' => $record->signer,
            'author' => $record->author,
            'security_level' => $record->security_level ?: 'thường',
            'copy_type' => $record->copy_type,
            'page_number' => $record->page_number,
            'total_pages' => $record->total_pages,
            'file_count' => $record->file_count,
            'file_name' => $record->file_name,
            'keywords' => $record->keywords,
            'note' => $record->note,
        ]]);
    }
}

