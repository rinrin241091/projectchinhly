<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ArchiveRecordItem;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ArchiveRecord extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $fillable = ['reference_code',
                           'code',
                           'organization_id',
                           'storage_id',
                           'box_id',
                           'archive_record_item_id',
                           'title',
                           'start_date',
                           'end_date',
                           'record_type_id',
                           'work_area_id',
                           'department_id',
                           'preservation_duration',
                           'page_count',
                           'condition',
                           'note',
                           'symbols_code',
                           'description',
                           'language',
                           'handwritten',
                           'usage_mode'];
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }
    public function archiveRecordItem()
    {
        return $this->belongsTo(ArchiveRecordItem::class);
    }
    public function box(): BelongsTo {
        return $this->belongsTo(Box::class);
    }
    public function storage(): BelongsTo {
        return $this->belongsTo(Storage::class);
    }

    public function recordType(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_id');
    }
    
    public function documents(): HasMany {
        return $this->hasMany(Document::class, 'archive_record_id');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'archive_record_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Hồ sơ lưu trữ')
            ->setDescriptionForEvent(function (string $eventName): string {
                $recordName = $this->title ?: ($this->code ?: ('#' . $this->getKey()));

                return "Người dùng đã {$eventName} hồ sơ {$recordName}";
            });
    }

    protected static function booted()
    {
        static::saving(function ($record) {
            $record->reference_code = static::buildReferenceCode($record);

            if (empty($record->reference_code)) {
                return;
            }

            $duplicateExists = static::query()
                ->where('reference_code', $record->reference_code)
                ->when($record->exists, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'code' => 'Mã tham chiếu đã tồn tại. Vui lòng nhập số hồ sơ khác.',
                ]);
            }
        });
    }

    private static function buildReferenceCode(self $record): ?string
    {
        if (empty($record->code)) {
            return null;
        }

        $organization = $record->organization
            ?? ($record->organization_id ? Organization::find($record->organization_id) : null)
            ?? $record->archiveRecordItem?->organization;

        $orgCode = $organization?->code ?? 'ORG';
        $year = date('Y', strtotime($record->start_date ?? now()));
        $userCode = trim((string) $record->code);

        return "{$orgCode}-{$year}-{$userCode}";
    }
}

