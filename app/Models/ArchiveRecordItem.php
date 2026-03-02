<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveRecordItem extends Model
{
    use HasFactory;
    protected $fillable = ['archive_record_item_code','organization_id', 'title','description', 'document_date'];

    public function organization():BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function records()
    {
        return $this->hasMany(ArchiveRecord::class);
    }
}
