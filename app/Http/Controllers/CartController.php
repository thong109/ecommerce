<?php

namespace App\Http\Controllers;

use App\Commons\CodeMasters\Status;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $carts = $request->all();

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        foreach ($carts as $item) {
            $productId = $item['id'];
            $attrsWithQty = $item['selectedAttributes'];
            $qty = $item['qty'];

            $attrsToCompare = $attrsWithQty;

            $existingItem = $cart->items->first(function ($cartItem) use ($productId, $attrsToCompare) {
                $itemAttrs = $cartItem->attributes;
                return $cartItem->product_id == $productId && $itemAttrs == $attrsToCompare;
            });

            if ($existingItem) {
                $existingQty = $existingItem->qty ?? 0;
                $existingItem->qty = $existingQty + (int) $qty;

                $existingItem->save();
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'attributes' => $attrsWithQty,
                    'qty' => $qty
                ]);
            }
        }

        return response()->json($cart);
    }

    public function getCartItems(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $cart = Cart::where('user_id', $user->id)->where('cart_status', Status::SHOW())->first();

        // Lấy tất cả các item trong giỏ hàng
        $cartItems = $cart->items->load('product');

        // Trả về giỏ hàng dưới dạng JSON
        return response()->json($cartItems);
    }

    public function deleteItemCart(string $id)
    {
        CartItem::where('id', $id)->delete();

        return response()->json(['code' => 200]);
    }
}
