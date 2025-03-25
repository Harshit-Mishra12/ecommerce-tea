<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
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

    // Webhook handler for Razorpay
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();

        try {
            $this->razorpay->utility->verifyWebhookSignature(
                $webhookBody,
                $webhookSignature,
                config('services.razorpay.webhook_secret')
            );
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Process the webhook payload
        $payload = json_decode($webhookBody, true);
        $event = $payload['event'];

        Log::info('Razorpay Webhook received: ' . $event);

        if ($event === 'payment.authorized' || $event === 'payment.captured') {
            return $this->handleSuccessfulPayment($payload);
        } elseif ($event === 'payment.failed') {
            return $this->handleFailedPayment($payload);
        }

        return response()->json(['message' => 'Webhook received but no action taken']);
    }

    protected function handleSuccessfulPayment($payload)
    {
        $paymentData = $payload['payload']['payment']['entity'];
        $razorpayOrderId = $paymentData['order_id'];
        $razorpayPaymentId = $paymentData['id'];

        // Find the order by Razorpay order ID
        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        if (!$order) {
            Log::error('Order not found for Razorpay order ID: ' . $razorpayOrderId);
            return response()->json(['error' => 'Order not found'], 404);
        }

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
                'transaction_id' => $razorpayPaymentId,
                'payment_method' => $paymentData['method'] ?? 'razorpay',
                'status' => 'success',
                'amount' => $paymentData['amount'] / 100, // Convert paise to rupees
                'currency' => $paymentData['currency'],
                'response_data' => json_encode($paymentData),
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

            DB::commit();
            Log::info('Payment processed successfully for order: ' . $order->order_number);

            return response()->json(['message' => 'Payment processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment: ' . $e->getMessage());
            return response()->json(['error' => 'Error processing payment'], 500);
        }
    }

    protected function handleFailedPayment($payload)
    {
        $paymentData = $payload['payload']['payment']['entity'];
        $razorpayOrderId = $paymentData['order_id'];
        $razorpayPaymentId = $paymentData['id'];

        // Find the order by Razorpay order ID
        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        if (!$order) {
            Log::error('Order not found for Razorpay order ID: ' . $razorpayOrderId);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Update order status
        $order->update([
            'payment_status' => 'failed',
        ]);

        // Create transaction record
        Transaction::create([
            'order_id' => $order->id,
            'transaction_id' => $razorpayPaymentId,
            'payment_method' => $paymentData['method'] ?? 'razorpay',
            'status' => 'failed',
            'amount' => $paymentData['amount'] / 100, // Convert paise to rupees
            'currency' => $paymentData['currency'],
            'response_data' => json_encode($paymentData),
        ]);

        // You could also dispatch events for notifications here
        // event(new PaymentFailed($order));

        Log::info('Payment failed for order: ' . $order->order_number);

        return response()->json(['message' => 'Payment failure recorded']);
    }

    // Frontend callback - optional but useful for redirecting users
    public function paymentCallback(Request $request)
    {
        $razorpayPaymentId = $request->razorpay_payment_id;
        $razorpayOrderId = $request->razorpay_order_id;
        $razorpaySignature = $request->razorpay_signature;

        // This is just an extra verification if you want to verify from frontend as well
        // Webhook should already have processed the payment
        try {
            $attributes = [
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_signature' => $razorpaySignature
            ];

            $this->razorpay->utility->verifyPaymentSignature($attributes);

            $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Payment verification successful',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
