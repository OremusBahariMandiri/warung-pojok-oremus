<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\HppController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductHppController;
use App\Http\Controllers\UserAccessController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('products.index');
});

// Master Data: Komponen HPP
Route::resource('hpp', HppController::class);

// Master Data: Produk & Komposisi HPP
Route::post('products/calculate-hpp', [ProductController::class, 'calculateHpp'])->name('products.calculate_hpp');
Route::get('products/{product}/components', [ProductHppController::class, 'index'])->name('products.components.index');
Route::post('products/{product}/components', [ProductHppController::class, 'store'])->name('products.components.store');
Route::resource('products', ProductController::class);

// Master Data: Users & Hak Akses
Route::get('users/{user}/access', [UserAccessController::class, 'show'])->name('users.access.show');
Route::put('users/{user}/access', [UserAccessController::class, 'update'])->name('users.access.update');
Route::resource('users', UserController::class);

// Activity Logs
Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity_logs.show');
