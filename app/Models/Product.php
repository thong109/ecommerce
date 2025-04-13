<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'image',
        'rating',
        'status'
    ];

    public function category()
    {
        return $this->hasOne(Category::class);
    }

    public function brand()
    {
        return $this->hasOne(Brand::class);
    }
}
