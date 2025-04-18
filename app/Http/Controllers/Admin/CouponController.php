<?php

namespace App\Http\Controllers\Admin;

use App\Commons\CodeMasters\Status;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validateCouponForUser(Request $request)
    {
        $user = $request->user();
        $code = $request->code;
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $coupon = Coupon::where('code', $code)->where('active', Status::SHOW())->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Mã không tồn tại.']);
        }

        if ($user->coupons->contains($coupon->id)) {
            return response()->json(['valid' => false, 'message' => 'Bạn đã sử dụng mã này rồi.']);
        }

        if ($coupon->end_date <= $now) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá đã hết hạn.']);
        }

        if ($coupon->used >= $coupon->usage_limit) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá đã hết số lần sử dụng.']);
        }

        $discount = $coupon->value;

        return response()->json([
            'valid' => true,
            'discount' => $discount,
            'type' => $coupon->type,
            'message' => 'Sử dụng mã thành công.'
        ]);
    }

    // $user->coupons()->attach($coupon->id, ['used_at' => now()]);
    // $coupon->increment('used');
}
