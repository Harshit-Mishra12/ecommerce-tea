<?php

use App\Http\Controllers\V1\AddressController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CartController;
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
            Route::post("/products/wishlist/save", [WishlistController::class, 'addToWishlist']);

            Route::get('/wishlist/add/{product_id}', [WishlistController::class, 'addToWishlist']);
            Route::get('/wishlist/remove/{product_id}', [WishlistController::class, 'removeFromWishlist']);
            Route::get('/wishlist', [WishlistController::class, 'getWishlist']);

            Route::post('/cart/add', [CartController::class, 'addToCart']);
            Route::post('/cart/remove', [CartController::class, 'removeFromCart']);
            Route::get('/cart', [CartController::class, 'getCart']);

            Route::post('/change-password', [AuthController::class, 'changePassword']);

            Route::get('/addresses', [AddressController::class, 'index']);
            Route::post('/addresses', [AddressController::class, 'store']);
            Route::post('/addresses/{id}/select', [AddressController::class, 'selectAddress']);
            Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        });
    });

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {});
});
