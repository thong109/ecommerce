<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
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

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::post('/user/update/{id}', [UserController::class, 'update']);
    Route::get('/user', [UserController::class, 'me']);
    // Route::get('/user', function (Request $request) {
    //     $user = $request->user()->load('user_info');
    //     return response()->json([
    //         'id' => $user->id,
    //         'name' => $user->name,
    //         'email' => $user->email,
    //         'created_at' => $user->created_at,
    //         'user_info' => $user->user_info,
    //     ]);
    // });
});
