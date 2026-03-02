<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ArchiveRecordItem;

class ArchiveRecord extends Model
{
    use HasFactory;
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
    
    public function documents(): HasMany {
        return $this->hasMany(Document::class, 'archive_record_id');
    }
    protected static function booted()
    {
        static::creating(function ($record) {
            \Log::info('archive_record_item_id:', [$record->archive_record_item_id]);

            if (!$record->archive_record_item_id) {
                \Log::warning('archive_record_item_id is null!');
                return;
            }

            $item = $record->archiveRecordItem;
            if (!$item) {
                \Log::warning('archiveRecordItem not found!');
                return;
            }

            $orgCode = $item->organization?->code ?? 'ORG';
            $year = date('Y', strtotime($record->start_date ?? now()));

            // Lấy giá trị code do người dùng nhập trong form
            $userCode = $record->code ?? '0000';

            $record->reference_code = "{$orgCode}-{$year}-{$userCode}";
        });
        
        static::updating(function ($record) {
            // Nếu code thay đổi, cập nhật lại reference_code
            if ($record->isDirty('code')) {
                $item = $record->archiveRecordItem;
                if (!$item) {
                    return;
                }

                $orgCode = $item->organization?->code ?? 'ORG';
                $year = date('Y', strtotime($record->start_date ?? now()));
                $userCode = $record->code ?? '0000';

                $record->reference_code = "{$orgCode}-{$year}-{$userCode}";
            }
        });
    }
}

