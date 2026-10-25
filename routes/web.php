<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function () {
    // Executive Analytics Dashboard & Live Auto-Sync
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/live', [DashboardController::class, 'liveDashboard'])->name('dashboard.live');

    // Orders Explorer & On-Demand Item Drawer
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{id}/items', [OrderController::class, 'items'])->name('orders.items');

    // Customers Directory & Profile Modal
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');

    // Product Metrics Leaderboard
    Route::get('/products', [ProductController::class, 'index'])->name('products');
});
