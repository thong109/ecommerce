<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_name',
        'shipping_address',
        'shipping_phone',
        'shipping_email',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
