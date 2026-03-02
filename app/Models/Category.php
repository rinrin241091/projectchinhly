<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name' , 'slug' , 'parent_id' ,'is_visible', 'description'
    ];
    public function parent(): BelongsTo
    {
        return $this->belongsTo(related: Category::class, foreignKey:'parent_id');
    }
    public function child():HasMany
    {
        return $this->hasMany(relate: Category::class, foreignKey:'parent_id' );
    }
    public function products(): belongsToMany
    {
        return $this->belongsToMany(related: Product::class);
    }
}
