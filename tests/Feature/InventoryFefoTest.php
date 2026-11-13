<?php

use App\Exceptions\InsufficientInventoryException;
use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use App\Models\TheKho;
use App\Models\TonKho;
use App\Models\User;
use App\Services\InventoryService;

function setupInventoryPrerequisites(): array
{
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Q1 Kho Test',
        'dia_chi' => '120 Hai Bà Trưng, Q.1',
        'so_dien_thoai' => '0289999888',
        'gio_mo_cua' => '06:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Thịt Sạch Hữu Cơ',
        'duong_dan_seo' => 'thit-sach-huu-co',
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => '893600000888',
        'ten_san_pham' => 'Thịt Bò Tươi VietGAP',
        'duong_dan_seo' => 'thit-bo-tuoi-vietgap',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 280000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create([
        'role' => 'nhan_vien',
        'store_id' => $store->id,
    ]);

    return compact('store', 'category', 'product', 'user');
}

test('inventory service kiemTraKhaDung accurately checks FEFO lots and excludes expired stock', function () {
    $data = setupInventoryPrerequisites();
    $service = new InventoryService;

    // Lô 1: Đã hết hạn hôm qua -> 5kg
    LoHang::create([
        'ma_lo' => 'LO-EXPIRED',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->subDays(5)->toDateString(),
        'ngay_het_han' => now()->subDay()->toDateString(),
        'so_luong_nhap' => 5,
        'so_luong_con_lai' => 5,
        'gia_nhap' => 200000,
        'trang_thai' => 1,
    ]);

    // Lô 2: Còn hạn 2 ngày tới -> 4kg
    LoHang::create([
        'ma_lo' => 'LO-NEAR-EXP',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->subDays(2)->toDateString(),
        'ngay_het_han' => now()->addDays(2)->toDateString(),
        'so_luong_nhap' => 4,
        'so_luong_con_lai' => 4,
        'gia_nhap' => 210000,
        'trang_thai' => 1,
    ]);

    // Lô 3: Còn hạn 10 ngày tới -> 10kg
    LoHang::create([
        'ma_lo' => 'LO-SAFE',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(10)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 210000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 19,
    ]);

    // 1. Cần 12kg (trong khi hàng còn hạn có 14kg: Lô 2 + Lô 3) -> Đủ hàng
    $check1 = $service->kiemTraKhaDung($data['store']->id, $data['product']->id, 12);
    expect($check1['kha_dung'])->toBeTrue();
    expect($check1['tong_con_han'])->toBe(14.0);
    expect($check1['thieu'])->toBe(0.0);
    expect($check1['lo_hangs']->first()->ma_lo)->toBe('LO-NEAR-EXP');

    // 2. Cần 16kg (hàng còn hạn chỉ có 14kg, lô hết hạn không được tính) -> Thiếu 2kg
    $check2 = $service->kiemTraKhaDung($data['store']->id, $data['product']->id, 16);
    expect($check2['kha_dung'])->toBeFalse();
    expect($check2['thieu'])->toBe(2.0);
});

test('inventory service xuatKhoDonHang deducts lots in exact FEFO order and logs card records', function () {
    $data = setupInventoryPrerequisites();
    $service = new InventoryService;

    // Lô 1: Hết hạn trước (3 ngày tới), còn 3kg
    $lot1 = LoHang::create([
        'ma_lo' => 'LO-001-FEFO',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->subDay()->toDateString(),
        'ngay_het_han' => now()->addDays(3)->toDateString(),
        'so_luong_nhap' => 5,
        'so_luong_con_lai' => 3,
        'gia_nhap' => 200000,
        'trang_thai' => 1,
    ]);

    // Lô 2: Hết hạn sau (7 ngày tới), còn 10kg
    $lot2 = LoHang::create([
        'ma_lo' => 'LO-002-FEFO',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(7)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 205000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 13,
    ]);

    // Tạo Đơn Hàng cần xuất 5kg thịt bò
    $order = DonHang::create([
        'ma_don_hang' => 'DH-TEST-FEFO-01',
        'user_id' => $data['user']->id,
        'cua_hang_id' => $data['store']->id,
        'tong_tien_hang' => 1400000,
        'phi_giao_hang' => 0,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 1400000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Khách Hàng Test FEFO',
        'so_dien_thoai_nhan' => '0901234567',
        'dia_chi_giao' => '120 Hai Bà Trưng, Q.1',
    ]);

    ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $data['product']->id,
        'ten_san_pham' => $data['product']->ten_san_pham,
        'so_luong' => 5,
        'don_gia' => 280000,
        'thanh_tien' => 1400000,
    ]);

    // Thực hiện xuất kho FEFO cho đơn hàng
    $phieuXuat = $service->xuatKhoDonHang($order, $data['user']->id);

    // Kiểm tra phiếu xuất
    expect($phieuXuat)->toBeInstanceOf(PhieuXuat::class);
    expect((float) $phieuXuat->tong_so_luong)->toBe(5.0);
    expect($phieuXuat->loai_xuat)->toBe(PhieuXuat::LOAI_XUAT_BAN);
    expect($phieuXuat->trang_thai)->toBe(PhieuXuat::TRANG_THAI_DA_XUAT);

    // Kiểm tra nguyên tắc FEFO:
    // Lô 1 (hết hạn trước, có 3kg) phải bị trừ sạch -> còn 0kg
    $lot1->refresh();
    expect((float) $lot1->so_luong_con_lai)->toBe(0.0);

    // Lô 2 (hết hạn sau, có 10kg) bị trừ 2kg còn lại -> còn 8kg
    $lot2->refresh();
    expect((float) $lot2->so_luong_con_lai)->toBe(8.0);

    // Kiểm tra bảng TonKho tổng thể của chi nhánh: 13kg - 5kg = 8kg
    $tonKho = TonKho::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->first();
    expect((float) $tonKho->so_luong_ton)->toBe(8.0);

    // Kiểm tra sổ TheKho
    $theKho = TheKho::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->latest('id')
        ->first();
    expect($theKho)->not->toBeNull();
    expect($theKho->loai_thao_tac)->toBe('xuat_ban');
    expect((float) $theKho->so_luong_thay_doi)->toBe(-5.0);
    expect((float) $theKho->ton_sau_thay_doi)->toBe(8.0);
});

