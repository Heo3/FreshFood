<?php

use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\MaGiamGia;
use App\Models\SanPham;
use App\Models\User;

test('guest is redirected to login when accessing protected areas', function () {
    $this->get('/admin')->assertRedirect(route('login'));
    $this->get('/store/pos')->assertRedirect(route('login'));
    $this->get('/tai-khoan')->assertRedirect(route('login'));
});

test('customer can access customer portal but cannot access admin or store portal', function () {
    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    // Được phép vào phân hệ khách hàng
    $this->actingAs($customer)->get('/tai-khoan')->assertSuccessful();

    // Bị cấm vào phân hệ Admin và phân hệ Kho/POS
    $this->actingAs($customer)->get('/admin')->assertForbidden();
    $this->actingAs($customer)->get('/store/pos')->assertForbidden();
});

test('staff can access store operations and pos but cannot access admin panel', function () {
    $staff = User::factory()->create([
        'role' => 'nhan_vien',
        'status' => 1,
    ]);

    // Được phép vào phân hệ vận hành chi nhánh
    $this->actingAs($staff)->get('/store/pos')->assertSuccessful();
    $this->actingAs($staff)->get('/store/ton-kho')->assertSuccessful();

    // Bị cấm vào phân hệ Admin
    $this->actingAs($staff)->get('/admin')->assertForbidden();
});

test('store manager can access store operations and admin reports but not root admin settings', function () {
    $manager = User::factory()->create([
        'role' => 'quan_ly',
        'status' => 1,
    ]);

    // Quản lý được vào dashboard admin và phân hệ chi nhánh
    $this->actingAs($manager)->get('/admin')->assertSuccessful();
    $this->actingAs($manager)->get('/admin/bao-cao')->assertSuccessful();
    $this->actingAs($manager)->get('/store/pos')->assertSuccessful();

    // Nhưng không được vào phần cấu hình chuỗi cửa hàng của Admin tối cao
    $this->actingAs($manager)->get('/admin/cua-hang')->assertForbidden();
    $this->actingAs($manager)->get('/admin/nhan-su')->assertForbidden();
});

test('admin can access all areas including system configurations', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 1,
    ]);

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/cua-hang')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/nhan-su')->assertSuccessful();
    $this->actingAs($admin)->get('/store/pos')->assertSuccessful();
});

test('inactive user is logged out and redirected to login', function () {
    $lockedUser = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 0,
    ]);

    $response = $this->actingAs($lockedUser)->get('/tai-khoan');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('store portal root redirects to pos', function () {
    $staff = User::factory()->create(['role' => 'nhan_vien', 'status' => 1]);
    $this->actingAs($staff)->get('/store')->assertRedirect(route('store.pos.index'));
});

test('store manager cannot delete master catalog items like products categories or coupons', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood RBAC Branch 1',
        'dia_chi' => '123 Đường A',
        'so_dien_thoai' => '0901234567',
        'trang_thai' => 1,
    ]);

    $manager = User::factory()->create([
        'role' => 'quan_ly',
        'store_id' => $store->id,
        'status' => 1,
    ]);

    $cat = DanhMuc::create([
        'ten_danh_muc' => 'Danh Mục RBAC Test',
        'duong_dan_seo' => 'danh-muc-rbac-test',
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $cat->id,
        'ten_san_pham' => 'Sản Phẩm RBAC Test',
        'duong_dan_seo' => 'san-pham-rbac-test',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 50000,
        'trang_thai' => 1,
    ]);

    $coupon = MaGiamGia::create([
        'ma_khuyen_mai' => 'COUPONRBAC',
        'ten_chuong_trinh' => 'Coupon RBAC',
        'loai_giam_gia' => 'phan_tram',
        'gia_tri_giam' => 10,
        'ngay_bat_dau' => now()->toDateString(),
        'ngay_ket_thuc' => now()->addDays(5)->toDateString(),
        'so_luong' => 10,
        'trang_thai' => 1,
    ]);

    // Quản lý không có quyền xóa sản phẩm
    $this->actingAs($manager)->delete(route('admin.products.destroy', $product->id))->assertForbidden();

    // Quản lý không có quyền xóa danh mục
    $this->actingAs($manager)->delete(route('admin.categories.destroy', $cat->id))->assertForbidden();

    // Quản lý không có quyền xóa coupon
    $this->actingAs($manager)->delete(route('admin.coupons.destroy', $coupon->id))->assertForbidden();
});

