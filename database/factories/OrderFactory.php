<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;


class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        // Fetch a random user with role 'user'
        $user = User::where('role', 'user')->inRandomOrder()->first();

        return [
           'user_id' => User::where('role', 'user')->inRandomOrder()->value('id') ?? User::factory(),
            'order_id' => 'ORD-' . strtoupper(Str::random(6)),
            'status' => $this->faker->randomElement(['Pending', 'Shipped', 'Delivered', 'Cancelled']),
            'total_amount' => $this->faker->randomFloat(2, 10, 500), // Random amount between $10-$500
            'created_at' => now(),
        ];
    }
}

