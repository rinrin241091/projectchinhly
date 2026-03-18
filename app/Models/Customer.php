<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;
    protected $fillable =[
       'name', 'email', 'phone' ,'date_of_birth', 'address' , 'zip_code', 'city'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(relate:Customer::class);
    }

}
