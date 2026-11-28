<?php

use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\SanPham;
use App\Models\TonKho;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('don hang model correctly defines nhanVienLenDon and nguoiGiaoHang relationships', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Cửa hàng Test',
        'dia_chi' => '123 Test',
        'so_dien_thoai' => '0987654321',
        'gio_mo_cua' => '07:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $staff = User::factory()->create([
        'name' => 'Nhân Viên Bán Hàng',
        'role' => 'nhan_vien',
    ]);

    $driver = User::factory()->create([
        'name' => 'Shipper Giao Hàng',
        'role' => 'giao_hang',
        'loai_phuong_tien' => 'xe_may',
        'bien_so_xe' => '43A-12345',
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'DH-TEST-STAFF',
        'user_id' => $staff->id,
        'nhan_vien_len_don_id' => $staff->id,
        'nguoi_giao_hang_id' => $driver->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 100000,
        'phi_giao_hang' => 0,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 100000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Khách hàng',
        'so_dien_thoai_nhan' => '0901234567',
        'dia_chi_giao' => 'Đà Nẵng',
    ]);

    expect($order->nhanVienLenDon)->not->toBeNull();
    expect($order->nhanVienLenDon->id)->toBe($staff->id);
    expect($order->nhanVienLenDon->name)->toBe('Nhân Viên Bán Hàng');

    expect($order->nguoiGiaoHang)->not->toBeNull();
    expect($order->nguoiGiaoHang->id)->toBe($driver->id);
    expect($order->nguoiGiaoHang->name)->toBe('Shipper Giao Hàng');
});

test('admin can update order and assign nhan vien len don and nguoi giao hang', function () {
    $admin = User::factory()->create([
        'name' => 'Admin Quản Trị',
        'role' => 'admin',
    ]);

    $staff = User::factory()->create([
        'name' => 'Nhân Viên Xử Lý',
        'role' => 'nhan_vien',
    ]);

    $driver = User::factory()->create([
        'name' => 'Tài Xế Nguyễn Văn B',
        'role' => 'giao_hang',
        'loai_phuong_tien' => 'xe_may',
        'bien_so_xe' => '92B-99999',
    ]);

    $store = CuaHang::create([
        'ten_cua_hang' => 'Chi Nhánh Q1',
        'dia_chi' => '1 Lê Duẩn',
        'so_dien_thoai' => '0900000000',
        'gio_mo_cua' => '07:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'DH-UPDATE-TEST',
        'user_id' => $admin->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 200000,
        'phi_giao_hang' => 0,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 200000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Khách Test',
        'so_dien_thoai_nhan' => '0901111111',
        'dia_chi_giao' => '123 Đường Số 1',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->id), [
        'trang_thai_don_hang' => 'dang_giao',
        'trang_thai_thanh_toan' => 'da_thanh_toan',
        'nhan_vien_len_don_id' => $staff->id,
        'nguoi_giao_hang_id' => $driver->id,
        'ghi_chu' => 'Đã giao cho tài xế B đi giao ngay',
    ]);

    $response->assertRedirect();

    $order->refresh();
    expect($order->nhan_vien_len_don_id)->toBe($staff->id);
    expect($order->nguoi_giao_hang_id)->toBe($driver->id);
    expect($order->loai_phuong_tien_giao)->toBe('xe_may');
    expect($order->bien_so_xe_giao)->toBe('92B-99999');
    expect($order->trang_thai_don_hang)->toBe('dang_giao');
    expect($order->thoi_gian_bat_dau_giao)->not->toBeNull();
});

