<?php

use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\LoHang;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use App\Models\TheKho;
use App\Models\TonKho;
use App\Models\User;
use App\Services\InventoryService;

function setupTransferPrerequisites(): array
{
    $store1 = CuaHang::create([
        'ten_cua_hang' => 'Cơ sở 1 - FreshFood Q1',
        'dia_chi' => '120 Hai Bà Trưng, Q.1',
        'so_dien_thoai' => '0289999111',
        'gio_mo_cua' => '06:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $store2 = CuaHang::create([
        'ten_cua_hang' => 'Cơ sở 2 - FreshFood Q7',
        'dia_chi' => '456 Nguyễn Thị Thập, Q.7',
        'so_dien_thoai' => '0289999222',
        'gio_mo_cua' => '06:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Rau Củ Quả Hữu Cơ',
        'duong_dan_seo' => 'rau-cu-qua-huu-co',
    ]);

    $caiBoXoi = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => '893600000999',
        'ten_san_pham' => 'Cải Bó Xôi (Rau Chân Vịt) Hữu Cơ Đà Lạt',
        'duong_dan_seo' => 'cai-bo-xoi-rau-chan-vit-huu-co-da-lat',
        'don_vi_tinh' => 'bó',
        'gia_ban' => 35000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $manager = User::factory()->create([
        'role' => 'quan_ly',
        'store_id' => $store1->id,
    ]);

    return compact('store1', 'store2', 'category', 'caiBoXoi', 'admin', 'manager');
}

test('store transfer takes 20 units of Cai Bo Xoi from store 2 to store 1 using FEFO', function () {
    $data = setupTransferPrerequisites();
    $service = new InventoryService;

    // Chi nhánh 2 có 2 lô Cải Bó Xôi:
    // Lô A (HSD gần hơn: 3 ngày nữa): 15 bó
    $lotA = LoHang::create([
        'ma_lo' => 'LO-CBX-CS2-01',
        'cua_hang_id' => $data['store2']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'ngay_san_xuat' => now()->subDay()->toDateString(),
        'ngay_het_han' => now()->addDays(3)->toDateString(),
        'so_luong_nhap' => 15,
        'so_luong_con_lai' => 15,
        'gia_nhap' => 20000,
        'trang_thai' => 1,
    ]);

    // Lô B (HSD xa hơn: 7 ngày nữa): 25 bó
    $lotB = LoHang::create([
        'ma_lo' => 'LO-CBX-CS2-02',
        'cua_hang_id' => $data['store2']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(7)->toDateString(),
        'so_luong_nhap' => 25,
        'so_luong_con_lai' => 25,
        'gia_nhap' => 22000,
        'trang_thai' => 1,
    ]);

    // Tồn kho tổng tại CS2 ban đầu là 40 bó
    TonKho::create([
        'cua_hang_id' => $data['store2']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'so_luong_ton' => 40,
        'so_luong_tam_giu' => 0,
        'nguong_canh_bao_thap' => 5,
    ]);

    // Tồn kho tại CS1 ban đầu là 2 bó
    TonKho::create([
        'cua_hang_id' => $data['store1']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'so_luong_ton' => 2,
        'so_luong_tam_giu' => 0,
        'nguong_canh_bao_thap' => 5,
    ]);

    // Thực hiện điều chuyển: CS1 lấy từ CS2 20 bó Cải Bó Xôi
    $phieuXuat = $service->dieuChuyenKhoChiNhanh(
        cuaHangXuatId: $data['store2']->id,
        cuaHangNhanId: $data['store1']->id,
        items: [
            ['san_pham_id' => $data['caiBoXoi']->id, 'so_luong' => 20],
        ],
        nguoiThucHienId: $data['manager']->id,
        ghiChu: 'Cơ sở 1 lấy từ Cơ sở 2 20 bó Cải Bó Xôi'
    );

    // 1. Kiểm tra phiếu xuất
    expect($phieuXuat)->not->toBeNull();
    expect($phieuXuat->loai_xuat)->toBe(PhieuXuat::LOAI_XUAT_CHUYEN);
    expect($phieuXuat->cua_hang_id)->toBe($data['store2']->id);
    expect($phieuXuat->cua_hang_nhan_id)->toBe($data['store1']->id);
    expect((float) $phieuXuat->tong_so_luong)->toBe(20.0);

    // 2. Kiểm tra FEFO tại Cơ sở 2 (Xuất 15 bó lô A và 5 bó lô B)
    $lotA->refresh();
    $lotB->refresh();
    expect((float) $lotA->so_luong_con_lai)->toBe(0.0);
    expect((float) $lotB->so_luong_con_lai)->toBe(20.0);

    // 3. Tồn kho CS2 giảm từ 40 xuống 20
    $tonKhoCS2 = TonKho::where('cua_hang_id', $data['store2']->id)
        ->where('san_pham_id', $data['caiBoXoi']->id)
        ->first();
    expect((float) $tonKhoCS2->so_luong_ton)->toBe(20.0);

    // 4. Tồn kho CS1 tăng từ 2 lên 22
    $tonKhoCS1 = TonKho::where('cua_hang_id', $data['store1']->id)
        ->where('san_pham_id', $data['caiBoXoi']->id)
        ->first();
    expect((float) $tonKhoCS1->so_luong_ton)->toBe(22.0);

    // 5. Kiểm tra lô hàng tạo mới/đồng bộ tại Cơ sở 1
    $lotsAtCS1 = LoHang::where('cua_hang_id', $data['store1']->id)
        ->where('san_pham_id', $data['caiBoXoi']->id)
        ->get();
    expect($lotsAtCS1->count())->toBe(2);
    expect((float) $lotsAtCS1->sum('so_luong_con_lai'))->toBe(20.0);

    // 6. Kiểm tra Thẻ kho ghi nhận đúng 2 bên (xuat_chuyen và nhap_chuyen)
    $theKhoXuat = TheKho::where('cua_hang_id', $data['store2']->id)
        ->where('san_pham_id', $data['caiBoXoi']->id)
        ->where('loai_thao_tac', 'xuat_chuyen')
        ->get();
    expect($theKhoXuat->count())->toBe(2); // xuất 2 lô
    expect((float) $theKhoXuat->sum('so_luong_thay_doi'))->toBe(-20.0);

    $theKhoNhap = TheKho::where('cua_hang_id', $data['store1']->id)
        ->where('san_pham_id', $data['caiBoXoi']->id)
        ->where('loai_thao_tac', 'nhap_chuyen')
        ->get();
    expect($theKhoNhap->count())->toBe(2); // nhận 2 lô
    expect((float) $theKhoNhap->sum('so_luong_thay_doi'))->toBe(20.0);
});

