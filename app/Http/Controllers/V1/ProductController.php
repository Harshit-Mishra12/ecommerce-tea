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
        return response()->json([
            'status_code' => 1,
            'message' => 'Product list retrieved successfully',
            'data' => $products
        ]);
    }
}
