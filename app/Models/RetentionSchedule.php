<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetentionSchedule extends Model
{
    use HasFactory;
   
        protected $fillable = ['code', 'description', 'years'];
 
}
