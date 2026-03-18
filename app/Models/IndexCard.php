<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndexCard extends Model
{
    use HasFactory;
    protected $fillable = ['record_id', 'card_code', 'title', 'content'];
    public function record(): BelongsTo {
        return $this->belongsTo(ArchiveRecord::class);
    }
}
