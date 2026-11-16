<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{id}', [ServiceController::class, 'show']);

Route::get('categories', fn() => response()->json([
    'success' => true,
    'message' => 'OK',
    'data'    => Category::select('id', 'name', 'slug')->get(),
]));

// Protected (needs Bearer token)
Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
});
