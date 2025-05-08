<?php

namespace App\Http\Controllers;

use App\Commons\CodeMasters\OrderStatus;
use App\Commons\CodeMasters\PaymentMethod;
use App\Commons\CodeMasters\Role;
use App\Commons\CodeMasters\Status;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Models\UserChangeRequest;
use App\Models\UserInfo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('user_info');

        if ($user->user_info->avatar_status === Status::HIDDEN()) {
            $user->user_info->avatar = $user->user_info->avatar_old;
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'created_at' => $user->created_at,
            'user_info' => $user->user_info,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->checkUser($request);

        $userUpdate = User::find($id);

        if (!$userUpdate) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user = User::findOrFail($id);

        // Cập nhật thông tin user_info
        $userInfo = $user->user_info;

        if ($request->hasFile('user_info.avatar')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ], [
                'image.mimes' => 'File không đúng định dạng'
            ]);
            // Trường hợp gửi dạng file qua FormData
            $file = $request->file('user_info.avatar');
            $path = $file->store('avatars', 'public');
            $userInfo->avatar = $path;

            if ($userInfo->avatar_status !== Status::HIDDEN()) {
                $userInfo->avatar_old = $userInfo->avatar;
                $userInfo->avatar_status = Status::HIDDEN();
            }

            $userInfo->save();

            UserChangeRequest::where('type', 'avatar')->delete();
            UserChangeRequest::create([
                'user_id' => auth()->id(),
                'type' => 'avatar',
                'data' => json_encode([
                    'avatar' => $path
                ]),
            ]);

            return response()->json([
                'message' => 'Ảnh của bạn đã được thay đổi.',
                'code' => 200
            ]);
        }

        $request->validate([
            'name' => 'required',
            'user_info.phone' => ['regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', Rule::unique('user_infos', 'phone')->ignore($userInfo->id),],
        ], [
            'name.required' => 'Tên không được để trống',
            'user_info.phone.regex' => 'Số điện thoại không đúng định dạng',
            'user_info.phone.unique' => 'Số điện đã được sử dụng'
        ]);

        // Cập nhật các trường cơ bản
        $user->name = $request->input('name');
        $user->save();
        $userInfo->phone = $request->input('user_info.phone');
        $userInfo->address = $request->input('user_info.address');
        $userInfo->description = $request->input('user_info.description');
        $userInfo->save();

        return response()->json([
            'code' => 200,
            'message' => 'Cập nhật thông tin thành công',
            'user' => $user->load('user_info'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        foreach ($ids as $id) {
            $user = User::with('user_info')->find($id);

            if ($user) {
                // Xoá thuộc tính trước
                $user->user_info()->delete();
                $user->delete();
            }
        }

        return response()->json(['message' => 'Xóa thành công.', 'code' => 200]);
    }

    private function saveBase64Image($image)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new \Exception('Invalid image type');
            }

            $image = base64_decode($image);
            if ($image === false) {
                throw new \Exception('base64_decode failed');
            }

            $filename = uniqid() . '.' . $type;
            $path = storage_path("app/public/avatars/{$filename}");
            file_put_contents($path, $image);

            return "avatars/{$filename}";
        }

        throw new \Exception('Invalid image format');
    }

    private function checkUser(Request $request)
    {
        $userCurrent = $request->user();

        if (!$userCurrent) {
            return response()->json(['message' => 'Authentication.'], 404);
        }
    }

    public function userBuy(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Chưa xác thực người dùng.'], 401);
        }

        $orders = Order::with('order_details.product')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function orderDetail(Request $request, string $id)
    {
        $userCurrent = $request->user();

        if (!$userCurrent) {
            return response()->json(['message' => 'Authentication failed.'], 401);
        }

        if (!$id) {
            return response()->json(['message' => 'Invalid order ID.'], 400);
        }

        $order = Order::with([
            'shipping',
            'order_details'
        ])->where('id', $id)->where('user_id', $userCurrent->id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->payment_method = PaymentMethod::from($order->payment_id)->label();

        $order->order_status_enum = OrderStatus::from($order->order_status);
        $order->order_status_label = $order->order_status_enum->label();

        foreach ($order->order_details as $detail) {
            $detail->product_image = $detail->product->image ?? null;
        }

        return response()->json($order);
    }
}
