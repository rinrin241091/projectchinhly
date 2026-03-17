<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisasterSyncSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_ip',
        'sync_time',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'sync_time' => 'datetime:H:i:s',
    ];
}
