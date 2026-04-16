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
                           'usage_mode',
                           'status'];
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

    /**
     * Auto-update status based on document count.
     * Only updates if current status is not 'đã nhập' (manually set by teamlead).
     */
    public function autoUpdateStatus(): void
    {
        if ($this->status === 'đã nhập') {
            return;
        }

        $docCount = $this->documents()->count();
        $newStatus = $docCount > 0 ? 'đang nhập' : 'chưa nhập';

        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }

    /**
     * Sync start_date, end_date, page_count and usage_mode from child documents.
     */
    public function syncFromDocuments(): void
    {
        $documents = $this->documents()->get();

        if ($documents->isEmpty()) {
            return;
        }

        // 1. Start date = min document_date, End date = max document_date
        $dates = $documents->pluck('document_date')->filter()->map(function ($d) {
            $cleaned = trim((string) $d, '[]');
            try {
                return \Carbon\Carbon::parse($cleaned);
            } catch (\Exception $e) {
                return null;
            }
        })->filter();

        $updates = [];

        if ($dates->isNotEmpty()) {
            $updates['start_date'] = $dates->min()->format('Y-m-d');
            $updates['end_date'] = $dates->max()->format('Y-m-d');
        }

        // 2. Page count = sum of total_pages
        $updates['page_count'] = $documents->sum(fn ($doc) => (int) $doc->total_pages);

        // 3. Security level = highest among documents
        // Hierarchy (low → high): Thường → Mật → Tuyệt mật → Tối mật
        $securityRank = [
            'thường' => 0,
            'mật' => 1,
            'tuyệt mật' => 2,
            'tối mật' => 3,
        ];

        $securityDisplay = [
            0 => 'Thường',
            1 => 'Mật',
            2 => 'Tuyệt mật',
            3 => 'Tối mật',
        ];

        $highestRank = $documents
            ->pluck('security_level')
            ->filter()
            ->map(fn ($level) => $securityRank[mb_strtolower(trim($level))] ?? 0)
            ->max();

        $updates['usage_mode'] = $securityDisplay[$highestRank ?? 0];

        $this->updateQuietly($updates);
    }
}

