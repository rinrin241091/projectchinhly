<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Shelf extends Model
{
    use HasFactory;
    protected $fillable = ['storage_id', 'code', 'description', 'name', 'location'];
    public function storage(): BelongsTo {
        return $this->belongsTo(Storage::class);
    }
    public function boxes(): HasMany {
        return $this->hasMany(Box::class);
    }
    public function archival()
    {
        return $this->hasOneThrough(Archival::class, Storage::class, 'id', 'id', 'storage_id', 'archival_id');
    }
}
