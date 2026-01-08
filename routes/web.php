<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\CheckoutController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\ProductListController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Webhooks\TrackingMoreWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Http\Controllers\Admin\CourierController;

//user routes
Route::get('/', [UserController::class, 'index'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/orders/{order}/download-invoice', [DashboardController::class, 'downloadInvoice'])
    ->middleware(['auth', 'verified'])
    ->name('orders.download.invoice');

Route::get('/orders/{order}/tracking', [DashboardController::class, 'tracking'])
    ->middleware(['auth', 'verified'])
    ->name('orders.tracking');

// Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //checkout
    Route::prefix('checkout')->controller(CheckoutController::class)->group(function () {
        Route::post('order', 'store')->name('checkout.store');
        Route::get('success', 'success')->name('checkout.success');
        Route::get('cancel', 'cancel')->name('checkout.cancel');
    });
// });

//add to cart
Route::prefix('cart')->controller(CartController::class)->group(function () {
    Route::get('view', 'view')->name('cart.view');
    Route::post('/store/{product}', 'store')->name('cart.store');
    Route::patch('/update/{product}', 'update')->name('cart.update');
    Route::delete('/delete/{product}', 'delete')->name('cart.delete');
});
//end

//routes for product list
Route::prefix('products')->controller(ProductListController::class)->group(function () {
    Route::get('/', 'index')->name('products.index');
});
//admin routes

Route::group(['prefix' => 'admin'], function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/sales-performance', [AdminController::class, 'salesPerformance']);
    Route::get('/sales-report', [AdminController::class, 'salesReport'])->name('admin.sales.report');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::get('/couriers', [CourierController::class, 'index'])->name('admin.couriers.index');

    Route::post('/orders/{order}/shipment', [ShipmentController::class, 'store'])->name('admin.orders.shipment.store');

    Route::get('/products', [ProductController::class, 'index'])->name('admin.products.index');
    Route::post('/products/store', [ProductController::class, 'store'])->name('admin.products.store');
    Route::put('/products/update/{id}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/image/{id}', [ProductController::class, 'deleteImage'])->name('admin.products.image.delete');
    Route::delete('/products/destroy/{id}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
});

Route::post('/webhooks/trackingmore', [TrackingMoreWebhookController::class, 'handle'])->name('webhooks.trackingmore');
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.stripe');
require __DIR__.'/auth.php';
