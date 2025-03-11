<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
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

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = Cart::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );
        return response()->json(['status_code' => 1,'message' => 'Product added to cart', 'data' => $cart]);
    }

    public function removeFromCart(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        Cart::where([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id
        ])->delete();
        return response()->json(['status_code' => 1,'message' => 'Product removed from cart']);
    }

    public function getCart()
    {
        $cart = Cart::where('user_id', auth()->id())->with('product')->get();

        return response()->json(['status_code' => 1,'message' => 'Products fetched','data' => $cart]);
    }
}
