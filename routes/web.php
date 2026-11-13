<?php

use Illuminate\Support\Facades\Route;

// Dashboard chung sau đăng nhập -> chuyển hướng về Trang chủ storefront
Route::get('dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

// Phân hệ Khách hàng & Trang bán lẻ Storefront
require __DIR__.'/client.php';

// Phân hệ Quản trị & Ban điều hành hệ thống
require __DIR__.'/admin.php';

// Phân hệ Vận hành Chi nhánh & POS Bán hàng
require __DIR__.'/store.php';

// Auth & Settings mặc định
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
