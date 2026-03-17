<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageBackupSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_id',
        'backup_time',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'backup_time' => 'datetime:H:i:s',
    ];

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}
