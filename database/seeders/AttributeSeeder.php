<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::first();

        Attribute::create([
            'name' => 'Màu sắc',
            'category_id' => $category->id
        ]);

        Attribute::create([
            'name' => 'Kích cỡ',
            'category_id' => $category->id
        ]);
    }
}
