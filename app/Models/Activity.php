<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends SpatieActivity
{
    // Gắn với bảng activity_log (mặc định Spatie đã dùng tên này)
    protected $table = 'activity_log';

    // Cho phép fill các cột cụ thể nếu muốn
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'event',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
    ];

    /**
     * Quan hệ đến User (người thực hiện hành động)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Trả về đối tượng bị tác động (có thể là bất kỳ model nào)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Hiển thị thuộc tính (properties) ở dạng mảng.
     */
    protected $casts = [
        'properties' => 'collection',
    ];
}