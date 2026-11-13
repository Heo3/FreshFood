<?php

use App\Http\Controllers\Store\DeliveryController;
use App\Http\Controllers\Store\PhieuDoiTraController;
use App\Http\Controllers\Store\PhieuKiemKeController;
use App\Http\Controllers\Store\PhieuNhapController;
use App\Http\Controllers\Store\PhieuXuatController;
use App\Http\Controllers\Store\StoreOpsController;
use Illuminate\Support\Facades\Route;

// ==========================================
// STORE BRANCH OPERATIONS & POS (/store)
// Dành cho nhân viên thu ngân, nhân viên kho, quản lý chi nhánh & admin
// ==========================================
Route::middleware(['auth', 'role:nhan_vien,quan_ly,admin'])->prefix('store')->as('store.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('store.pos.index');
    })->name('dashboard');

    // POS Bán lẻ tại quầy
    Route::get('/pos', [StoreOpsController::class, 'pos'])->name('pos.index');
    Route::get('/pos/customer', [StoreOpsController::class, 'findCustomer'])->name('pos.customer');
    Route::post('/pos/thanh-toan', [StoreOpsController::class, 'checkoutPos'])->name('pos.checkout');

    // Tồn kho & Lô hàng
    Route::get('/ton-kho', [StoreOpsController::class, 'inventory'])->name('inventory.index');

    // Cảnh báo cận date & Xả hàng / Hủy hàng
    Route::get('/canh-bao-can-date', [StoreOpsController::class, 'nearExpiry'])->name('batches.expiry');
    Route::post('/canh-bao-can-date/giam-gia', [StoreOpsController::class, 'applyDiscount'])->name('batches.discount');
    Route::post('/canh-bao-can-date/xuat-huy', [StoreOpsController::class, 'discardLot'])->name('batches.discard');

    // Nhập kho theo lô HSD
    Route::get('/phieu-nhap', [PhieuNhapController::class, 'index'])->name('inbound.index');
    Route::get('/phieu-nhap/tao-moi', [PhieuNhapController::class, 'create'])->name('inbound.create');
    Route::post('/phieu-nhap', [PhieuNhapController::class, 'store'])->name('inbound.store');

    // Xuất kho (FEFO bán hàng, hủy, điều chuyển)
    Route::get('/phieu-xuat', [PhieuXuatController::class, 'index'])->name('outbound.index');
    Route::get('/phieu-xuat/{id}', [PhieuXuatController::class, 'show'])->name('outbound.show');
    Route::post('/phieu-xuat/soan-hang/{id}', [PhieuXuatController::class, 'fulfillOrder'])->name('outbound.fulfill');

    // Quản lý đội xe & Điều phối giao hàng (Xe tải / Xe máy)
    Route::get('/doi-xe', [DeliveryController::class, 'fleetIndex'])->name('delivery.fleet');
    Route::post('/doi-xe/{id}', [DeliveryController::class, 'updateDriver'])->name('delivery.update-driver');
    Route::post('/don-hang/{id}/dieu-phoi', [DeliveryController::class, 'assignDelivery'])->name('delivery.assign');

    // Điều chuyển hàng hóa giữa các chi nhánh
    Route::get('/dieu-chuyen', [PhieuXuatController::class, 'createTransfer'])->name('transfer.create');
    Route::post('/dieu-chuyen', [PhieuXuatController::class, 'storeTransfer'])->name('transfer.store');
    Route::post('/dieu-chuyen/{id}/xac-nhan', [PhieuXuatController::class, 'confirmTransfer'])->name('transfer.confirm');

    // Kiểm kê kho & Cân bằng tồn kho
    Route::get('/kiem-ke', [PhieuKiemKeController::class, 'index'])->name('audit.index');
    Route::get('/kiem-ke/tao-moi', [PhieuKiemKeController::class, 'create'])->name('audit.create');
    Route::post('/kiem-ke', [PhieuKiemKeController::class, 'store'])->name('audit.store');

    // Duyệt xử lý đổi trả hàng dập nát 24h
    Route::get('/doi-tra', [PhieuDoiTraController::class, 'index'])->name('returns.index');
    Route::post('/doi-tra/{id}/duyet', [PhieuDoiTraController::class, 'approve'])->name('returns.approve');
    Route::post('/doi-tra/{id}/tu-choi', [PhieuDoiTraController::class, 'reject'])->name('returns.reject');
});

// ==========================================
// DELIVERY STAFF PORTAL (Dành cho tài xế xe tải & shipper xe máy)
// ==========================================
Route::middleware(['auth', 'role:giao_hang,quan_ly,admin'])->prefix('giao-hang')->as('delivery.')->group(function () {
    Route::get('/', [DeliveryController::class, 'myDeliveries'])->name('index');
    Route::post('/don-hang/{id}/hoan-thanh', [DeliveryController::class, 'completeDelivery'])->name('complete');
    Route::post('/don-hang/{id}/bao-cao-loi', [DeliveryController::class, 'failDelivery'])->name('fail');
});
