<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run()
    {
        Coupon::insert([
            [
                'code' => 'TEA10',
                'discount' => 10,
                'discount_type' => 'percentage',
                //'minimum_purchase' => null,
                'usage_limit' => 100,
                'used_count' => 0,
                'expires_at' => Carbon::now()->addDays(30),
                'is_active' => true,
            ],
            [
                'code' => 'FIRSTBREW',
                'discount' => 15,
                'discount_type' => 'percentage',
                //  'minimum_purchase' => null,
                'usage_limit' => 1,
                'used_count' => 0,
                'expires_at' => Carbon::now()->addDays(60),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP25',
                'discount' => 500, // Free shipping (assuming shipping is 500)
                'discount_type' => 'fixed',
                //  'minimum_purchase' => 25.00,
                'usage_limit' => null,
                'used_count' => 0,
                'expires_at' => null,
                'is_active' => true,
            ],
            [
                'code' => 'BULKTEA',
                'discount' => 20,
                'discount_type' => 'percentage',
                //  'minimum_purchase' => 75.00,
                'usage_limit' => 50,
                'used_count' => 0,
                'expires_at' => Carbon::now()->addDays(90),
                'is_active' => true,
            ],
        ]);
    }
}
