<?php

use App\Http\Controllers\V1\AddressController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CartController;
use App\Http\Controllers\V1\CheckoutController;
use App\Http\Controllers\V1\CoupenController;
use App\Http\Controllers\V1\OrderController;
use App\Http\Controllers\V1\PaymentController;
use App\Http\Controllers\V1\ProductController;
use App\Http\Controllers\V1\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {

    Route::post("/auth/login", [AuthController::class, 'login']);
    Route::post("/auth/register", [AuthController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('user')->group(function () {
            Route::get("/products/fetch", [ProductController::class, 'fetchProducts']);

            Route::get("/products/similar/fetch", [ProductController::class, 'fetchSimilarProducts']);

            Route::post("/products/wishlist/save", [WishlistController::class, 'addToWishlist']);

            Route::get('/wishlist/add/{product_id}', [WishlistController::class, 'addToWishlist']);
            Route::get('/wishlist/remove/{product_id}', [WishlistController::class, 'removeFromWishlist']);
            Route::get('/wishlist', [WishlistController::class, 'getWishlist']);

            Route::post('/cart/add', [CartController::class, 'addToCart']);
            Route::post('/cart/increment', [CartController::class, 'incrementCartQuantity']);
            Route::post('/coupen/validate', [CoupenController::class, 'applyCouponBeforeCheckout']);
            Route::get('/coupons/fetch', [CoupenController::class, 'fetchCoupen']);

            Route::post('/cart/remove', [CartController::class, 'removeFromCart']);
            Route::get('/cart', [CartController::class, 'getCart']);

            Route::post('/change-password', [AuthController::class, 'changePassword']);

            Route::get('/addresses', [AddressController::class, 'index']);
            Route::post('/addresses', [AddressController::class, 'store']);
            Route::get('/addresses/{id}/select', [AddressController::class, 'selectAddress']);
            Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

            Route::post('/profile/save', [AddressController::class, 'updateProfile']);
            Route::post('/profile/fetch', [AddressController::class, 'getProfile']);


            Route::post('/checkout', [CheckoutController::class, 'checkout']);
            // Payment callback (frontend verification)
            // Route::post('/payment/callback', [PaymentController::class, 'paymentCallback']);

            Route::post('/payment/process', [PaymentController::class, 'processPayment']);
            //orders
            Route::post('/orders/fetch', [OrderController::class, 'getOrders']);
        });
    });

    Route::post('/webhook/razorpay', [PaymentController::class, 'handleWebhook']);

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {});
});
