<?php

use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\MaGiamGia;
use App\Models\SanPham;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('chi_tiet_don_hang can associate with a lo_hang for product batch traceability', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Schema Test Store',
        'dia_chi' => '100 Lê Lợi, Q.1',
        'so_dien_thoai' => '0901234567',
        'gio_mo_cua' => '07:00:00',
        'gio_dong_cua' => '21:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Rau Củ Tươi',
        'duong_dan_seo' => 'rau-cu-tuoi-'.uniqid(),
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => 'TRACE123456',
        'ten_san_pham' => 'Rau Cải Xanh Đà Lạt',
        'duong_dan_seo' => 'rau-cai-xanh-da-lat-'.uniqid(),
        'don_vi_tinh' => 'kg',
        'gia_ban' => 35000,
        'gia_giam' => 30000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $loHang = LoHang::create([
        'ma_lo' => 'LH-TEST-TRACE-'.uniqid(),
        'san_pham_id' => $product->id,
        'cua_hang_id' => $store->id,
        'ngay_san_xuat' => Carbon::now()->subDays(1)->toDateString(),
        'ngay_het_han' => Carbon::now()->addDays(5)->toDateString(),
        'so_luong_nhap' => 50,
        'so_luong_con_lai' => 50,
        'gia_nhap' => 20000,
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create([
        'role' => 'khach_hang',
    ]);

    $donHang = DonHang::create([
        'ma_don_hang' => 'DH-TRACE-'.uniqid(),
        'user_id' => $user->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 60000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 75000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Người Nhận Test',
        'so_dien_thoai_nhan' => '0901234567',
        'dia_chi_giao' => '123 Đường Test, Q.1',
    ]);

    $chiTiet = ChiTietDonHang::create([
        'don_hang_id' => $donHang->id,
        'san_pham_id' => $product->id,
        'lo_hang_id' => $loHang->id,
        'ten_san_pham' => $product->ten_san_pham,
        'don_vi_tinh' => 'kg',
        'don_gia' => 30000,
        'gia_von' => 20000,
        'so_luong' => 2,
        'thanh_tien' => 60000,
    ]);

    expect($chiTiet->lo_hang_id)->toBe($loHang->id);
    expect($chiTiet->loHang)->not->toBeNull();
    expect($chiTiet->loHang->id)->toBe($loHang->id);
    expect($loHang->chiTietDonHangs)->toHaveCount(1);
    expect($loHang->chiTietDonHangs->first()->id)->toBe($chiTiet->id);
});

test('ma_giam_gia lookup queries function accurately with valid date range', function () {
    $now = Carbon::now();

    $coupon = MaGiamGia::create([
        'ma_khuyen_mai' => 'VALIDTEST'.rand(100, 999),
        'ten_chuong_trinh' => 'Test Valid Coupon',
        'loai_giam_gia' => 'tien_mat',
        'gia_tri_giam' => 20000,
        'don_hang_toi_thieu' => 100000,
        'ngay_bat_dau' => $now->copy()->subDay(),
        'ngay_ket_thuc' => $now->copy()->addDays(10),
        'so_luong' => 100,
        'da_su_dung' => 10,
        'trang_thai' => 1,
    ]);

    $found = MaGiamGia::where('ma_khuyen_mai', $coupon->ma_khuyen_mai)
        ->where('trang_thai', 1)
        ->where('ngay_bat_dau', '<=', $now)
        ->where('ngay_ket_thuc', '>=', $now)
        ->first();

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($coupon->id);
});
