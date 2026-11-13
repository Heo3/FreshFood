<?php

use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store1 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store 1',
        'dia_chi' => '123 Nguyen Trai, Q1',
        'so_dien_thoai' => '0901234567',
        'trang_thai' => 1,
    ]);

    $this->store2 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store 2',
        'dia_chi' => '456 Le Van Viet, Q9',
        'so_dien_thoai' => '0907654321',
        'trang_thai' => 1,
    ]);

    $this->manager = User::factory()->create([
        'role' => 'quan_ly',
        'store_id' => $this->store1->id,
        'status' => 1,
    ]);

    $this->truckDriver = User::factory()->create([
        'name' => 'Tài Xế Xe Tải',
        'email' => 'xetai@freshfood.vn',
        'role' => 'giao_hang',
        'store_id' => $this->store1->id,
        'loai_phuong_tien' => 'xe_tai',
        'bien_so_xe' => '51D-999.88',
        'tai_trong_kg' => 1500,
        'trang_thai_giao' => 'san_sang',
        'status' => 1,
    ]);

    $this->bikeShipper = User::factory()->create([
        'name' => 'Shipper Xe Máy',
        'email' => 'xemay@freshfood.vn',
        'role' => 'giao_hang',
        'store_id' => $this->store1->id,
        'loai_phuong_tien' => 'xe_may',
        'bien_so_xe' => '59-S2 123.45',
        'tai_trong_kg' => 30,
        'trang_thai_giao' => 'san_sang',
        'status' => 1,
    ]);

    $this->customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $this->category = DanhMuc::create([
        'ten_danh_muc' => 'Rau củ hữu cơ',
        'duong_dan_seo' => 'rau-cu-huu-co',
        'trang_thai' => 1,
    ]);

    $this->product = SanPham::create([
        'danh_muc_id' => $this->category->id,
        'ma_va_vach' => '893600000888',
        'ten_san_pham' => 'Cà Chua Đà Lạt',
        'duong_dan_seo' => 'ca-chua-da-lat',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 35000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);
});

