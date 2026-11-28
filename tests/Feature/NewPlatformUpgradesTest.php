<?php

use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can export orders to csv stream', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store 1',
        'so_dien_thoai' => '0901234567',
        'dia_chi' => '123 Nguyen Hue',
        'trang_thai' => 1,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 1,
        'store_id' => $store->id,
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'DHEXP001',
        'user_id' => $admin->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 100000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 115000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Nguyen Van A',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '456 Le Loi, Q1',
        'khung_gio_giao' => 'Giao hỏa tốc 2h',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.orders.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('danh-sach-don-hang-');
});

test('admin can export revenue report to csv stream', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store 1',
        'so_dien_thoai' => '0901234567',
        'dia_chi' => '123 Nguyen Hue',
        'trang_thai' => 1,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 1,
        'store_id' => $store->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.reports.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('bao-cao-doanh-thu-');
});

test('customer can reorder previous order items into cart', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store 1',
        'so_dien_thoai' => '0901234567',
        'dia_chi' => '123 Nguyen Hue',
        'trang_thai' => 1,
    ]);

    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Rau Củ Quả',
        'duong_dan_seo' => 'rau-cu-qua',
    ]);

    $product = SanPham::create([
        'ten_san_pham' => 'Rau Cải Xanh Hữu Cơ',
        'ma_va_vach' => 'RCX001',
        'duong_dan_seo' => 'rau-cai-xanh-huu-co',
        'danh_muc_id' => $category->id,
        'gia_ban' => 25000,
        'don_vi_tinh' => 'kg',
        'trang_thai' => 1,
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'DHREORDER01',
        'user_id' => $customer->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 50000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 65000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'da_thanh_toan',
        'trang_thai_don_hang' => 'da_giao',
        'ten_nguoi_nhan' => 'Nguyen Van A',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '456 Le Loi, Q1',
    ]);

    ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $product->id,
        'ten_san_pham' => $product->ten_san_pham,
        'don_vi_tinh' => 'kg',
        'don_gia' => 25000,
        'so_luong' => 2,
        'thanh_tien' => 50000,
    ]);

    $response = $this->actingAs($customer)->post(route('account.orders.reorder', $order->id));

    $response->assertRedirect(route('cart.index'));
    $this->assertDatabaseHas('gio_hang', [
        'user_id' => $customer->id,
        'san_pham_id' => $product->id,
        'so_luong' => 2,
    ]);
});
