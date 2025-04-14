<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = Attribute::all();

        foreach ($attributes as $attribute) {
            // Tạo giá trị mẫu cho từng thuộc tính
            if ($attribute->name == 'Màu sắc') {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => 'Đỏ',
                ]);
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => 'Xanh',
                ]);
            } elseif ($attribute->name == 'Kích cỡ') {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => 'S',
                ]);
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => 'M',
                ]);
            }
        }
    }
}