test('store manager can view delivery fleet index', function () {
    $response = $this->actingAs($this->manager)->get(route('store.delivery.fleet'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/delivery/Fleet')
        ->has('drivers', 2)
    );
});

test('manager can assign standard order to motorbike shipper', function () {
    $order = DonHang::create([
        'ma_don_hang' => 'FFTEST001',
        'user_id' => $this->customer->id,
        'cua_hang_id' => $this->store1->id,
        'tong_tien_hang' => 70000,
        'phi_giao_hang' => 15000,
        'tong_thanhtoan' => 85000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_giao_hang',
        'ten_nguoi_nhan' => 'Nguyễn Văn A',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '123 Lê Lợi, Q1',
    ]);

    $response = $this->actingAs($this->manager)->post(route('store.delivery.assign', $order->id), [
        'nguoi_giao_hang_id' => $this->bikeShipper->id,
        'loai_phuong_tien_giao' => 'xe_may',
        'bien_so_xe_giao' => '59-S2 123.45',
        'ghi_chu_giao_hang' => 'Giao trước 18h',
    ]);

    $response->assertSessionHas('success');

    $order->refresh();
    $this->assertEquals('dang_giao', $order->trang_thai_don_hang);
    $this->assertEquals($this->bikeShipper->id, $order->nguoi_giao_hang_id);
    $this->assertEquals('xe_may', $order->loai_phuong_tien_giao);

    $this->bikeShipper->refresh();
    $this->assertEquals('dang_giao', $this->bikeShipper->trang_thai_giao);
});

test('manager can assign bulk order to truck driver', function () {
    $order = DonHang::create([
        'ma_don_hang' => 'FFBULK001',
        'user_id' => $this->customer->id,
        'cua_hang_id' => $this->store1->id,
        'tong_tien_hang' => 3500000,
        'phi_giao_hang' => 0,
        'tong_thanhtoan' => 3500000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_giao_hang',
        'ten_nguoi_nhan' => 'Công Ty Khách Hàng Lớn',
        'so_dien_thoai_nhan' => '0988776655',
        'dia_chi_giao' => 'Khu Công Nghệ Cao, TP Thủ Đức',
    ]);

    $this->assertTrue($order->khuyenNghiXeTai());

    $response = $this->actingAs($this->manager)->post(route('store.delivery.assign', $order->id), [
        'nguoi_giao_hang_id' => $this->truckDriver->id,
        'loai_phuong_tien_giao' => 'xe_tai',
        'bien_so_xe_giao' => '51D-999.88',
        'ghi_chu_giao_hang' => 'Đơn hàng lớn - Chở bằng xe tải giao tận cửa kho',
    ]);

    $response->assertSessionHas('success');

    $order->refresh();
    $this->assertEquals('dang_giao', $order->trang_thai_don_hang);
    $this->assertEquals($this->truckDriver->id, $order->nguoi_giao_hang_id);
    $this->assertEquals('xe_tai', $order->loai_phuong_tien_giao);
});

test('shipper can view assigned deliveries in mobile portal', function () {
    $order = DonHang::create([
        'ma_don_hang' => 'FFDELIV001',
        'user_id' => $this->customer->id,
        'cua_hang_id' => $this->store1->id,
        'nguoi_giao_hang_id' => $this->bikeShipper->id,
        'loai_phuong_tien_giao' => 'xe_may',
        'tong_tien_hang' => 100000,
        'phi_giao_hang' => 15000,
        'tong_thanhtoan' => 115000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'dang_giao',
        'ten_nguoi_nhan' => 'Nguyễn Văn B',
        'so_dien_thoai_nhan' => '0909090909',
        'dia_chi_giao' => '789 Trần Hưng Đạo',
    ]);

    $response = $this->actingAs($this->bikeShipper)->get(route('delivery.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/delivery/MyDeliveries')
        ->has('activeDeliveries', 1)
    );
});

test('shipper can complete delivery and COD payment is marked as paid', function () {
    $order = DonHang::create([
        'ma_don_hang' => 'FFDELIV002',
        'user_id' => $this->customer->id,
        'cua_hang_id' => $this->store1->id,
        'nguoi_giao_hang_id' => $this->bikeShipper->id,
        'loai_phuong_tien_giao' => 'xe_may',
        'tong_tien_hang' => 100000,
        'phi_giao_hang' => 15000,
        'tong_thanhtoan' => 115000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'dang_giao',
        'ten_nguoi_nhan' => 'Nguyễn Văn C',
        'so_dien_thoai_nhan' => '0901112233',
        'dia_chi_giao' => '45 Hai Bà Trưng',
    ]);

    $this->bikeShipper->update(['trang_thai_giao' => 'dang_giao']);

    $response = $this->actingAs($this->bikeShipper)->post(route('delivery.complete', $order->id), [
        'ghi_chu' => 'Đã giao thành công cho khách, nhận đủ tiền mặt',
    ]);

    $response->assertSessionHas('success');

    $order->refresh();
    $this->assertEquals('da_giao', $order->trang_thai_don_hang);
    $this->assertEquals('da_thanh_toan', $order->trang_thai_thanh_toan);
    $this->assertNotNull($order->thoi_gian_giao_thanh_cong);

    $this->bikeShipper->refresh();
    $this->assertEquals('san_sang', $this->bikeShipper->trang_thai_giao);
});

test('inter-store bulk transfer rejects motorbike if quantity is 20 or higher', function () {
    $response = $this->actingAs($this->manager)->post(route('store.transfer.store'), [
        'cua_hang_xuat_id' => $this->store1->id,
        'cua_hang_nhan_id' => $this->store2->id,
        'items' => [
            [
                'san_pham_id' => $this->product->id,
                'so_luong' => 25,
            ],
        ],
        'loai_phuong_tien' => 'xe_may',
        'nguoi_giao_hang_id' => $this->bikeShipper->id,
    ]);

    $response->assertSessionHasErrors(['error']);
});
