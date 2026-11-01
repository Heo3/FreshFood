<?php

use App\Http\Controllers\Client\AccountController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Client\CheckoutController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ProductController;
use Illuminate\Support\Facades\Route;

// ==========================================
// STOREFRONT PUBLIC ROUTES (Khách hàng xem)
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/chi-nhanh/chon', [HomeController::class, 'selectStore'])->name('store.select');

Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index');
Route::get('/api/products/search-suggestions', [ProductController::class, 'searchSuggestions'])->name('products.suggestions');
Route::get('/san-pham/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/danh-muc/{slug}', [ProductController::class, 'category'])->name('categories.show');

Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
// Cart API routes (accessible to guests, using session_id for unauthenticated users)
Route::post('/api/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/api/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/api/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/api/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/api/cart/check-coupon', [CartController::class, 'checkCoupon'])->name('cart.check-coupon');
Route::post('/api/cart/sync', [CartController::class, 'sync'])->name('cart.sync');
Route::post('/api/cart/refresh-prices', [CartController::class, 'refreshPrices'])->name('cart.refresh-prices');

Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/thanh-toan/thanh-cong/{maDonHang}', [CheckoutController::class, 'success'])->name('checkout.success');

Route::get('/tra-cuu-don-hang', [CheckoutController::class, 'tracking'])->name('orders.tracking');

// ==========================================
// CUSTOMER ACCOUNT PORTAL (Khách hàng đã đăng nhập)
// ==========================================
Route::middleware(['auth', 'role:khach_hang'])->prefix('tai-khoan')->as('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/don-hang', [AccountController::class, 'orders'])->name('orders');
    Route::get('/don-hang/{maDonHang}', [AccountController::class, 'orderDetail'])->name('orders.show');
    Route::get('/dia-chi', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/dia-chi', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/dia-chi/{id}', [AccountController::class, 'deleteAddress'])->name('addresses.destroy');
    Route::get('/diem-thuong', [AccountController::class, 'points'])->name('points');
    Route::post('/yeu-cau-doi-tra', [AccountController::class, 'requestReturn'])->name('return.store');
});