test('inventory service xuatKhoDonHang throws InsufficientInventoryException when out of stock', function () {
    $data = setupInventoryPrerequisites();
    $service = new InventoryService;

    // Chỉ có 2kg trong kho
    LoHang::create([
        'ma_lo' => 'LO-LOW-STOCK',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(5)->toDateString(),
        'so_luong_nhap' => 2,
        'so_luong_con_lai' => 2,
        'gia_nhap' => 200000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 2,
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'DH-OVER-STOCK',
        'user_id' => $data['user']->id,
        'cua_hang_id' => $data['store']->id,
        'tong_tien_hang' => 2800000,
        'phi_giao_hang' => 0,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 2800000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Khách Hàng',
        'so_dien_thoai_nhan' => '0901234567',
        'dia_chi_giao' => '120 Hai Bà Trưng, Q.1',
    ]);

    ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $data['product']->id,
        'ten_san_pham' => $data['product']->ten_san_pham,
        'so_luong' => 10, // Cần 10kg nhưng kho chỉ có 2kg
        'don_gia' => 280000,
        'thanh_tien' => 2800000,
    ]);

    expect(fn () => $service->xuatKhoDonHang($order))
        ->toThrow(InsufficientInventoryException::class);
});

test('inventory service xuatHuyHang properly discards damaged lots and updates stock', function () {
    $data = setupInventoryPrerequisites();
    $service = new InventoryService;

    $lot = LoHang::create([
        'ma_lo' => 'LO-DAMAGED',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->subDays(5)->toDateString(),
        'ngay_het_han' => now()->subDay()->toDateString(), // Hết hạn
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 6,
        'gia_nhap' => 150000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 6,
    ]);

    $phieuHuy = $service->xuatHuyHang(
        cuaHangId: $data['store']->id,
        loHangId: $lot->id,
        soLuong: 4,
        nguoiThucHienId: $data['user']->id,
        lyDo: 'Hàng dập nát và quá date'
    );

    expect($phieuHuy->loai_xuat)->toBe(PhieuXuat::LOAI_XUAT_HUY);
    expect((float) $phieuHuy->tong_so_luong)->toBe(4.0);

    $lot->refresh();
    expect((float) $lot->so_luong_con_lai)->toBe(2.0);

    $tonKho = TonKho::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->first();
    expect((float) $tonKho->so_luong_ton)->toBe(2.0);

    $theKho = TheKho::where('cua_hang_id', $data['store']->id)
        ->where('lo_hang_id', $lot->id)
        ->latest('id')
        ->first();
    expect($theKho->loai_thao_tac)->toBe('xuat_huy');
    expect((float) $theKho->so_luong_thay_doi)->toBe(-4.0);
});

test('inventory service layDanhSachCanhBaoCanDate returns lots near expiry', function () {
    $data = setupInventoryPrerequisites();
    $service = new InventoryService;

    // Lô cận date 1 ngày tới
    LoHang::create([
        'ma_lo' => 'LO-NEAR-1',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->subDays(4)->toDateString(),
        'ngay_het_han' => now()->addDay()->toDateString(),
        'so_luong_nhap' => 5,
        'so_luong_con_lai' => 5,
        'gia_nhap' => 180000,
        'trang_thai' => 1,
    ]);

    // Lô an toàn 15 ngày tới
    LoHang::create([
        'ma_lo' => 'LO-SAFE-FAR',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(15)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 180000,
        'trang_thai' => 1,
    ]);

    $nearExpiry = $service->layDanhSachCanhBaoCanDate($data['store']->id, 3);
    expect($nearExpiry->count())->toBe(1);
    expect($nearExpiry->first()->ma_lo)->toBe('LO-NEAR-1');
});

test('inventory service xuatKhoSanPham deducts lot via FEFO for pos retail sale', function () {
    $data = setupInventoryPrerequisites();
    $service = new InventoryService;

    LoHang::create([
        'ma_lo' => 'LO-POS-1',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(5)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 200000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 10,
    ]);

    $result = $service->xuatKhoSanPham(
        cuaHangId: $data['store']->id,
        sanPhamId: $data['product']->id,
        soLuong: 3.5,
        loaiThaoTac: 'xuat_ban',
        maChungTu: 'POS-0001',
        nguoiThucHienId: $data['user']->id,
        ghiChu: 'Bán tại quầy POS'
    );

    expect($result['so_luong_da_xuat'])->toBe(3.5);
    expect($result['ton_sau_xuat'])->toBe(6.5);
    expect(count($result['lo_da_tru']))->toBe(1);
    expect($result['lo_da_tru'][0]['so_luong'])->toBe(3.5);

    $lot = LoHang::where('ma_lo', 'LO-POS-1')->first();
    expect((float) $lot->so_luong_con_lai)->toBe(6.5);
});
