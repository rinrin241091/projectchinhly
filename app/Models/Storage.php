<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Storage extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'location','code','archival_id'];
    public function shelves(): HasMany {
        return $this->hasMany(Shelf::class);
    }
    public function archival():BelongsTo
    {
        return $this->belongsTo(related: Archival::class);
    }

}
