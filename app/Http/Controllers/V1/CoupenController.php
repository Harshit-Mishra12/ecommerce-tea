<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
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

class CoupenController extends Controller
{
    public function applyCouponBeforeCheckout(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'cart_total' => 'required|numeric|min:1',
        ]);

        $coupon = Coupon::where('code', $request->coupon_code)->first();

        // Check if coupon exists and is active
        if (!$coupon || !$coupon->is_active) {
            return response()->json(['status_code' => 2, 'message' => 'Invalid or inactive coupon']);
        }

        // Check if coupon is expired
        if ($coupon->expires_at && Carbon::parse($coupon->expires_at)->isPast()) {
            return response()->json(['status_code' => 2, 'message' => 'This coupon has expired']);
        }

        // Check if the coupon usage limit is exceeded
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['status_code' => 2, 'message' => 'This coupon has reached its usage limit']);
        }

        // Calculate discount
        $cartTotal = $request->cart_total;
        $discountAmount = $coupon->discount_type === 'percentage'
            ? ($cartTotal * $coupon->discount) / 100
            : $coupon->discount;

        $discountAmount = min($discountAmount, $cartTotal); // Ensure discount doesn't exceed cart total

        return response()->json([
            'status_code' => 1,
            'message' => 'Coupon applied successfully',
            'data' => [
                'discount' => number_format($discountAmount, 2),
                'total_after_discount' => number_format($cartTotal - $discountAmount, 2),
            ]
        ]);
    }

    public function fetchCoupen(Request $request)
    {
        // Fetch all active coupons
        $coupons = Coupon::where('is_active', true)->get();

        // Prepare response data
        $formattedCoupons = $coupons->map(function ($coupon) {
            return [
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'description' => $this->getCouponDescription($coupon),
                'condition' => 'No minimum purchase required',
                'isValid' => true, // Assuming all active coupons are valid
                'discount' => $this->formatDiscount($coupon) // Return function as string
            ];
        });

        return response()->json([
            'status_code' => 1,
            'message' => 'Coupon fetched successfully',
            'data' => $formattedCoupons
        ]);
    }

    private function getCouponDescription($coupon)
    {
        if ($coupon->discount_type == 'percentage') {
            return "{$coupon->discount}% off your order";
        } else {
            return "Flat ₹{$coupon->discount} off";
        }
    }

    private function formatDiscount($coupon)
    {
        if ($coupon->discount_type == 'percentage') {
            return "(subtotal) => subtotal * " . ($coupon->discount / 100);
        } else {
            return $coupon->discount; // Fixed discount as a number
        }
    }
}
