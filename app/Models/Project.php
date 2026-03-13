<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'target_meters',
        'target_pages',
        'team_lead_id',
    ];

    protected $casts = [
        'target_meters' => 'decimal:2',
        'target_pages' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $project) {
            $project->target_pages = max(0, (int) round(((float) $project->target_meters) * 1000));
        });
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function getCompletedPagesAttribute(): int
    {
        return (int) Document::query()
            ->join('archive_records', 'archive_records.id', '=', 'documents.archive_record_id')
            ->join('organization_project', 'organization_project.organization_id', '=', 'archive_records.organization_id')
            ->where('organization_project.project_id', $this->id)
            ->sum(DB::raw('CAST(COALESCE(documents.page_number, 0) AS UNSIGNED)'));
    }

    public function getProgressPercentAttribute(): float
    {
        if (($this->target_pages ?? 0) <= 0) {
            return 0;
        }

        $percent = ($this->completed_pages / $this->target_pages) * 100;

        return min(100, round($percent, 2));
    }
}
