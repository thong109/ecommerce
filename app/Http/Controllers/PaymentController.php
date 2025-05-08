<?php

namespace App\Http\Controllers;

use App\Commons\CodeMasters\CouponStatus;
use App\Commons\CodeMasters\OrderStatus;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function createVnpayPayment(Request $request)
    {
        try {
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
            $cartItems = array_map(function ($item) {
                return json_decode($item);
            }, $rawItems);

            $data = DB::transaction(function () use ($request, $cartItems, $discounted) {

                if (!empty($discounted)) {
                    DB::table('coupon_user')->insert([
                        'coupon_id' => $discounted->coupon_id,
                        'user_id' => $request->user()->id,
                        'used_at' => now('Asia/Ho_Chi_Minh')->timestamp,
                        'status' => CouponStatus::USED->value,
                    ]);

                    $coupon = Coupon::where('id', $discounted->coupon_id)->first();
                    $coupon->update([
                        'used' => $coupon->used + 1
                    ]);
                }

                $shipping = [
                    'shipping_name' => $request['name'],
                    'shipping_address' => $request['address'],
                    'shipping_phone' => $request['phone'],
                    'shipping_email' => $request['email'],
                ];

                $shippingId = DB::table('shippings')->insertGetId($shipping);

                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'shipping_id' => $shippingId,
                    'payment_id' => $request['paymentMethod'],
                    'order_total' => collect($cartItems)->sum('qty'),
                    'order_discount' => $discounted?->discount ?? null,
                    'order_discount_type' => $discounted?->type ?? null,
                    'note' => $request['note'],
                    'order_status' => OrderStatus::PROCESSING,
                ]);

                $total = 0;

                foreach ($cartItems as $item) {
                    $order->order_details()->create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'product_price' => $item->price,
                        'product_sales_quantity' => $item->qty,
                        'attributes' => $item->attributes,
                    ]);
                    $total += $item->price * $item->qty;
                }

                $cart = Cart::where('user_id', $request->user()->id)->first();
                if ($cart) {
                    $cart->items()->delete();
                    $cart->delete();
                }

                return ['order' => $order, 'total' => $total];
            });

            $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

            $partnerCode = 'MOMOBKUN20180529';
            $accessKey = 'klm05TvNBzhg7h7j';
            $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

            $orderId = $data['order']['id'];
            $orderInfo = "Thanh toán qua MoMo";
            $amount = $data['total'];
            $redirectUrl = 'http://localhost:5173/payment-result';
            $ipnUrl = 'http://localhost:5173/checkout'; // IPN callback

            $requestId = time() . "";
            $extraData = ""; // nếu cần thêm dữ liệu

            $rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$ipnUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=payWithATM";

            $signature = hash_hmac("sha256", $rawHash, $secretKey);

            $data = [
                'partnerCode' => $partnerCode,
                'accessKey' => $accessKey,
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'extraData' => $extraData,
                'requestType' => 'payWithATM',
                'signature' => $signature,
                'lang' => 'vi'
            ];

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $result = curl_exec($ch);
            $jsonResult = json_decode($result, true); // decode

            return response()->json([
                'payUrl' => $jsonResult['payUrl'],
                'code' => 200
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function handleVnpayReturn(Request $request)
    {
        $orderId = $request->input('orderId');
        $resultCode = $request->input('resultCode');

        $order = Order::where('id', $orderId)->first();

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại']);
        }

        if ($resultCode === '0') {
            // Thanh toán thành công
            $order->update([
                'order_status' => OrderStatus::PROCESSING,
            ]);
            return response()->json(['message' => 'Xác nhận thanh toán thành công']);
        }

        // Ngược lại: huỷ thanh toán → rollback dữ liệu
        DB::transaction(function () use ($order) {
            // Xoá chi tiết đơn hàng
            $order->order_details()->delete();

            // Xoá đơn hàng
            $order->delete();

            // Xoá shipping
            DB::table('shippings')->where('id', $order->shipping_id)->delete();

            // (Tuỳ chọn) restore lại cart nếu muốn
            // Cart::create([...]); hoặc lưu lại trước khi xoá để khôi phục
        });

        return response()->json(['message' => 'Thanh toán thất bại - Đơn hàng đã bị huỷ']);
    }
}
