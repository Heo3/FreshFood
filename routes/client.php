<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================
// STOREFRONT PUBLIC ROUTES (Khách hàng xem)
// ==========================================
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/san-pham', function () {
    return Inertia::render('client/Products');
})->name('products.index');

Route::get('/san-pham/{slug}', function (string $slug) {
    return Inertia::render('client/ProductDetail', ['slug' => $slug]);
})->name('products.show');

Route::get('/danh-muc/{slug}', function (string $slug) {
    return Inertia::render('client/Category', ['slug' => $slug]);
})->name('categories.show');

Route::get('/gio-hang', function () {
    return Inertia::render('client/Cart');
})->name('cart.index');

Route::get('/thanh-toan', function () {
    return Inertia::render('client/Checkout');
})->name('checkout.index');

Route::get('/tra-cuu-don-hang', function () {
    return Inertia::render('client/OrderTracking');
})->name('orders.tracking');

// ==========================================
// CUSTOMER ACCOUNT PORTAL (Khách hàng đã đăng nhập)
// ==========================================
Route::middleware(['auth', 'role:khach_hang'])->prefix('tai-khoan')->as('account.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('client/account/Index');
    })->name('index');

    Route::get('/don-hang', function () {
        return Inertia::render('client/account/Orders');
    })->name('orders');

    Route::get('/don-hang/{maDonHang}', function (string $maDonHang) {
        return Inertia::render('client/account/OrderDetail', ['maDonHang' => $maDonHang]);
    })->name('orders.show');

    Route::get('/dia-chi', function () {
        return Inertia::render('client/account/Addresses');
    })->name('addresses');

    Route::get('/diem-thuong', function () {
        return Inertia::render('client/account/Points');
    })->name('points');
});
