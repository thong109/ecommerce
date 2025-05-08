<?php

namespace App\Http\Controllers\Admin;

use App\Commons\CodeMasters\Status;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Validation\ValidationException;

class ProductAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'brand',
            'product_attribute_values.attribute',
            'product_attribute_values.attributeValue'
        ]);

        if ($request['attr'] === 'blockProduct') {
            $query->onlyTrashed();
        }

        $products = $query->orderBy('created_at', 'desc')->get();

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
                'cost' => $product->cost,
                'image' => $product->image,
                'attributes' => $attributeMap,
                'categoryName' => $product->category->name,
                'brandName' => $product->brand->name,
                'quantity' => $product->quantity,
                'status' => $product->status
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
            'name' => 'required|string|max:255|unique:products,name',
            'quantity' => 'required|numeric',
            'status' => 'required|in:0,1',
            'price' => 'required|numeric',
            'cost' => 'required|numeric|lt:price',
            'tag' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'image' => 'required|image|max:5120',
            'short_desc' => 'required',
            'description' => 'required',
            'attributes' => 'required'
        ], [
            'name.required' => 'Tên không được để trống.',
            'quantity.required' => 'Số lượng không được để trống.',
            'status.required' => 'Tình trạng không được để trống.',
            'attributes.required' => 'Thuộc tính không được để trống.',
            'price.required' => 'Giá bán không được để trống.',
            'cost.required' => 'Giá vốn không được để trống.',
            'tag.required' => 'Thẻ tag không được để trống.',
            'category_id.required' => 'Danh mục được để trống.',
            'brand_id.required' => 'Thương hiệu không được để trống.',
            'image.required' => 'Ảnh không được để trống.',
            'short_desc.required' => 'Mô tả ngắn không được để trống.',
            'description.required' => 'Mô tả không được để trống.',
            'cost.lt' => 'Giá vốn phải nhỏ hơn giá bán.',
            'price.numeric' => 'Giá bán nên là số.',
            'quantity.numeric' => 'Số lượng nên là số.',
            'cost.numeric' => 'Giá vốn nên là số.',
            'name.unique' => 'Tên sản phẩm đã tồn tại.',
        ]);

        // 1. Lưu sản phẩm
        $product = new Product();
        $product->fill($request->only([
            'name',
            'quantity',
            'status',
            'price',
            'cost',
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
        $product = Product::with([
            'category',
            'brand',
            'product_attribute_values.attribute',
            'product_attribute_values.attributeValue'
        ])->findOrFail($id);

        $groupedAttributes = $product->product_attribute_values
            ->groupBy(fn($pav) => $pav->attribute?->id)
            ->map(function ($items) {
                return $items->pluck('attributeValue.id')->filter()->unique()->values()->toArray();
            });

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'status' => $product->status,
            'image_url' => $product->image, // Nếu ảnh lưu trong storage
            'cost' => $product->cost,
            'tag' => $product->tag,
            'short_desc' => $product->short_desc,
            'description' => $product->description,
            'category' => [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
            ],
            'brand' => [
                'id' => $product->brand?->id,
                'name' => $product->brand?->name,
            ],
            'attributes' => $groupedAttributes
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
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name,' . $id,
            'quantity' => 'required|numeric',
            'status' => 'required|in:0,1',
            'price' => 'required|numeric',
            'cost' => 'required|numeric|lt:price',
            'tag' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'short_desc' => 'required',
            'description' => 'required',
            'attributes' => 'required|array'
        ], [
            'name.required' => 'Tên không được để trống.',
            'quantity.required' => 'Số lượng không được để trống.',
            'status.required' => 'Tình trạng không được để trống.',
            'attributes.required' => 'Thuộc tính không được để trống.',
            'price.required' => 'Giá bán không được để trống.',
            'cost.required' => 'Giá vốn không được để trống.',
            'tag.required' => 'Thẻ tag không được để trống.',
            'category_id.required' => 'Danh mục được để trống.',
            'brand_id.required' => 'Thương hiệu không được để trống.',
            'short_desc.required' => 'Mô tả ngắn không được để trống.',
            'description.required' => 'Mô tả không được để trống.',
            'cost.lt' => 'Giá vốn phải nhỏ hơn giá bán.',
            'price.numeric' => 'Giá bán nên là số.',
            'quantity.numeric' => 'Số lượng nên là số.',
            'cost.numeric' => 'Giá vốn nên là số.',
            'name.unique' => 'Tên sản phẩm đã tồn tại.',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($id);
            $product->name = $request['name'];
            $product->price = $request['price'];
            $product->status = $request['status'];
            $product->quantity = $request['quantity'];
            $product->tag = $request['tag'];
            $product->category_id = $request['category_id'];
            $product->brand_id = $request['brand_id'];
            $product->short_desc = $request['short_desc'];
            $product->description = $request['description'];

            // Cập nhật ảnh sản phẩm nếu có
            if ($request->hasFile('image')) {
                Storage::disk('public')->delete($product->image); // Xóa ảnh cũ nếu có
                $image = $request->file('image');
                $path = $image->store('products', 'public');
                $product->image = $path;
            }

            $product->save();

            $product->product_attribute_values()->forceDelete();

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

            DB::commit();

            return response()->json(['message' => 'Cập nhật sản phẩm thành công!', 'code' => 200]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Cập nhật sản phẩm thất bại', 'code' => 422]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        foreach ($ids as $id) {
            $product = Product::with('product_attribute_values')->find($id);

            if ($product) {
                // Xoá thuộc tính trước
                $product->product_attribute_values()->delete();

                // Xoá sản phẩm
                $product->delete();
            }
        }

        return response()->json(['message' => 'Xóa thành công.', 'code' => 200]);
    }

    public function export()
    {
        $products = Product::with([
            'category',
            'brand',
            'product_attribute_values.attribute',
            'product_attribute_values.attributeValue'
        ])->get();

        // Tạo spreadsheet mới
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set tiêu đề cột
        $sheet->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Tên sản phẩm')
            ->setCellValue('C1', 'Giá bán')
            ->setCellValue('D1', 'Giá vốn')
            ->setCellValue('E1', 'Thông tin')
            ->setCellValue('F1', 'Chi tiết')
            ->setCellValue('G1', 'Số lượng')
            ->setCellValue('H1', 'Số lượng đã bán')
            ->setCellValue('I1', 'Đánh giá')
            ->setCellValue('J1', 'Hình ảnh');

        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tự động co độ rộng cột theo nội dung
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Điền dữ liệu sản phẩm
        $row = 2; // Bắt đầu từ hàng 2 vì hàng 1 là tiêu đề
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->id)
                ->setCellValue('B' . $row, $product->name)
                ->setCellValue('C' . $row, (float) $product->price)
                ->setCellValue('D' . $row, (float) $product->cost)
                ->setCellValue('E' . $row, "Danh mục: {$product->category->name}\nThương hiệu: {$product->brand->name}")
                ->setCellValue('G' . $row, $product->quantity)
                ->setCellValue('H' . $row, 0)
                ->setCellValue('I' . $row, $product->rating);

            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle("C{$row}:D{$row}")
                ->getNumberFormat()
                ->setFormatCode('#,##0\ "VNĐ"');
            $attributesText = collect($product->product_attribute_values)->map(function ($attrVal) {
                return $attrVal->attribute->name . ': ' . $attrVal->attributeValue->value;
            })->implode("\n");

            $sheet->setCellValue('F' . $row, $attributesText);
            $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);

            $imagePath = public_path('storage/' . $product->image);
            if (file_exists($imagePath)) {
                $drawing = new Drawing();
                $drawing->setPath($imagePath);
                $drawing->setHeight(60);
                $drawing->setWidth(60);
                $drawing->setCoordinates('J' . $row);
                $drawing->setOffsetX(15);
                $drawing->setOffsetY((80 - 60) / 2);
                $drawing->setWorksheet($sheet);

                $sheet->getRowDimension($row)->setRowHeight(60);
            } else {
                $sheet->setCellValue('J' . $row, "Image not found");
            }

            $row++;
        }

        // Tạo file Excel và xuất ra trình duyệt
        $writer = new Xlsx($spreadsheet);
        $filename = 'products_export.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function copy(Request $request)
    {
        $ids = $request->input('ids');

        foreach ($ids as $id) {
            $product = Product::with('product_attribute_values')->find($id);

            if ($product) {
                $newProduct = $product->replicate();
                $newProduct->name .= ' (Copy)';

                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    $extension = pathinfo($product->image, PATHINFO_EXTENSION);
                    $newImageName = Str::random(10) . '.' . $extension;

                    Storage::disk('public')->copy(
                        $product->image,
                        "products/{$newImageName}"
                    );

                    $newProduct->image = "products/{$newImageName}";
                }

                $newProduct->save();

                foreach ($product->product_attribute_values as $attr) {
                    $newAttr = $attr->replicate();
                    $newAttr->product_id = $newProduct->id;
                    $newAttr->save();
                }
            }
        }

        return response()->json(['message' => 'Sao chép thành công', 'code' => 200]);
    }

    public function restore(Request $request)
    {
        $ids = $request->input('ids');

        foreach ($ids as $id) {
            $product = Product::withTrashed()->with(['product_attribute_values' => function ($q) {
                $q->withTrashed();
            }])->find($id);

            if ($product && $product->trashed()) {
                // Khôi phục sản phẩm
                $product->restore();

                // Khôi phục các attribute values nếu có
                foreach ($product->product_attribute_values as $attrValue) {
                    if ($attrValue->trashed()) {
                        $attrValue->restore();
                    }
                }
            }
        }

        return response()->json(['message' => 'Khôi phục thành công', 'code' => 200]);
    }

    public function uploadZip(Request $request)
    {
        // Validate file
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:10240', // Max size 10MB
        ]);

        // Lưu file ZIP vào thư mục tạm thời
        $zipFile = $request->file('zip_file');
        $zipFilePath = $zipFile->storeAs('uploads', rand(0, 999999) . '.zip');

        $zip = new ZipArchive;

        if ($zip->open(storage_path("app/{$zipFilePath}")) === TRUE) {
            // Đặt đường dẫn giải nén
            $extractPath = storage_path('app/uploads/extracted/');

            // Giải nén file ZIP vào thư mục
            $zip->extractTo($extractPath);
            $zip->close();

            // Lấy tất cả các thư mục con trong thư mục extracted
            $directories = collect(Storage::directories('uploads/extracted'));

            // Lặp qua các thư mục con (mỗi thư mục là một sản phẩm)
            foreach ($directories as $dir) {
                $directory = collect(Storage::directories($dir));

                foreach ($directory as $k => $d) {
                    $productName = basename($d); // product-1, product-2, ...
                    // Tạo đối tượng sản phẩm mới
                    $productData = [
                        'name' => $productName, // Tên sản phẩm từ tên thư mục
                        'quantity' => 0, // Bạn có thể thay đổi theo yêu cầu
                        'status' => Status::HIDDEN(), // Mặc định trạng thái sản phẩm
                        'price' => 0, // Bạn có thể thay đổi theo yêu cầu
                        'cost' => 0, // Bạn có thể thay đổi theo yêu cầu
                        'tag' => '', // Bạn có thể thay đổi theo yêu cầu
                        'category_id' => null, // Bạn có thể thay đổi theo yêu cầu
                        'brand_id' => null, // Bạn có thể thay đổi theo yêu cầu
                        'short_desc' => '', // Bạn có thể thay đổi theo yêu cầu
                        'description' => '', // Bạn có thể thay đổi theo yêu cầu
                        'image' => null
                    ];

                    $infoPath = $d . '/info.txt';
                    Log::info($infoPath);
                    if (Storage::disk('local')->exists($infoPath)) {
                        $content = Storage::disk('local')->get($infoPath);
                        Log::info($content);
                        $lines = explode("\n", $content);

                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (!$line || !str_contains($line, ':')) continue;

                            [$key, $value] = explode(':', $line, 2);
                            $key = trim(strtolower($key));
                            $value = trim($value);

                            if (array_key_exists($key, $productData)) {
                                // Kiểm tra giá trị của các trường (dành cho số nguyên hoặc float)
                                if ($key === 'price' || $key === 'cost' || $key === 'quantity') {
                                    if (is_numeric($value)) {
                                        $productData[$key] = (float)$value; // Chuyển giá trị thành số
                                    } else {
                                        Log::error("Giá trị {$key} không hợp lệ trong file info.txt.");
                                    }
                                } else {
                                    // Kiểm tra các trường còn lại là chuỗi không trống
                                    if (!empty($value)) {
                                        $productData[$key] = $value;
                                    } else {
                                        Log::error("Giá trị {$key} trong file info.txt không hợp lệ.");
                                    }
                                }
                            }
                        }
                    }

                    $files = Storage::disk('local')->files($d);
                    foreach ($files as $file) {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $filename = time() . '_' . basename($file);
                            // Đọc nội dung file từ 'local'
                            $fileContent = Storage::disk('local')->get($file);

                            // Ghi nội dung vào 'public/products'
                            Storage::disk('public')->put('products/' . $filename, $fileContent);

                            // Lưu đường dẫn vào mảng dữ liệu sản phẩm
                            $productData['image'] = 'products/' . $filename;
                            break; // Chỉ lấy ảnh đầu tiên (nếu có nhiều ảnh, bạn có thể thay đổi logic này)
                        }
                    }

                    try {
                        $validator = \Illuminate\Support\Facades\Validator::make($productData, [
                            'name' => 'required|string|max:255|unique:products,name',
                            'quantity' => 'required|numeric',
                            'price' => 'required|numeric',
                            'cost' => 'required|numeric|lt:price',
                            'tag' => 'required|string|max:255',
                            'category_id' => 'required|integer|exists:categories,id',
                            'brand_id' => 'required|integer|exists:brands,id',
                            'image' => 'required|max:5120',
                            'short_desc' => 'required',
                            'description' => 'required',
                        ], [
                            'name.required' => 'Tên không được để trống.',
                            'quantity.required' => 'Số lượng không được để trống.',
                            'price.required' => 'Giá bán không được để trống.',
                            'cost.required' => 'Giá vốn không được để trống.',
                            'tag.required' => 'Thẻ tag không được để trống.',
                            'category_id.required' => 'Danh mục được để trống.',
                            'brand_id.required' => 'Thương hiệu không được để trống.',
                            'image.required' => 'Ảnh không được để trống.',
                            'short_desc.required' => 'Mô tả ngắn không được để trống.',
                            'description.required' => 'Mô tả không được để trống.',
                            'cost.lt' => 'Giá vốn phải nhỏ hơn giá bán.',
                            'price.numeric' => 'Giá bán nên là số.',
                            'quantity.numeric' => 'Số lượng nên là số.',
                            'cost.numeric' => 'Giá vốn nên là số.',
                            'name.unique' => "Tên " . $productData['name'] . " đã tồn tại.",
                        ]);

                        // Nếu có lỗi validate, throw ra ngoại lệ
                        if ($validator->fails()) {
                            // Log::error('Validation errors: ' . $validator->errors()->toJson());
                            // continue; // Bỏ qua sản phẩm này
                            return response()->json(['errors' => $validator->errors()], 422);
                        }

                        Product::create($productData);
                    } catch (ValidationException $e) {
                        Log::error('Validation exception: ' . $e->getMessage());
                        continue; // Bỏ qua sản phẩm này nếu có lỗi validate
                    }
                }
                Storage::disk('local')->deleteDirectory($dir);
            }
            Storage::delete('uploads/' . basename(storage_path("app/{$zipFilePath}")));
            return response()->json(['message' => 'Sản phẩm đã được tạo thành công.'], 201);
        } else {
            throw new \Exception("Không thể mở file ZIP.");
        }
        Storage::disk('local')->deleteDirectory($dir);

        // return response()->json(['message' => 'File uploaded and extracted successfully.']);
    }

    public function changeStatus(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'code' => 422,
                'message' => 'Không tồn tại sản phẩm.'
            ]);
        }

        $product->update([
            'status' => $product->status === Status::HIDDEN() ? Status::SHOW() : Status::HIDDEN()
        ]);

        return response()->json([
            'code' => 200,
            'message' => 'Thay đổi trạng thái thành công.'
        ]);
    }
}
