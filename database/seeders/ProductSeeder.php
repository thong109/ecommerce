<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
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

        for ($i = 1; $i <= 5; $i++) {
            Category::create([
                'name' => $faker->name(),
                'image' => 'https://picsum.photos/150/150' . '?random=' . $i,
            ]);
        }

        for ($i = 1; $i <= 5; $i++) {
            Brand::create([
                'name' => $faker->name(),
                'image' => 'https://picsum.photos/150/150' . '?random=' . $i,
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            Product::create([
                'name' => $faker->name,
                'price' => rand(5000, 10000),
                'cost' => rand(1000, 4999),
                'image' => 'https://picsum.photos/150/150?random=' . $i,
                'rating' => round(rand(0, 50) / 10, 1),
                'category_id' => rand(1, 5),
                'brand_id' => rand(1, 5),
                'quantity' => rand(5, 100),
                'short_desc' => 'Coat with quilted lining and an adjustable hood. Featuring long sleeves with adjustable cuff tabs, adjustable asymmetric hem with elastic side tabs and a front zip fastening with placket.',
                'description' => 'A Pocket PC is a handheld computer, which features many of the same capabilities as a modern PC. These handy little devices allow individuals to retrieve and store e-mail messages, create a contact file, coordinate appointments, surf the internet, exchange text messages and more. Every product that is labeled as a Pocket PC must be accompanied with specific software to operate the unit and must feature a touchscreen and touchpad.As is the case with any new technology product, the cost of a Pocket PC was substantial during it’s early release. For approximately $700.00, consumers could purchase one of top-of-the-line Pocket PCs in 2003. These days, customers are finding that prices have become much more reasonable now that the newness is wearing off. For approximately $350.00, a new Pocket PC can now be purchased.',
                'tag' => 'Clothes,Skin,Body'
            ]);
        }
    }
}
