<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Organization extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'name','type','archival_id','archivals_time','key_groups'];
    protected $casts = [
        'key_groups' => 'array',
    ];
    public function departments(): HasMany {
        return $this->hasMany(Department::class);
    }
    public function archival():BelongsTo
    {
        return $this->belongsTo(related: Archival::class);
    }
    public function archivalrecords(): HasMany {
        return $this->hasMany(ArchiveRecord::class);
    }
    
    /**
     * Users assigned to this organization.
     */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class);
    }
}
