<?php

namespace App\Http\Controllers\Admin;

use App\Commons\CodeMasters\Status;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brand = Brand::where('status', Status::SHOW())->get();
        return response()->json($brand);
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
        $data = $request->validate(
            [
                'name' => 'required',
                'status' => 'required|in:0,1',
                'image' => 'required',
            ],
            [
                'name.required' => 'Tên danh mục không được để trống.',
                'name.max' => 'Tên danh mục không được vượt quá :max ký tự.',
                'status.required' => 'Vui lòng chọn tình trạng hiển thị.',
            ]
        );

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'brand-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.' . $extension;
            // Lưu ảnh với tên mới
            $path = $file->storeAs('brand', $fileName, 'public');

            $data['image'] = $path;
        }

        $brand = Brand::create([
            'name' => $data['name'],
            'status' => $data['status'],
            'image' => $data['image'],
        ]);

        return response()->json([
            'message' => 'Brand created with attributes.',
            'brandId' => $brand->id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $brand = Brand::findOrFail($id);
        return response()->json($brand);
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
        return response()->json($request->all());
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255',
                'status' => 'required|in:0,1',
            ],
            [
                'name.required' => 'Tên thương hiệu không được để trống.',
                'name.max' => 'Tên thương hiệu không được vượt quá :max ký tự.',
                'status.required' => 'Vui lòng chọn tình trạng hiển thị.',
            ]
        );

        DB::beginTransaction();

        try {
            $brand = Brand::findOrFail($id);
            $brand->name = $validated['name'];
            $brand->status = $validated['status'];

            // Cập nhật ảnh nếu có
            if ($request->hasFile('image')) {
                Storage::disk('public')->delete($brand->image); // Xóa ảnh brand cũ nếu cập nhật ảnh mới
                $image = $request->file('image');
                $path = $image->store('categories', 'public');
                $brand->image = $path;
            }

            $brand->save();
            DB::commit();
            return response()->json(['message' => 'Cập nhật thương hiệu thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Cập nhật thương hiệu thất bại']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json(['message' => 'Brand not found'], 404);
            }

            $checkBrandUsed = Product::where('brand_id', $id)->get()->count();
            if ($checkBrandUsed > 0) {
                return response()->json(['message' => 'Brand used'], 404);
            }

            // Xoá ảnh sản phẩm
            if ($brand->image && Storage::disk('public')->exists($brand->image)) {
                Storage::disk('public')->delete($brand->image);
            }

            // Xoá sản phẩm
            $brand->delete();

            return response()->json(['message' => 'Product and attributes deleted']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Cập nhật thương hiệu thất bại']);
        }
    }
}
