<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'Sample Product',
            'description' => 'This is a sample product description.',
            'price' => 199.99,
            'stock' => 10,
            'images' => json_encode(['/storage/products/sample.jpg']),
            'is_active' => true,
        ]);
    }
}
