<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with([
            'category',
            'brand',
            'product_attribute_values.attribute',
            'product_attribute_values.attributeValue'
        ])->get();

        $result = [];

        foreach ($products as $product) {
            $attributeMap = [];

            foreach ($product->product_attribute_values as $pav) {
                $attrName = $pav->attribute->name ?? null;
                $attrValue = $pav->attributeValue->value ?? null;

                if ($attrName && $attrValue) {
                    if (!isset($attributeMap[$attrName])) {
                        $attributeMap[$attrName] = [];
                    }

                    if (!in_array($attrValue, $attributeMap[$attrName])) {
                        $attributeMap[$attrName][] = $attrValue;
                    }
                }
            }

            $result[] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'discounted' => $product->discounted,
                'cost' => $product->cost,
                'image' => $product->image,
                'attributes' => $attributeMap,
                'categoryName' => $product->category->name,
                'brandName' => $product->brand->name,
            ];
        }

        return response()->json($result);
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
        $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer',
            'status' => 'required|in:0,1',
            'price' => 'required|numeric',
            'cost' => 'nullable|numeric',
            'discounted' => 'nullable|numeric',
            'tag' => 'nullable|string|max:255',
            'category_id' => 'required',
            'image' => 'image|max:5120',
            'short_desc' => 'required',
            'description' => 'required',
        ]);

        // 1. Lưu sản phẩm
        $product = new Product();
        $product->fill($request->only([
            'name',
            'quantity',
            'status',
            'price',
            'cost',
            'discounted',
            'tag',
            'category_id',
            'brand_id',
            'short_desc',
            'description'
        ]));

        // 2. Upload ảnh nếu có
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('products', $filename, 'public');
            $product->image = $path;
        }

        $product->save();

        // 3. Gán thuộc tính
        if ($request->has('attributes')) {
            foreach ($request->input('attributes') as $attrId => $values) {
                if (is_array($values)) {
                    foreach ($values as $valueId) {
                        DB::table('product_attribute_values')->insert([
                            'product_id' => $product->id,
                            'attribute_id' => $attrId,
                            'attribute_value_id' => $valueId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Sản phẩm đã được tạo thành công!',
            'product' => $product
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::with('product_attribute_values')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Xoá ảnh sản phẩm
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        // Xoá thuộc tính trước
        $product->product_attribute_values()->delete();

        // Xoá sản phẩm
        $product->delete();

        return response()->json(['message' => 'Product and attributes deleted']);
    }
}
