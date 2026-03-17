<?php

namespace App\Services;

use App\Models\ArchiveRecord;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Storage as StorageFacade;

class StorageBackupService
{
    public function buildPayload(Storage $storage, ?User $actor = null): array
    {
        $storage->load([
            'archival:id,name,code',
            'shelves:id,storage_id,name,code,description,location,created_at,updated_at',
            'shelves.boxes:id,shelf_id,storage_id,code,description,type,page_count,record_count,status,created_at,updated_at',
        ]);

        $archiveRecords = ArchiveRecord::query()
            ->where('storage_id', $storage->id)
            ->with([
                'organization:id,name,code',
                'recordType:id,code,name',
                'archiveRecordItem:id,title,archive_record_item_code',
                'box:id,code,description,shelf_id',
                'documents:id,archive_record_id,doc_type_id,description,document_number,document_symbol,document_code,issuing_agency,signer,author,security_level,copy_type,page_number,total_pages,file_count,file_name,document_duration,usage_mode,keywords,language,handwritten,topic,information_code,reliability_level,physical_condition,document_date,note,created_at,updated_at',
            ])
            ->get();

        return [
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
                'generated_by' => $actor
                    ? [
                        'id' => $actor->id,
                        'name' => $actor->name,
                        'email' => $actor->email,
                    ]
                    : [
                        'id' => null,
                        'name' => 'system',
                        'email' => null,
                    ],
                'storage_id' => $storage->id,
                'storage_name' => $storage->name,
            ],
            'storage' => $storage,
            'archive_records' => $archiveRecords,
        ];
    }

    public function makeFileName(Storage $storage): string
    {
        return 'sao-luu-kho-' . $storage->id . '-' . now()->format('Ymd_His') . '.json';
    }

    public function storeBackupFile(Storage $storage, ?User $actor = null): string
    {
        $payload = $this->buildPayload($storage, $actor);
        $fileName = $this->makeFileName($storage);
        $path = 'backups/storages/' . $fileName;

        StorageFacade::disk('local')->put(
            $path,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $path;
    }
}
