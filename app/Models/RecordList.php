<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordList extends Model
{
    use HasFactory;
    protected $fillable = ['list_code', 'name', 'description'];
    public function records() {
        return $this->belongsToMany(Record::class);
    }
    
}
