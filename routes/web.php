<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HppController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductHppController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserAccessController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware("throttle:10,1");
});

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    /* HOMEPAGE */
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');


    // Master Data: Komponen HPP
    Route::resource('hpp', HppController::class)->middleware('user.access:hpp');

    // Master Data: Satuan / Unit
    Route::resource('units', UnitController::class)->middleware('user.access:units');

    // Master Data: Produk & Komposisi HPP
    Route::post('products/calculate-hpp', [ProductController::class, 'calculateHpp'])
        ->name('products.calculate_hpp')
        ->middleware('user.access:products,index');
    Route::get('products/{product}/components', [ProductHppController::class, 'index'])
        ->name('products.components.index')
        ->middleware('user.access:products,show');
    Route::post('products/{product}/components', [ProductHppController::class, 'store'])
        ->name('products.components.store')
        ->middleware('user.access:products,edit');
    Route::resource('products', ProductController::class)->middleware('user.access:products');

    // Master Data: Users & Hak Akses
    Route::get('users/{user}/access', [UserAccessController::class, 'show'])
        ->name('users.access.show')
        ->middleware('user.access:users,show');
    Route::put('users/{user}/access', [UserAccessController::class, 'update'])
        ->name('users.access.update')
        ->middleware('user.access:users,edit');
    Route::resource('users', UserController::class)->middleware('user.access:users');

    // Transaksi: Restock
    Route::resource('restock', RestockController::class)->middleware('user.access:restock');

    // Transaksi & Monitoring: Laporan Penjualan & Margin
    Route::get('reports/summary', [ReportController::class, 'summary'])
        ->name('reports.summary')
        ->middleware('user.access:reports,index');
    Route::resource('reports', ReportController::class)->middleware('user.access:reports');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity_logs.index')
        ->middleware('user.access:activity_logs,index');
    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->name('activity_logs.show')
        ->middleware('user.access:activity_logs,show');
});