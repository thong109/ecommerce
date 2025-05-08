<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            Coupon::create([
                'code' => $this->generateRandomString(5),
                'type' => 'fixed',
                'value' => 20000,
                'usage_limit' => 50,
                'used' => 0,
                'start_date' => gmdate('Y-m-d', strtotime('2026-04-01')),
                'end_date' => gmdate('Y-m-d', strtotime('2026-04-30')),
                'active' => 1
            ]);
        }
    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
