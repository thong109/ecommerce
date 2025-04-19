<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shipping_id',
        'payment_id',
        'order_total',
        'order_discount',
        'order_discount_type',
        'note',
        'order_status',
    ];

    public function shipping()
    {
        return $this->hasOne(Shipping::class);
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
