<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;
    protected $fillable = ['organization_id', 'code', 'name'];
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }
    // public function users(): HasMany {
    //     return $this->hasMany(User::class);
    // }
}
