<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json($categories);
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
                'status' => 'required|in:0,1',  // Trạng thái phải là 0 hoặc 1
                'image' => 'required',
                'attributes' => 'required|array',
                'attributes.*.name' => 'required|string',
                'attributes.*.values' => 'required|array|min:1',
                'attributes.*.values.*' => 'required|string'
            ],
            [
                'name.required' => 'Tên danh mục không được để trống.',
                'name.max' => 'Tên danh mục không được vượt quá :max ký tự.',
                'status.required' => 'Vui lòng chọn tình trạng hiển thị.',
                'attributes.required' => 'Thuộc tính ko được để trống'
            ]
        );

        if ($request->hasFile('image')) {
            // Trường hợp gửi dạng file qua FormData
            $file = $request->file('image');
            $path = $file->store('category', 'public');
            $data['image'] = $path;
        }

        $category = Category::create([
            'name' => $data['name'],
            'status' => $data['status'],
            'image' => $data['image'],
        ]);

        if (!empty($data['attributes'])) {
            foreach ($data['attributes'] as $attr) {
                $attribute = $category->attributes()->create([
                    'name' => $attr['name']
                ]);

                foreach ($attr['values'] as $val) {
                    $attribute->values()->create([
                        'value' => $val
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Category created with attributes.',
            'categoryId' => $category->id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::with(['attributes.values'])->findOrFail($id);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'status' => $category->status,
            'image_url' => $category->image, // nếu lưu ảnh bằng storage
            'attributes' => $category->attributes->map(function ($attr) {
                return [
                    'name' => $attr->name,
                    'values' => $attr->values->pluck('value')->toArray()
                ];
            })
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
        // Xác thực dữ liệu (nếu cần)
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255',
                'status' => 'required|in:0,1',  // Trạng thái phải là 0 hoặc 1
                'attributes' => 'required|array',
                'attributes.*.name' => 'required|string',
                'attributes.*.values' => 'required|array|min:1',
                'attributes.*.values.*' => 'required|string'
            ],
            [
                'name.required' => 'Tên danh mục không được để trống.',
                'name.max' => 'Tên danh mục không được vượt quá :max ký tự.',
                'status.required' => 'Vui lòng chọn tình trạng hiển thị.',
                'attributes.required' => 'Thuộc tính ko được để trống'
            ]
        );

        DB::beginTransaction();

        try {
            $category = Category::findOrFail($id);
            $category->name = $validated['name'];
            $category->status = $validated['status'];

            // Cập nhật ảnh nếu có
            if ($request->hasFile('image')) {
                Storage::disk('public')->delete($category->image); // Xóa ảnh category cũ nếu cập nhật ảnh mới
                $image = $request->file('image');
                $path = $image->store('categories', 'public');
                $category->image = $path;
            }

            $category->save();

            // Xóa toàn bộ thuộc tính cũ (hoặc có thể cập nhật thông minh hơn nếu cần)
            $category->attributes()->delete();

            // Lưu thuộc tính và giá trị mới
            foreach ($validated['attributes'] ?? [] as $attrData) {
                $attribute = $category->attributes()->create([
                    'name' => $attrData['name']
                ]);

                foreach ($attrData['values'] as $value) {
                    $attribute->values()->create([
                        'value' => $value
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Cập nhật danh mục thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Cập nhật danh mục thất bại']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
