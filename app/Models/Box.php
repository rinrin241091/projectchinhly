<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//Log
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
//end log

class Box extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $fillable = ['shelf_id', 'code','description', 'type', 'page_count','record_count','status'];
    public function shelf(): BelongsTo {
        return $this->belongsTo(Shelf::class);
    }
    public function archival_records() {
        return $this->belongsToMany(ArchiveRecord::class);
    }
    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // log tất cả các trường
            ->logOnlyDirty() // chỉ log khi có thay đổi
            ->dontSubmitEmptyLogs()
            ->useLogName('Hồ sơ lưu trữ')
            ->setDescriptionForEvent(fn(string $eventName) => "Người dùng đã {$eventName} hồ sơ");
    }
}
