<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SubscriptionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
}
