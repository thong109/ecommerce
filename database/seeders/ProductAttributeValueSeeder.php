<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductAttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::first();

        // Lấy tất cả các thuộc tính
        $attributes = Attribute::all();

        foreach ($attributes as $attribute) {
            // Lấy giá trị của từng thuộc tính
            $attributeValue = AttributeValue::where('attribute_id', $attribute->id)->first();

            // Tạo dữ liệu cho bảng product_attribute_values
            ProductAttributeValue::create([
                'product_id' => $product->id,
                'attribute_id' => $attribute->id,
                'attribute_value_id' => $attributeValue->id,
            ]);
        }
    }
}
