<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
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

class OrderController extends Controller
{

    public function getOrders(Request $request)
    {
        $validated = $request->validate([
            'filter'   => 'nullable|string|in:all,last_30_days,last_3_months,last_6_months',
            'search'   => 'nullable|string',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $filter  = $validated['filter'] ?? 'all';
        $perPage = $validated['per_page'] ?? 10;
        $search  = $validated['search'] ?? null;

        // Base Query
        $query = Order::where('payment_status', 'paid');

        // Apply filter
        switch ($filter) {
            case 'last_30_days':
                $query->where('created_at', '>=', now()->subDays(30));
                break;
            case 'last_3_months':
                $query->where('created_at', '>=', now()->subMonths(3));
                break;
            case 'last_6_months':
                $query->where('created_at', '>=', now()->subMonths(6));
                break;
        }

        // Search by order number or product name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Fetch Orders with Relations
        $paidOrders = $query->select('id', 'user_id', 'order_number', 'created_at', 'subtotal', 'status',  'shipping_address as address')
            ->with([
                'items:id,order_id,product_id,product_name,quantity,price',
                'items.product:id,images', // Load product images
                'user:id',
                'user.addresses:id,user_id,name,mobile,street,city,state,postal_code,country,is_selected'
            ])
            ->latest()
            ->paginate($perPage);



        // Process images and addresses
        $paidOrders->transform(function ($order) {
            // Extract user address where is_selected = true
            // $address = $order->user->addresses->firstWhere('is_selected', true);
            // $order->address = $address ? "{$address->name}, {$address->mobile}, {$address->street}, {$address->city}, {$address->state}, {$address->postal_code}, {$address->country}" : "static address";

            // Process items and extract first image as "product_image"
            $order->items->transform(function ($item) {
                $images = $item->product->images;

                // Ensure images is properly formatted
                if (is_string($images)) {
                    $decodedImages = json_decode($images, true);
                } elseif (is_array($images)) {
                    $decodedImages = $images;
                } else {
                    $decodedImages = [];
                }

                // Extract first image URL
                $item->product_image = !empty($decodedImages) ? reset($decodedImages) : null;

                unset($item->product); // Remove unnecessary product object
                return $item;
            });

            return $order;
        });

        return response()->json([
            'status_code' => 1,
            'message'     => 'Orders fetched successfully',
            'data'        => $paidOrders,
        ]);
    }




    // public function getOrders(Request $request)
    // {
    //     // Validate the incoming request
    //     $validated = $request->validate([
    //         'filter' => 'nullable|string|in:all,last_30_days,last_3_months,last_6_months',
    //         'search' => 'nullable|string', // Allow search by order number or product name
    //         'page' => 'nullable|integer|min:1',
    //         'per_page' => 'nullable|integer|min:1|max:50', // Limit max per page
    //     ]);

    //     $filter = $validated['filter'] ?? 'all'; // Default to 'all'
    //     $perPage = $validated['per_page'] ?? 10; // Default pagination to 10 orders per page
    //     $search = $validated['search'] ?? null;

    //     $query = Order::where('payment_status', 'paid');

    //     // Apply filter based on time period
    //     switch ($filter) {
    //         case 'last_30_days':
    //             $query->where('created_at', '>=', now()->subDays(30));
    //             break;
    //         case 'last_3_months':
    //             $query->where('created_at', '>=', now()->subMonths(3));
    //             break;
    //         case 'last_6_months':
    //             $query->where('created_at', '>=', now()->subMonths(6));
    //             break;
    //         case 'all':
    //         default:
    //             // No filtering required for "All Time"
    //             break;
    //     }

    //     // Search by order number or product name
    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('order_number', 'LIKE', "%{$search}%") // Search in order number
    //                 ->orWhereHas('items', function ($itemQuery) use ($search) {
    //                     $itemQuery->where('product_name', 'LIKE', "%{$search}%"); // Search in order items' product name
    //                 });
    //         });
    //     }

    //     // Fetch paginated data
    //     $paidOrders = $query->select('id', 'order_number', 'created_at', 'subtotal', 'status')
    //         ->with(['items:id,order_id,product_id,product_name,quantity,price']) // Ensure items relation is loaded
    //         ->latest()
    //         ->paginate($perPage);

    //     return response()->json([
    //         'status_code' => 1,
    //         'message'     => 'Orders fetched successfully',
    //         'data' => $paidOrders
    //     ]);
    // }
}
