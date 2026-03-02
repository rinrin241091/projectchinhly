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
                             'document_code', 
                             'author', 
                             'page_number', 
                             'document_date',
                             'note'];
    public function archive_record(): BelongsTo {
    return $this->belongsTo(ArchiveRecord::class, 'archive_record_id', 'id');
}

    public function docType(): BelongsTo {
        return $this->belongsTo(\App\Models\DocType::class, 'doc_type_id');
    }
}