test('store transfer via HTTP endpoint creates transfer and redirects with success', function () {
    $data = setupTransferPrerequisites();

    // Chi nhánh 2 có sẵn 30 bó
    LoHang::create([
        'ma_lo' => 'LO-CBX-HTTP-01',
        'cua_hang_id' => $data['store2']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(5)->toDateString(),
        'so_luong_nhap' => 30,
        'so_luong_con_lai' => 30,
        'gia_nhap' => 20000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store2']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'so_luong_ton' => 30,
        'so_luong_tam_giu' => 0,
        'nguong_canh_bao_thap' => 5,
    ]);

    $response = $this->actingAs($data['admin'])->post(route('store.transfer.store'), [
        'cua_hang_xuat_id' => $data['store2']->id,
        'cua_hang_nhan_id' => $data['store1']->id,
        'items' => [
            ['san_pham_id' => $data['caiBoXoi']->id, 'so_luong' => 20],
        ],
        'ghi_chu' => 'Chuyển gấp 20 bó Cải Bó Xôi',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $phieuXuat = PhieuXuat::where('loai_xuat', PhieuXuat::LOAI_XUAT_CHUYEN)->latest('id')->first();
    expect($phieuXuat)->not->toBeNull();
    expect($phieuXuat->cua_hang_id)->toBe($data['store2']->id);
    expect($phieuXuat->cua_hang_nhan_id)->toBe($data['store1']->id);
    expect((float) $phieuXuat->tong_so_luong)->toBe(20.0);
});

test('store transfer fails when source store has insufficient inventory', function () {
    $data = setupTransferPrerequisites();

    // Chi nhánh 2 chỉ có 5 bó (không đủ 20 bó)
    LoHang::create([
        'ma_lo' => 'LO-CBX-SHORT',
        'cua_hang_id' => $data['store2']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(5)->toDateString(),
        'so_luong_nhap' => 5,
        'so_luong_con_lai' => 5,
        'gia_nhap' => 20000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store2']->id,
        'san_pham_id' => $data['caiBoXoi']->id,
        'so_luong_ton' => 5,
        'so_luong_tam_giu' => 0,
        'nguong_canh_bao_thap' => 5,
    ]);

    $response = $this->actingAs($data['admin'])->post(route('store.transfer.store'), [
        'cua_hang_xuat_id' => $data['store2']->id,
        'cua_hang_nhan_id' => $data['store1']->id,
        'items' => [
            ['san_pham_id' => $data['caiBoXoi']->id, 'so_luong' => 20],
        ],
    ]);

    $response->assertSessionHasErrors(['error']);
});
