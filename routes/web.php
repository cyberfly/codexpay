<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProductOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('products', [CatalogController::class, 'products'])->name('products.index');
Route::get('products/{product}', [CatalogController::class, 'show'])->name('products.show');
Route::post('products/{product}/coupon-preview', [ProductOrderController::class, 'previewCoupon'])
    ->middleware('throttle:coupon-preview')
    ->name('products.coupons.preview');
Route::post('products/{product}/orders', [ProductOrderController::class, 'store'])
    ->middleware('throttle:order-submission')
    ->name('products.orders.store');
Route::get('orders/{order}/confirmation', [ProductOrderController::class, 'confirmation'])
    ->name('orders.confirmation');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');

    Route::prefix('admin')->as('admin.')->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
        Route::livewire('products', 'pages::admin.products')->name('products');
        Route::livewire('coupons', 'pages::admin.coupons')->name('coupons');
        Route::livewire('orders', 'pages::admin.orders')->name('orders');
        Route::livewire('orders/{order}', 'pages::admin.order')->name('orders.show');
    });
});

require __DIR__.'/settings.php';
