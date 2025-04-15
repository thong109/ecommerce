<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $carts = $request->all();
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Authentication required.'], 404);
        }

        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
        );

        foreach ($carts as $item) {
            $productId = $item['product']['id'];
            $newAttrs = $item['attrs'];

            // Tìm cart item cho product này
            $cartItem = $cart->items()->where('product_id', $productId)->first();

            if (!$cartItem) {
                // Nếu chưa có -> tạo mới
                $cart->items()->create([
                    'product_id' => $productId,
                    'attributes' => $newAttrs,
                ]);
            } else {
                // Nếu đã có -> merge đúng product
                $existingAttrs = $cartItem->attributes ?? [];

                foreach ($newAttrs as $newAttr) {
                    $newKey = $newAttr['productKey'] ?? null;
                    if (!$newKey) continue;

                    $matched = false;

                    foreach ($existingAttrs as &$existAttr) {
                        if (($existAttr['productKey'] ?? null) === $newKey) {
                            // Nếu trùng key -> cộng quantity
                            (int) $existAttr['quantity'] += (int) $newAttr['quantity'];
                            $matched = true;
                            break;
                        }
                    }

                    unset($existAttr); // Xoá tham chiếu

                    if (!$matched) {
                        // Nếu không trùng -> thêm mới
                        $existingAttrs[] = $newAttr;
                    }
                }

                // Cập nhật lại giỏ hàng
                $cartItem->update(['attributes' => $existingAttrs]);
            }
        }

        return response()->json(['message' => 'Giỏ hàng đã được cập nhật.']);
    }
}
