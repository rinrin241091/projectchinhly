<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexCard extends Model
{
    use HasFactory;
    protected $fillable = ['record_id', 'card_code', 'title', 'content'];
    public function record(): BelongsTo {
        return $this->belongsTo(Record::class);
    }
}
