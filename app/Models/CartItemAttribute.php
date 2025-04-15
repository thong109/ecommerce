<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItemAttribute extends Model
{
    use HasFactory;

    protected $fillable = ['cart_item_id', 'attribute_name', 'attribute_value'];

    public function cartItem()
    {
        return $this->belongsTo(CartItem::class);
    }
}
