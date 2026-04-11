<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;
    protected $fillable = ['archive_record_id',
                            'stt',
                            'doc_type_id',
                            'record_id',
                             'description',
                             'document_number',
                             'document_symbol',
                             'document_code', 
                             'issuing_agency',
                             'signer',
                             'author', 
                             'security_level',
                             'copy_type',
                             'page_number', 
                             'total_pages',
                             'file_count',
                             'file_name',
                             'document_duration',
                             'usage_mode',
                             'keywords',
                             'language',
                             'handwritten',
                             'topic',
                             'information_code',
                             'reliability_level',
                             'physical_condition',
                             'document_date',
                             'note'];
    public function archive_record(): BelongsTo {
    return $this->belongsTo(ArchiveRecord::class, 'archive_record_id', 'id');
}

    public function docType(): BelongsTo {
        return $this->belongsTo(\App\Models\DocType::class, 'doc_type_id');
    }

    public function getQrTextPayload(): string
    {
        $record = $this->archive_record;
        $box = $record?->box;
        $shelf = $box?->shelf;

        $documentName = $this->limitQrText(trim((string) ($this->description ?: $this->document_code ?: $this->document_number ?: ('Tài liệu #' . $this->id))), 80);
        $documentSymbol = $this->limitQrText(trim((string) ($this->document_code ?: $this->document_symbol ?: $this->document_number ?: '-')), 40);
        $recordName = $this->limitQrText(trim((string) ($record?->title ?: $record?->code ?: ($record?->reference_code ?: '-'))), 60);
        $boxName = $this->limitQrText(trim((string) ($box?->description ?: $box?->code ?: '-')), 40);
        $shelfName = $this->limitQrText(trim((string) ($shelf?->name ?: $shelf?->description ?: $shelf?->code ?: '-')), 40);

        return implode("\n", [
            '- Tên tài liệu: ' . $documentName,
            '- Số ký hiệu VB: ' . $documentSymbol,
            '- Tên hồ sơ: ' . $recordName,
            '- Tên hộp: ' . $boxName,
            '- Tên kệ: ' . $shelfName,
        ]);
    }

    private function limitQrText(string $value, int $maxLength): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $maxLength - 3)) . '...';
    }
}
