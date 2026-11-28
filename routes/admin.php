<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupportChatController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// ==========================================
// ADMIN & MANAGER PORTAL (/admin)
// ==========================================
Route::middleware(['auth', 'role:admin,quan_ly'])->prefix('admin')->as('admin.')->group(function () {
    // 1. Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Sản phẩm & Bảng giá chi nhánh
    Route::resource('san-pham', ProductController::class)->names('products');
    Route::post('san-pham/{sanPham}/gia-chi-nhanh', [ProductController::class, 'updateBranchPrice'])->name('products.branch-price');
    Route::delete('san-pham/hinh-anh/{hinhAnh}', [ProductController::class, 'deleteImage'])->name('products.delete-image');
    Route::post('san-pham/hinh-anh/{hinhAnh}/chinh', [ProductController::class, 'setPrimaryImage'])->name('products.set-primary-image');

    // 3. Danh mục
    Route::resource('danh-muc', CategoryController::class)->except(['create', 'show', 'edit'])->names('categories');

    // 4. Đơn hàng
    Route::get('don-hang/export', [OrderController::class, 'exportCsv'])->name('orders.export');
    Route::resource('don-hang', OrderController::class)->only(['index', 'show'])->names('orders');
    Route::post('don-hang/{donHang}/trang-thai', [OrderController::class, 'updateStatus'])->name('orders.update-status');

    // 5. Khuyến mãi & Voucher
    Route::resource('khuyen-mai', CouponController::class)->except(['create', 'show', 'edit'])->names('coupons');

    // 6. Nhà cung cấp
    Route::resource('nha-cung-cap', SupplierController::class)->except(['create', 'show', 'edit'])->names('suppliers');

    // 7. Báo cáo thống kê
    Route::get('bao-cao/export', [ReportController::class, 'exportCsv'])->name('reports.export');
    Route::get('bao-cao', [ReportController::class, 'index'])->name('reports.index');

    // 8. Đánh giá & Phản hồi khách hàng
    Route::get('danh-gia', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('danh-gia/{review}/toggle', [ReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('danh-gia/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // 9. Banner & Quảng cáo trang chủ
    Route::resource('banners', BannerController::class)->except(['create', 'show', 'edit'])->names('banners');
    Route::post('banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');

    // 10. Chăm sóc khách hàng & Khiếu nại
    Route::get('ho-tro', [SupportChatController::class, 'index'])->name('support.index');
    Route::post('ho-tro/{session}/reply', [SupportChatController::class, 'reply'])->name('support.reply');
    Route::post('ho-tro/{session}/status', [SupportChatController::class, 'updateStatus'])->name('support.update-status');

    // ==========================================
    // DÀNH RIÊNG CHO QUẢN TRỊ VIÊN CẤP CAO (ADMIN)
    // ==========================================
    Route::middleware('role:admin')->group(function () {
        Route::resource('cua-hang', StoreController::class)->except(['create', 'show', 'edit'])->names('stores');
        Route::resource('nhan-su', UserController::class)->parameters(['nhan-su' => 'user'])->except(['create', 'show', 'edit'])->names('users');
        Route::post('nhan-su/{user}/khoa-tai-khoan', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});
