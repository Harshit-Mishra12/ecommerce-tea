<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\SubscriptionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function fetchProducts()
    {
        $products = Product::all();

        // Transform the products to include only the first image as product_image
        $transformedProducts = $products->map(function ($product) {
            $images = $product->images;
            $firstImage = !empty($images) ? reset($images) : null;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'product_image' => $firstImage,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
        });

        return response()->json([
            'status_code' => 1,
            'message' => 'Product list retrieved successfully',
            'data' => $transformedProducts
        ]);
    }


    public function fetchSimilarProducts()
    {
        $userId = auth()->id(); // Get authenticated user ID

        // Fetch all cart records for the user
        $cartItems = Cart::where('user_id', $userId)->get();

        // Extract all product IDs from all cart entries
        $cartProductIds = $cartItems->pluck('product_id')->unique()->values()->all();


        // Fetch products NOT in the cart
        $similarProducts = Product::whereNotIn('id', $cartProductIds)->get();


        // Transform products to include formatted product images
        $transformedProducts = $similarProducts->map(function ($product) {
            $images = $product->images; // Assuming images are stored as an array or JSON
            $firstImage = !empty($images) ? reset($images) : null;
            $imagePath = $firstImage ? "products/" . $firstImage : null; // Format the image path

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'product_image' => $imagePath, // Use the same image logic
                'is_active' => $product->is_active,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
        });

        return response()->json([
            'status_code' => 1,
            'message' => 'Products retrieved successfully',
            'data' => $transformedProducts
        ]);
    }
}
