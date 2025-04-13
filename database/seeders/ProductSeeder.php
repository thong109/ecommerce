<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 40; $i++) {
            Product::create([
                'name' => $faker->name,
                'price' => rand(1000, 10000),
                'image' => 'https://picsum.photos/150/150?random=' . $i,
                'rating' => round(rand(0, 50) / 10, 1),
                'category_id' => rand(1, 10),
                'brand_id' => rand(1, 10),
            ]);
        }
    }
}
