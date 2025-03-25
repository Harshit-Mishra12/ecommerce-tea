<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    private $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    /**
     * Process payment after frontend callback
     */
    public function processPayment(Request $request)
    {
        // Validate the request
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'order_id' => 'required',
            'coupon_code' => 'nullable|string' // Optional coupon code
        ]);

        try {
            // Verify the payment signature
            $attributes = [
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_signature' => $request->razorpay_signature
            ];
            $this->razorpay->utility->verifyPaymentSignature($attributes);

            // Find the order
            $order = Order::findOrFail($request->order_id);
            if ($order->razorpay_order_id !== $request->razorpay_order_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID mismatch'
                ], 400);
            }

            // Fetch payment details from Razorpay
            $payment = $this->razorpay->payment->fetch($request->razorpay_payment_id);

            // Check if payment is authorized or captured
            if ($payment->status === 'authorized' || $payment->status === 'captured') {
                $response = $this->processSuccessfulPayment($order, $payment);

                // Update coupon usage count if a valid coupon code is provided
                if (!empty($request->coupon_code)) {
                    $coupon = Coupon::where('code', $request->coupon_code)->first();

                    if ($coupon) {
                        $coupon->increment('used_count'); // Increase used count
                    }
                }

                return $response;
            } else {
                return $this->processFailedPayment($order, $payment);
            }
        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 400);
        }
    }


    protected function processSuccessfulPayment($order, $paymentData)
    {
        // Begin transaction
        DB::beginTransaction();

        try {
            // Update order status
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);

            // Create transaction record
            Transaction::create([
                'order_id' => $order->id,
                'transaction_id' => $paymentData->id,
                'payment_method' => $paymentData->method ?? 'razorpay',
                'status' => 'success',
                'amount' => $paymentData->amount / 100, // Convert paise to rupees
                'currency' => $paymentData->currency,
                'response_data' => json_encode($paymentData->toArray()),
            ]);

            // Update inventory
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrementStock($item->quantity);
                }
            }

            // You could also dispatch events for notifications here
            // event(new OrderPaid($order));

            // Clear the user's cart if needed
            if (Auth::check()) {
                Cart::where('user_id', Auth::id())->delete();
            }

            DB::commit();
            Log::info('Payment processed successfully for order: ' . $order->order_number);

            return response()->json([
                'status_code' => 1,
                'message' => 'Payment processed successfully',
                'data' => $order->load('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function processFailedPayment($order, $paymentData)
    {
        // Update order status
        $order->update([
            'payment_status' => 'failed',
        ]);

        // Create transaction record
        Transaction::create([
            'order_id' => $order->id,
            'transaction_id' => $paymentData->id,
            'payment_method' => $paymentData->method ?? 'razorpay',
            'status' => 'failed',
            'amount' => $paymentData->amount / 100, // Convert paise to rupees
            'currency' => $paymentData->currency,
            'response_data' => json_encode($paymentData->toArray()),
        ]);

        // You could also dispatch events for notifications here
        // event(new PaymentFailed($order));

        Log::info('Payment failed for order: ' . $order->order_number);

        return response()->json([
            'success' => false,
            'message' => 'Payment failed',
            'order' => $order
        ]);
    }
}
