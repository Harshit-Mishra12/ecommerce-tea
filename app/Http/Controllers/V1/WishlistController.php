<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SubscriptionDetail;
use App\Models\User;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    public function addToWishlist($product_id)
    {
        // Validate that the product exists
        if (!Product::where('id', $product_id)->exists()) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Add to wishlist if not already added
        $wishlist = Wishlist::firstOrCreate([
            'user_id' => Auth::id(),
            'product_id' => $product_id
        ]);

        return response()->json(['status_code' => 1,'message' => 'Product added to wishlist', 'data' => $wishlist]);
    }

    // Remove from Wishlist (Using GET request with product_id in URL)
    public function removeFromWishlist($product_id)
    {
        // Validate that the product exists in the wishlist
        $deleted = Wishlist::where([
            'user_id' => Auth::id(),
            'product_id' => $product_id
        ])->delete();

        if ($deleted) {
            return response()->json(['status_code' => 1,'message' => 'Product removed from wishlist']);
        }

        return response()->json(['status_code' => 2,'message' => 'Product not found in wishlist']);
    }

    public function getWishlist()
    {
        $wishlist = Wishlist::where('user_id', auth()->id())->with('product')->get();

        return response()->json(['status_code' => 1,'data' => $wishlist]);
    }
}
