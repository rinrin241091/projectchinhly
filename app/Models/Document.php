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
}
