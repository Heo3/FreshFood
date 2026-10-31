<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================
// STORE BRANCH OPERATIONS & POS (/store)
// Dành cho nhân viên thu ngân, nhân viên kho & quản lý chi nhánh
// ==========================================
Route::middleware(['auth', 'role:nhan_vien,quan_ly'])->prefix('store')->as('store.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('store.pos.index');
    })->name('dashboard');

    Route::get('/pos', function () {
        return Inertia::render('store/pos/Index');
    })->name('pos.index');

    Route::get('/ton-kho', function () {
        return Inertia::render('store/inventory/Index');
    })->name('inventory.index');

    Route::get('/canh-bao-can-date', function () {
        return Inertia::render('store/inventory/NearExpiry');
    })->name('batches.expiry');

    Route::get('/phieu-nhap', function () {
        return Inertia::render('store/inbound/Index');
    })->name('inbound.index');

    Route::get('/phieu-xuat', function () {
        return Inertia::render('store/outbound/Index');
    })->name('outbound.index');

    Route::get('/kiem-ke', function () {
        return Inertia::render('store/audit/Index');
    })->name('audit.index');

    Route::get('/doi-tra', function () {
        return Inertia::render('store/returns/Index');
    })->name('returns.index');
});
