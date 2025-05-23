<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

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
        return $this->hasOne(Shipping::class, 'id', 'shipping_id');
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
