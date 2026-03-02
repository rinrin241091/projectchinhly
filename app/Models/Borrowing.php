<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    use HasFactory;
    protected $fillable = ['record_id', 'user_id', 'borrowed_at', 'returned_at', 'purpose'];
    public function record(): BelongsTo {
        return $this->belongsTo(Record::class);
    }
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
