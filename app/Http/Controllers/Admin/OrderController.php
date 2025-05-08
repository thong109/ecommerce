<?php

namespace App\Http\Controllers\Admin;

use App\Commons\CodeMasters\OrderStatus;
use App\Commons\CodeMasters\PaymentMethod;
use App\Commons\CodeMasters\Status;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with('shipping', 'order_details')->get();
        $orders->transform(function ($order) {
            $order->order_status = OrderStatus::from($order->order_status);
            $order->order_status_label = $order->order_status->label();

            $order->product_summary = collect($order->order_details)->sum(
                fn($item) => $item->product_price
            );
            return $order;
        });

        return response()->json([
            'orders' => $orders
        ]);
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
        if (!$id) {
            return response()->json([
                'message' => 'Error'
            ]);
        }

        $order = Order::where('id', $id)->with('shipping', 'order_details')->first();
        $order->payment_method = PaymentMethod::from($order->payment_id)->label();

        return response()->json($order);
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
    public function update(Request $request)
    {
        $userId = $request->user()->id;
        $order = Order::where('id', $request->order_id)->first();

        if (!$order) {
            return response()->json(['error' => 'Đơn hàng không tồn tại'], 404);
        }

        if (
            (int)$request->order_status === OrderStatus::DELIVERED->value &&
            (int)$order->order_status !== OrderStatus::DELIVERED->value
        ) {
            DB::beginTransaction();
            try {
                $invalidItems = [];

                foreach ($order->order_details as $detail) {
                    $product = Product::find($detail->product_id);
                    if ($product) {
                        // Kiểm tra đủ hàng không (tuỳ chọn)
                        if ($product->quantity < $detail->product_sales_quantity) {
                            $invalidItems[] = [
                                'product_id' => $product->id,
                                'message' => 'Sản phẩm không đủ hàng'
                            ];
                            continue;
                        }

                        if ($product->status === Status::HIDDEN()) {
                            $invalidItems[] = [
                                'product_id' => $product->id,
                                'message' => 'Sản phẩm không còn mở bán'
                            ];
                            continue;
                        }

                        // Trừ số lượng tồn kho
                        $product->quantity -= $detail->product_sales_quantity;
                        $product->sold = $product->sold + $detail->product_sales_quantity;
                        $product->save();
                    }
                }

                if (count($invalidItems) > 0) {
                    return response()->json([
                        'valid' => false,
                        'invalidItems' => $invalidItems
                    ], 400);
                }

                // Cập nhật trạng thái đơn
                $order->order_status = $request->order_status;
                $order->save();

                DB::commit();
                return response()->json([
                    'message' => 'Cập nhật đơn hàng & trừ hàng thành công',
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
            }
        }

        // Nếu không cần trừ hàng, chỉ update trạng thái
        $order->order_status = $request->order_status;
        $order->save();

        return response()->json([
            'message' => 'Cập nhật đơn hàng thành công',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
