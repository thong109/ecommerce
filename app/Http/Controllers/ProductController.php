<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        $limit = $request->input('limit', 6);
        $page = $request->input('page', 1);
        $sort = $request->input('sort');
        $dataBy = $request->input('getBy');
        $id = $request->input('id');

        switch ($dataBy) {
            case 'category':
                $query =  Product::where('category_id', $id);
                break;
            case 'product':
            default:
                $query =  Product::query();
                break;
        }

        // Áp dụng sort theo yêu cầu
        switch ($sort) {
            case 'high':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'low':
            default:
                $query->orderBy('price', 'asc');
                break;
        }

        // Phân trang đúng theo trang gửi lên
        $products = $query->where('status', 1)->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'products' => $products->items(),     // Sản phẩm của trang hiện tại
            'total' => $products->total(),        // Tổng số sản phẩm
            'last_page' => $products->lastPage(), // Tổng số trang
        ]);
    }
}
