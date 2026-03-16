<?php

namespace App\Models;

use App\Models\ArchiveRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Borrowing extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'archive_record_id',
        'user_id',
        'borrowed_at',
        'due_at',
        'returned_at',
        'return_requested_at',
        'purpose',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_note',
        'due_soon_notified_at',
        'overdue_notified_at',
    ];

    protected $casts = [
        'borrowed_at' => 'date',
        'due_at' => 'date',
        'returned_at' => 'date',
        'return_requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'due_soon_notified_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
    ];

    public function archiveRecord(): BelongsTo
    {
        return $this->belongsTo(ArchiveRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Mượn trả hồ sơ')
            ->setDescriptionForEvent(function (string $eventName): string {
                $recordName = $this->archiveRecord?->title
                    ?: ($this->archiveRecord?->reference_code ?: ('#' . $this->archive_record_id));

                return "Người dùng đã {$eventName} mượn trả hồ sơ {$recordName}";
            });
    }
}
