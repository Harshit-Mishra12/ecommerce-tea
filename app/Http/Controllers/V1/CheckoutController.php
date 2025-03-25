<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Razorpay\Api\Api;

class CheckoutController extends Controller
{
    private $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|integer|exists:addresses,id',
            'billing_address_id' => 'required|integer|exists:addresses,id',
            'amount' => 'required|numeric'
        ]);



        $user = auth()->user();
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

        // Ensure cart is not empty
        if ($cartItems->isEmpty()) {
            return response()->json([
                'error' => 'Cart is empty'
            ], 400);
        }

        $shippingAddress = Address::findOrFail($request->shipping_address_id);
        // Concatenate address details
        $formattedShippingAddress = "{$shippingAddress->name}, {$shippingAddress->mobile}, {$shippingAddress->street}, {$shippingAddress->city}, {$shippingAddress->state}, {$shippingAddress->postal_code}, {$shippingAddress->country}";
        // Calculate order totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $tax = $subtotal * 0.18; // 18% tax example
        //   $shipping = 50; // Fixed shipping rate
        $total = $subtotal;

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'subtotal' => $subtotal,
            // 'tax' => $tax,
            //   'shipping' => $shipping,
            'total' => $request->amount,
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipping_address' => $formattedShippingAddress,
            'billing_address' => $formattedShippingAddress,
        ]);

        // Create order items
        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
                'subtotal' => $item->product->price * $item->quantity,
            ]);
        }

        // Create Razorpay order
        $razorpayOrder = $this->razorpay->order->create([
            'amount' => $total * 100, // Razorpay requires amount in paise
            'currency' => 'INR',
            'receipt' => $order->order_number,
            'notes' => [
                'order_id' => $order->id,
                'shipping_address' => $order->shipping_address,
            ],
        ]);

        // Update order with Razorpay order ID
        $order->update([
            'razorpay_order_id' => $razorpayOrder->id,
        ]);

        // Clear user's cart after successful order creation
        //  Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'status_code' => 1,
            'message' => 'Order created successfully',
            'data' => [
                'order' => $order,
                'razorpay_order_id' => $razorpayOrder->id,
                'razorpay_key' => config('services.razorpay.key'),
                'amount' => $total,
                'currency' => 'INR',
                'name' => config('app.name'),
                'description' => 'Order #' . $order->order_number,
                'prefill' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact' => $user->phone ?? '',
                ],
            ],
        ]);
    }
}
