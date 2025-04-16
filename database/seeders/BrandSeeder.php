<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        for ($i = 1; $i <= 5; $i++) {
            Brand::create([
                'name' => $faker->name(),
                'image' => 'https://picsum.photos/150/150' . '?random=' . $i,
            ]);
        }
    }
}
