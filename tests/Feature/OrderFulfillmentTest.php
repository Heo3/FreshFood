<?php

use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use App\Models\TonKho;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setupOrderFulfillmentPrerequisites(): array
{
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store Test',
        'dia_chi' => '100 Lê Lợi, Q.1',
        'so_dien_thoai' => '0281111222',
        'gio_mo_cua' => '06:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Rau Củ Tươi',
        'duong_dan_seo' => 'rau-cu-tuoi',
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => '893600000777',
        'ten_san_pham' => 'Rau Xà Lách Thuỷ Canh',
        'duong_dan_seo' => 'rau-xa-lach-thuy-canh',
        'don_vi_tinh' => 'gói',
        'gia_ban' => 25000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $lot = LoHang::create([
        'ma_lo' => 'LO-XALACH-001',
        'cua_hang_id' => $store->id,
        'san_pham_id' => $product->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(4)->toDateString(),
        'so_luong_nhap' => 50,
        'so_luong_con_lai' => 50,
        'gia_nhap' => 15000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $store->id,
        'san_pham_id' => $product->id,
        'so_luong_ton' => 50,
        'so_luong_tam_giu' => 0,
        'nguong_canh_bao_thap' => 5,
    ]);

    $customer = User::factory()->create([
        'role' => 'khach_hang',
    ]);

    $staff = User::factory()->create([
        'role' => 'nhan_vien',
        'store_id' => $store->id,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    return compact('store', 'category', 'product', 'lot', 'customer', 'staff', 'admin');
}

test('store outbound index displays pending orders needing packaging and fulfillment', function () {
    $data = setupOrderFulfillmentPrerequisites();

    // Tạo 1 đơn hàng mới cho chi nhánh
    $order = DonHang::create([
        'ma_don_hang' => 'DH-PENDING-01',
        'user_id' => $data['customer']->id,
        'cua_hang_id' => $data['store']->id,
        'tong_tien_hang' => 50000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 65000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Nguyễn Văn A',
        'so_dien_thoai_nhan' => '0901234567',
        'dia_chi_giao' => '123 Cách Mạng Tháng 8, Q.3',
    ]);

    ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $data['product']->id,
        'ten_san_pham' => $data['product']->ten_san_pham,
        'so_luong' => 2,
        'don_gia' => 25000,
        'thanh_tien' => 50000,
    ]);

    // Nhân viên chi nhánh truy cập /store/phieu-xuat
    $response = $this->actingAs($data['staff'])->get(route('store.outbound.index'));
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('store/outbound/Index')
        ->has('pendingOrders', 1)
        ->where('pendingOrders.0.ma_don_hang', 'DH-PENDING-01')
    );
});

test('store staff fulfills pending order to generate phieu_xuat, allocate FEFO lot, and update status', function () {
    $data = setupOrderFulfillmentPrerequisites();

    $order = DonHang::create([
        'ma_don_hang' => 'DH-FULFILL-01',
        'user_id' => $data['customer']->id,
        'cua_hang_id' => $data['store']->id,
        'tong_tien_hang' => 50000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 65000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Trần Thị B',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '456 Hai Bà Trưng, Q.1',
    ]);

    $chiTiet = ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $data['product']->id,
        'ten_san_pham' => $data['product']->ten_san_pham,
        'so_luong' => 2,
        'don_gia' => 25000,
        'thanh_tien' => 50000,
        'lo_hang_id' => null, // chưa gán mã lô
    ]);

    // Nhân viên chi nhánh bấm Soạn hàng & Xuất kho
    $response = $this->actingAs($data['staff'])->post(route('store.outbound.fulfill', $order->id));
    $response->assertRedirect();

    // 1. Kiểm tra đơn hàng được chuyển sang dang_chuan_bi
    $order->refresh();
    expect($order->trang_thai_don_hang)->toBe('dang_chuan_bi');

    // 2. Kiểm tra chi_tiet_don_hang được gán lo_hang_id
    $chiTiet->refresh();
    expect($chiTiet->lo_hang_id)->toBe($data['lot']->id);

    // 3. Kiểm tra phiếu xuất kho được tạo
    $phieuXuat = PhieuXuat::where('don_hang_id', $order->id)->first();
    expect($phieuXuat)->not->toBeNull();
    expect($phieuXuat->loai_xuat)->toBe(PhieuXuat::LOAI_XUAT_BAN);
    expect((float) $phieuXuat->tong_so_luong)->toBe(2.0);

    // 4. Kiểm tra tồn kho lô hàng giảm từ 50 xuống 48
    $data['lot']->refresh();
    expect((float) $data['lot']->so_luong_con_lai)->toBe(48.0);

    // 5. Kiểm tra tổng tồn kho giảm từ 50 xuống 48
    $tonKho = TonKho::where('cua_hang_id', $data['store']->id)->where('san_pham_id', $data['product']->id)->first();
    expect((float) $tonKho->so_luong_ton)->toBe(48.0);
});

test('admin order status update to dang_chuan_bi automatically generates phieu_xuat', function () {
    $data = setupOrderFulfillmentPrerequisites();

    $order = DonHang::create([
        'ma_don_hang' => 'DH-ADMIN-APPROVE-01',
        'user_id' => $data['customer']->id,
        'cua_hang_id' => $data['store']->id,
        'tong_tien_hang' => 25000,
        'phi_giao_hang' => 0,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 25000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Lê Văn C',
        'so_dien_thoai_nhan' => '0933445566',
        'dia_chi_giao' => '789 Võ Văn Kiệt, Q.5',
    ]);

    ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $data['product']->id,
        'ten_san_pham' => $data['product']->ten_san_pham,
        'so_luong' => 1,
        'don_gia' => 25000,
        'thanh_tien' => 25000,
    ]);

    // Admin duyệt đơn sang dang_chuan_bi
    $response = $this->actingAs($data['admin'])->post(route('admin.orders.update-status', $order->id), [
        'trang_thai_don_hang' => 'dang_chuan_bi',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    // Phiếu xuất kho đã được sinh tự động
    $phieuXuat = PhieuXuat::where('don_hang_id', $order->id)->first();
    expect($phieuXuat)->not->toBeNull();
    expect($phieuXuat->loai_xuat)->toBe(PhieuXuat::LOAI_XUAT_BAN);
});
