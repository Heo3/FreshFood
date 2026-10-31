<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================
// ADMIN & MANAGER PORTAL (/admin)
// ==========================================
Route::middleware(['auth', 'role:admin,quan_ly'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('admin/Dashboard');
    })->name('dashboard');

    Route::get('/san-pham', function () {
        return Inertia::render('admin/products/Index');
    })->name('products.index');

    Route::get('/danh-muc', function () {
        return Inertia::render('admin/categories/Index');
    })->name('categories.index');

    Route::get('/don-hang', function () {
        return Inertia::render('admin/orders/Index');
    })->name('orders.index');

    Route::get('/khuyen-mai', function () {
        return Inertia::render('admin/coupons/Index');
    })->name('coupons.index');

    Route::get('/nha-cung-cap', function () {
        return Inertia::render('admin/suppliers/Index');
    })->name('suppliers.index');

    Route::get('/bao-cao', function () {
        return Inertia::render('admin/reports/Index');
    })->name('reports.index');

    // Các tính năng cấu hình toàn hệ thống dành riêng cho Admin tối cao
    Route::middleware('role:admin')->group(function () {
        Route::get('/cua-hang', function () {
            return Inertia::render('admin/stores/Index');
        })->name('stores.index');

        Route::get('/nhan-su', function () {
            return Inertia::render('admin/users/Index');
        })->name('users.index');

        Route::get('/cai-dat-he-thong', function () {
            return Inertia::render('admin/settings/Index');
        })->name('settings.index');
    });
});
