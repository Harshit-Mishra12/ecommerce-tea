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

        // Create or update cart entry
        $cart = Cart::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        // Fetch updated cart with product details
        $cartItems = Cart::where('user_id', auth()->id())->with('product')->get();

        // Calculate total price per product & subtotal
        $subtotal = 0;
        $cartItems->transform(function ($item) use (&$subtotal) {
            $item->total_price = $item->product->price * $item->quantity;
            $subtotal += $item->total_price;
            return $item;
        });

        return response()->json([
            'status_code' => 1,
            'message' => 'Cart fetched',
            'data' => $cartItems,
            'subtotal' => $subtotal
        ]);
    }


    public function incrementCartQuantity(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        // Find or create the cart item
        $cartItem = Cart::firstOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $request->product_id],
            ['quantity' => 0] // Default quantity 0, will increment below
        );

        // Increment quantity
        $cartItem->increment('quantity');

        // Fetch updated cart with product details
        $cartItems = Cart::where('user_id', auth()->id())->with('product')->get();

        // Calculate total price per product & subtotal
        $subtotal = 0;
        $cartItems->transform(function ($item) use (&$subtotal) {
            $item->total_price = $item->product->price * $item->quantity;
            $subtotal += $item->total_price;
            return $item;
        });

        return response()->json([
            'status_code' => 1,
            'message' => 'Cart fetched',
            'data' => $cartItems,
            'subtotal' => $subtotal
        ]);
    }




    public function removeFromCart(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        Cart::where([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id
        ])->delete();

        $cart = Cart::where('user_id', auth()->id())->with('product')->get();

        // Calculate total for each product and overall subtotal
        $subtotal = 0;
        $cart->transform(function ($item) use (&$subtotal) {
            $item->total_price = $item->product->price * $item->quantity;
            $subtotal += $item->total_price;
            return $item;
        });

        return response()->json([
            'status_code' => 1,
            'message' => 'Cart fetched',
            'data' => $cart,
            'subtotal' => $subtotal, // ✅ Add subtotal
        ]);
    }
    public function getCart()
    {
        $cart = Cart::where('user_id', auth()->id())->with('product')->get();

        // Calculate total for each product and overall subtotal
        $subtotal = 0;
        $cart->transform(function ($item) use (&$subtotal) {
            $item->total_price = $item->product->price * $item->quantity;
            $subtotal += $item->total_price;
            return $item;
        });

        return response()->json([
            'status_code' => 1,
            'message' => 'Cart fetched',
            'data' => $cart,
            'subtotal' => $subtotal, // ✅ Add subtotal
        ]);
    }
}