test('pos checkout automatically records the staff who processed the order', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Chi nhánh POS',
        'dia_chi' => '456 POS',
        'so_dien_thoai' => '0911222333',
        'gio_mo_cua' => '07:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $staff = User::factory()->create([
        'name' => 'Thu Ngân 01',
        'role' => 'nhan_vien',
        'store_id' => $store->id,
    ]);

    $cat = DanhMuc::create([
        'ten_danh_muc' => 'Thực phẩm khô',
        'duong_dan_seo' => 'thuc-pham-kho-pos',
    ]);

    $prod = SanPham::create([
        'danh_muc_id' => $cat->id,
        'ma_va_vach' => '893000111222',
        'ten_san_pham' => 'Gạo Thơm ST25',
        'duong_dan_seo' => 'gao-thom-st25-pos',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 30000,
        'la_tuoi_song' => false,
        'trang_thai' => 1,
    ]);

    LoHang::create([
        'ma_lo' => 'LO-POS-01',
        'cua_hang_id' => $store->id,
        'san_pham_id' => $prod->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addMonths(6)->toDateString(),
        'so_luong_nhap' => 100,
        'so_luong_con_lai' => 100,
        'gia_nhap' => 20000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $store->id,
        'san_pham_id' => $prod->id,
        'so_luong_ton' => 100,
        'so_luong_tam_giu' => 0,
        'nguong_canh_bao_thap' => 10,
    ]);

    $response = $this->actingAs($staff)->postJson(route('store.pos.checkout'), [
        'store_id' => $store->id,
        'items' => [
            [
                'san_pham_id' => $prod->id,
                'so_luong' => 2,
                'don_gia' => 30000,
            ],
        ],
        'phuong_thuc_thanh_toan' => 'tien_mat',
        'customer_name' => 'Khách Vãng Lai',
        'customer_phone' => '0909123987',
    ]);

    $response->assertSuccessful();

    $order = DonHang::latest('id')->first();
    expect($order)->not->toBeNull();
    expect($order->nhan_vien_len_don_id)->toBe($staff->id);
    expect($order->nhanVienLenDon->name)->toBe('Thu Ngân 01');
});

test('customer can view driver name and phone on order detail and tracking', function () {
    $customer = User::factory()->create([
        'name' => 'Khách Hàng Online',
        'role' => 'khach_hang',
    ]);

    $driver = User::factory()->create([
        'name' => 'Tài Xế Giao Nhanh',
        'phone' => '0933888999',
        'role' => 'giao_hang',
        'loai_phuong_tien' => 'xe_may',
        'bien_so_xe' => '43-C1 123.45',
    ]);

    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Hải Châu',
        'dia_chi' => '12 Bạch Đằng',
        'so_dien_thoai' => '0236123456',
        'gio_mo_cua' => '07:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'FF-ONLINE-999',
        'user_id' => $customer->id,
        'nguoi_giao_hang_id' => $driver->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 150000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 165000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'dang_giao',
        'ten_nguoi_nhan' => 'Khách Hàng Online',
        'so_dien_thoai_nhan' => '0905123456',
        'dia_chi_giao' => '123 Hùng Vương',
        'loai_phuong_tien_giao' => 'xe_may',
        'bien_so_xe_giao' => '43-C1 123.45',
    ]);

    // 1. Kiểm tra màn hình chi tiết đơn hàng của tài khoản khách
    $responseDetail = $this->actingAs($customer)->get(route('account.orders.show', ['maDonHang' => $order->ma_don_hang]));
    $responseDetail->assertSuccessful();
    $responseDetail->assertInertia(fn ($page) => $page
        ->component('client/account/OrderDetail')
        ->has('order.nguoi_giao_hang')
        ->where('order.nguoi_giao_hang.name', 'Tài Xế Giao Nhanh')
        ->where('order.nguoi_giao_hang.phone', '0933888999')
    );

    // 2. Kiểm tra màn hình tra cứu công khai
    $responseTracking = $this->get(route('orders.tracking', ['code' => 'FF-ONLINE-999']));
    $responseTracking->assertSuccessful();
    $responseTracking->assertInertia(fn ($page) => $page
        ->component('client/OrderTracking')
        ->has('order.nguoi_giao_hang')
        ->where('order.nguoi_giao_hang.name', 'Tài Xế Giao Nhanh')
        ->where('order.nguoi_giao_hang.phone', '0933888999')
    );
});
