<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BrandAdminController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
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

Route::post('/signup', [LoginController::class, 'register']);
Route::post('/login', [LoginController::class, 'submit']);
Route::get('/products', [ProductController::class, 'getProducts']);
Route::get('/products/related/{id}', [ProductController::class, 'getProductsRelated']);
Route::get('/product/{id}', [ProductController::class, 'getProductDetail']);
Route::get('/categories', [CategoryController::class, 'getCategories']);
Route::get('/brands', [BrandController::class, 'getBrands']);
Route::get('/categories/{category}/attributes', [CategoryController::class, 'getAttributes']);
Route::get('/settings', [SettingsController::class, 'settings']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [LoginController::class, 'logout']);

    // Cart
    Route::post('/add-to-cart', [CartController::class, 'addToCart']);
    Route::get('/carts/get/', [CartController::class, 'getCartItems']);
    Route::delete('/carts/delete/{id}', [CartController::class, 'deleteItemCart']);
    Route::post('/checkout', [CartController::class, 'checkout']);
    Route::post('/check-cart', [CartController::class, 'checkCart']);

    // --------Admin--------

    // Brand
    Route::get('/brands/all', [BrandAdminController::class, 'index']);
    Route::post('/brands/store', [BrandAdminController::class, 'store']);
    Route::get('/brands/show/{id}', [BrandAdminController::class, 'show']);
    Route::put('/brands/update/{id}', [BrandAdminController::class, 'update']);
    Route::post('/brands/destroy', [BrandAdminController::class, 'destroy']);
    Route::post('/brands/restore', [BrandAdminController::class, 'restore']);
    Route::post('/brands/status/change/{id}', [BrandAdminController::class, 'changeStatus']);
    // Product
    Route::get('/products/all', [ProductAdminController::class, 'index']);
    Route::post('/products/store', [ProductAdminController::class, 'store']);
    Route::post('/products/destroy', [ProductAdminController::class, 'destroy']);
    Route::get('/products/show/{id}', [ProductAdminController::class, 'show']);
    Route::post('/products/status/change/{id}', [ProductAdminController::class, 'changeStatus']);
    Route::put('/products/update/{id}', [ProductAdminController::class, 'update']);

    // Categories
    Route::get('/categories/all', [CategoryAdminController::class, 'index']);
    Route::post('/categories/store', [CategoryAdminController::class, 'store']);
    Route::get('/categories/show/{id}', [CategoryAdminController::class, 'show']);
    Route::put('/categories/update/{id}', [CategoryAdminController::class, 'update']);
    Route::post('/categories/destroy', [CategoryAdminController::class, 'destroy']);
    Route::post('/categories/restore', [CategoryAdminController::class, 'restore']);
    Route::post('/categories/status/change/{id}', [CategoryAdminController::class, 'changeStatus']);
    // User
    Route::get('/user', [UserController::class, 'me']);
    Route::get('/users', [AdminController::class, 'getAllUsers']);
    Route::get('/user/buy', [UserController::class, 'userBuy']);
    Route::get('/user/order/{id}', [UserController::class, 'orderDetail']);
    Route::post('/user/update/{id}', [UserController::class, 'update']);
    Route::post('/user/destroy', [UserController::class, 'destroy']);
    // Wishlist
    Route::get('/wishlists', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);

    // Coupon
    Route::post('/check-coupon', [CouponController::class, 'validateCouponForUser']);

    // Order
    Route::get('/order/all', [OrderController::class, 'index']);
    Route::get('/order/show/{id}', [OrderController::class, 'show']);
    Route::post('/order/update', [OrderController::class, 'update']);

    // Export
    Route::get('/products/export', [ProductAdminController::class, 'export']);

    // Copy
    Route::post('/products/copy', [ProductAdminController::class, 'copy']);

    // Upload by Zip
    Route::post('/products/upload-zip', [ProductAdminController::class, 'uploadZip']);

    // Restore
    Route::post('/products/restore', [ProductAdminController::class, 'restore']);

    // Statistics
    Route::get('/sales-statistics', [StatisticsController::class, 'monthlySales']);
    Route::post('/sales-statistics-date', [StatisticsController::class, 'dateSales']);

    // Settings
    Route::post('/settings', [SettingsController::class, 'setSettings']);

    // Payment
    Route::post('/payment/momo', [PaymentController::class, 'createVnpayPayment'])->name('payment.momo_notify');
    Route::post('/payment/momo-result', [PaymentController::class, 'handleVnpayReturn'])->name('payment.momo_return');
});