test('store manager cannot update price for other branches', function () {
    $store1 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store 1',
        'dia_chi' => '123 Đường 1',
        'so_dien_thoai' => '0901234561',
        'trang_thai' => 1,
    ]);

    $store2 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store 2',
        'dia_chi' => '123 Đường 2',
        'so_dien_thoai' => '0901234562',
        'trang_thai' => 1,
    ]);

    $manager = User::factory()->create([
        'role' => 'quan_ly',
        'store_id' => $store1->id,
        'status' => 1,
    ]);

    $cat = DanhMuc::create([
        'ten_danh_muc' => 'Thực Phẩm Giá Test',
        'duong_dan_seo' => 'thuc-pham-gia-test',
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $cat->id,
        'ten_san_pham' => 'Rau Giá Test',
        'duong_dan_seo' => 'rau-gia-test',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 30000,
        'trang_thai' => 1,
    ]);

    // Cập nhật giá cho chi nhánh của chính mình -> OK
    $this->actingAs($manager)->post(route('admin.products.branch-price', $product->id), [
        'cua_hang_id' => $store1->id,
        'gia_ban' => 32000,
    ])->assertRedirect();

    // Cố tình cập nhật giá cho chi nhánh khác -> Bị cấm (403)
    $this->actingAs($manager)->post(route('admin.products.branch-price', $product->id), [
        'cua_hang_id' => $store2->id,
        'gia_ban' => 40000,
    ])->assertForbidden();
});

test('store manager cannot view or modify orders of another store', function () {
    $store1 = CuaHang::create([
        'ten_cua_hang' => 'Store A',
        'dia_chi' => 'Store A Addr',
        'so_dien_thoai' => '0909000001',
        'trang_thai' => 1,
    ]);

    $store2 = CuaHang::create([
        'ten_cua_hang' => 'Store B',
        'dia_chi' => 'Store B Addr',
        'so_dien_thoai' => '0909000002',
        'trang_thai' => 1,
    ]);

    $manager = User::factory()->create([
        'role' => 'quan_ly',
        'store_id' => $store1->id,
        'status' => 1,
    ]);

    $order1 = DonHang::create([
        'ma_don_hang' => 'DH-STORE-A',
        'user_id' => $manager->id,
        'cua_hang_id' => $store1->id,
        'tong_tien_hang' => 100000,
        'phi_giao_hang' => 0,
        'tong_thanhtoan' => 100000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Khách A',
        'so_dien_thoai_nhan' => '0909000001',
        'dia_chi_giao' => 'Địa chỉ A',
    ]);

    $order2 = DonHang::create([
        'ma_don_hang' => 'DH-STORE-B',
        'user_id' => $manager->id,
        'cua_hang_id' => $store2->id,
        'tong_tien_hang' => 100000,
        'phi_giao_hang' => 0,
        'tong_thanhtoan' => 100000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Khách B',
        'so_dien_thoai_nhan' => '0909000002',
        'dia_chi_giao' => 'Địa chỉ B',
    ]);

    // Xem đơn chi nhánh của mình -> OK
    $this->actingAs($manager)->get(route('admin.orders.show', $order1->id))->assertSuccessful();

    // Cập nhật đơn chi nhánh của mình -> OK
    $this->actingAs($manager)->post(route('admin.orders.update-status', $order1->id), [
        'trang_thai_don_hang' => 'dang_chuan_bi',
    ])->assertRedirect();

    // Xem đơn chi nhánh khác -> 403 Forbidden
    $this->actingAs($manager)->get(route('admin.orders.show', $order2->id))->assertForbidden();

    // Cập nhật đơn chi nhánh khác -> 403 Forbidden
    $this->actingAs($manager)->post(route('admin.orders.update-status', $order2->id), [
        'trang_thai_don_hang' => 'dang_chuan_bi',
    ])->assertForbidden();
});

test('staff cannot discard batch lots without manager authority', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Store Discard Test',
        'dia_chi' => 'Store Addr',
        'so_dien_thoai' => '0909000099',
        'trang_thai' => 1,
    ]);

    $staff = User::factory()->create([
        'role' => 'nhan_vien',
        'store_id' => $store->id,
        'status' => 1,
    ]);

    $cat = DanhMuc::create([
        'ten_danh_muc' => 'Rau Discard',
        'duong_dan_seo' => 'rau-discard',
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $cat->id,
        'ten_san_pham' => 'Rau Muống Hư',
        'duong_dan_seo' => 'rau-muong-hu',
        'don_vi_tinh' => 'bó',
        'gia_ban' => 10000,
        'trang_thai' => 1,
    ]);

    $lot = LoHang::create([
        'ma_lo' => 'LO-DISCARD-TEST',
        'cua_hang_id' => $store->id,
        'san_pham_id' => $product->id,
        'ngay_san_xuat' => now()->subDays(5)->toDateString(),
        'ngay_het_han' => now()->subDay()->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 5,
        'gia_nhap' => 6000,
        'trang_thai' => 1,
    ]);

    // Nhân viên thử xuất hủy -> Bị cấm (403)
    $this->actingAs($staff)->post(route('store.batches.discard'), [
        'lo_hang_id' => $lot->id,
        'so_luong' => 5,
        'ly_do' => 'Hư dập nát',
    ])->assertForbidden();
});
