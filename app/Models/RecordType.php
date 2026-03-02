<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordType extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'name', 'description', 'retention_schedule_id'];
    public function retentionSchedule(): BelongsTo {
        return $this->belongsTo(RetentionSchedule::class);
    }
}
