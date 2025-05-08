<?php

namespace App\Http\Controllers;

use App\Commons\CodeMasters\OrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function monthlySales()
    {
        $now = Carbon::now();
        $start = $now->copy()->subMonths(5)->startOfMonth();

        $sales = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as month, SUM(product_price) as total")
            ->groupBy('month')
            ->where('orders.order_status', OrderStatus::DELIVERED)
            ->orderBy('month')
            ->get();

        // Đảm bảo đủ 6 tháng, nếu không có đơn sẽ trả về 0
        $months = collect(range(0, 5))->map(function ($i) use ($now) {
            return $now->copy()->subMonths($i)->format('Y-m');
        })->reverse()->values();

        $salesData = $months->map(function ($month) use ($sales) {
            $data = $sales->firstWhere('month', $month);
            return [
                'month' => $month,
                'total' => $data ? (float) $data->total : 0
            ];
        });

        return response()->json($salesData);
    }

    public function dateSales(Request $request)
    {
        $start = Carbon::parse($request->input('start_date'));
        $end = Carbon::parse($request->input('end_date'));

        $sales = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween('orders.created_at', [$start, $end]) // Lọc theo khoảng thời gian
            ->selectRaw("DATE(orders.created_at) as day, SUM(product_price) as total")
            ->where('orders.order_status', OrderStatus::DELIVERED)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Tạo danh sách các tháng trong khoảng thời gian từ start đến end
        $dates = collect();
        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            $dates->push($currentDate->format('Y-m-d'));
            $currentDate->addDay();
        }

        // Đảm bảo rằng tất cả các ngày đều có giá trị doanh thu (0 nếu không có đơn)
        $salesData = $dates->map(function ($date) use ($sales) {
            $data = $sales->firstWhere('day', $date);
            return [
                'day' => $date,
                'total' => $data ? (float) $data->total : 0
            ];
        });

        return response()->json($salesData);
    }
}
