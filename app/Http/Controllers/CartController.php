<?php

namespace App\Http\Controllers;

use App\Commons\CodeMasters\CouponStatus;
use App\Commons\CodeMasters\OrderStatus;
use App\Commons\CodeMasters\Status;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            $price = $item['price'];

            $attrsToCompare = $attrsWithQty;

            $existingItem = $cart->items->first(function ($cartItem) use ($productId, $attrsToCompare) {
                $itemAttrs = $cartItem->attributes;
                return $cartItem->product_id == $productId && $itemAttrs == $attrsToCompare;
            });

            if ($existingItem) {
                $existingQty = $existingItem->qty ?? 0;
                $existingItem->qty = $existingQty + (int) $qty;
                $existingItem->price = $existingItem->price + $price * (int) $qty;

                $existingItem->save();
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'attributes' => $attrsWithQty,
                    'qty' => $qty,
                    'price' => $price * $qty
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
        $cartItems = $cart?->items->load('product') ?? [];

        // Trả về giỏ hàng dưới dạng JSON
        return response()->json($cartItems);
    }

    public function deleteItemCart(string $id)
    {
        CartItem::where('id', $id)->delete();

        return response()->json(['code' => 200]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|max:255',
            'email' => 'required|email',
            'phone' => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'paymentMethod' => 'required'
        ], [
            'name.required' => 'Tên không được để trống.',
            'address.required' => 'Địa chỉ không được để trống.',
            'email.required' => 'Email không được để trống.',
            'phone.required' => 'Số diện thoại không được để trống.',
            'paymentMethod.required' => 'Phương thức thanh toán không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'phone.regex' => 'Số điện thoại không đúng định dạng'
        ]);

        $rawItems = $request['cartItems'];
        $discounted = json_decode($request['discounted']) ?? null;
        $cartItem = array_map(function ($item) {
            return json_decode($item);
        }, $rawItems);

        if (!empty($discounted)) {
            $couponUser = DB::table('coupon_user')->insert([
                'coupon_id' => $discounted->coupon_id,
                'user_id' => $request->user()->id,
                'used_at' => now('Asia/Ho_Chi_Minh')->timestamp,
                'status' => CouponStatus::USED->value,
            ]);

            if ($couponUser) {
                $coupon = Coupon::where('id', $discounted->coupon_id)->first();
                $coupon->update([
                    'used' => $coupon->used += 1
                ]);
            }
        }

        $shipping = [
            'shipping_name' => $request['name'],
            'shipping_address' => $request['address'],
            'shipping_phone' => $request['phone'],
            'shipping_email' => $request['email'],
        ];

        $orders = Order::create([
            'user_id' => $request->user()->id,
            'shipping_id' => DB::table('shippings')->insertGetId($shipping),
            'payment_id' => $request['paymentMethod'],
            'order_total' => collect($cartItem)->sum('qty'),
            'order_discount' => $discounted?->discount ?? null,
            'order_discount_type' => $discounted?->type ?? null,
            'note' => $request['note'],
            'order_status' => OrderStatus::PROCESSING,
        ]);

        foreach ($cartItem as $value) {
            $orders->order_details()->create([
                'order_id' => $orders->id,
                'product_id' => $value->product_id,
                'product_name' => $value->product->name,
                'product_price' => $value->price,
                'product_sales_quantity' => $value->qty,
                'attributes' => $value->attributes,
            ]);
            // return response()->json($value->attributes);
        }

        $cart = Cart::where('user_id',$request->user()->id)->first();
        $cart->items()->delete();
        $cart->delete();

        return response()->json([
            'code' => 200,
        ]);
    }
}
