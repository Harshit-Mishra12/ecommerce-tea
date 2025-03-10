<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;

class OrderSeeder extends Seeder
{
    public function run()
    {
        // Ensure we have users with the role "user"
        if (User::where('role', 'user')->count() === 0) {
            User::factory(5)->create([
                'role' => 'user',
            ]);
        }

        // Generate 10 dummy orders
        Order::factory(10)->create();
    }
}

