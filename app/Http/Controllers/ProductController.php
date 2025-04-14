<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        try {
            $limit = $request->input('limit', 6);
            $page = $request->input('page', 1);
            $sort = $request->input('sort');
            $dataBy = $request->input('getBy');
            $id = $request->input('id');

            switch ($dataBy) {
                case 'category':
                    $query = Product::where('category_id', $id);
                    break;
                case 'brand':
                    $query = Product::where('brand_id', $id);
                    break;
                case 'product':
                default:
                    $query = Product::query();
                    break;
            }

            // Áp dụng sắp xếp
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

            $products = $query->where('status', 1)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'products' => $products->items(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi khi lấy sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductDetail(string $id)
    {
        try {
            $product = Product::with(['category', 'brand', 'attributeValues.attribute'])->find($id);

            if (!$product) {
                return response()->json([], 404);
            }

            // Tách tags (nếu có)
            $product->tag = $product->tag ? explode(',', $product->tag) : [];

            // Gom thuộc tính và giá trị
            $attributes = [];
            foreach ($product->attributeValues as $value) {
                $attrName = $value->attribute->name;
                $val = $value->value;

                if (!isset($attributes[$attrName])) {
                    $attributes[$attrName] = [];
                }

                $attributes[$attrName][] = $val;
            }

            // Format về dạng dễ dùng
            $attributeData = [];
            foreach ($attributes as $key => $vals) {
                $attributeData[] = [
                    'attribute' => $key,
                    'values' => $vals
                ];
            }

            return response()->json([
                'product' => $product,
                'attributes' => $attributeData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi khi lấy sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductsRelated(string $id)
    {
        try {
            $product = Product::whereId($id)->first();
            $productsRelated  = Product::where('category_id', $product->category_id)->where('status', 1)->where('id', '!=', $id)->get();

            return response()->json([
                'productsRelated' => $productsRelated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi khi lấy sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
