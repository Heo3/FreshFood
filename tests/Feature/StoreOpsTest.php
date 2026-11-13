<?php

use App\Models\ChiTietDoiTra;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\GiaSanPhamCuaHang;
use App\Models\LoHang;
use App\Models\NhaCungCap;
use App\Models\PhieuDoiTra;
use App\Models\PhieuNhap;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use App\Models\TheKho;
use App\Models\TonKho;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setupStoreOpsPrerequisites(): array
{
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Q1 Operations Test',
        'dia_chi' => '120 Hai Bà Trưng, Q.1',
        'so_dien_thoai' => '0281234888',
        'gio_mo_cua' => '06:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Rau Củ Tươi Sạch',
        'duong_dan_seo' => 'rau-cu-tuoi-sach',
    ]);

    $supplier = NhaCungCap::create([
        'ma_ncc' => 'NCC-DALAT',
        'ten_nha_cung_cap' => 'Nông Trại Hữu Cơ Đà Lạt',
        'so_dien_thoai' => '02633888999',
        'dia_chi' => 'Đà Lạt, Lâm Đồng',
        'trang_thai' => 1,
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => '893600000777',
        'ten_san_pham' => 'Rau Cải Xanh VietGAP',
        'duong_dan_seo' => 'rau-cai-xanh-vietgap',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 25000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $staff = User::factory()->create([
        'name' => 'Nhân Viên Thu Ngân',
        'email' => 'cashier.test@freshfood.vn',
        'role' => 'nhan_vien',
        'store_id' => $store->id,
        'status' => 1,
    ]);

    $customer = User::factory()->create([
        'name' => 'Khách Hàng Mua',
        'email' => 'khach.test@freshfood.vn',
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $manager = User::factory()->create([
        'name' => 'Quản Lý Chi Nhánh',
        'email' => 'manager.test@freshfood.vn',
        'role' => 'quan_ly',
        'store_id' => $store->id,
        'status' => 1,
    ]);

    return compact('store', 'category', 'supplier', 'product', 'staff', 'manager', 'customer');
}

test('staff can access pos screen and view products with branch stock', function () {
    $data = setupStoreOpsPrerequisites();

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 20,
    ]);

    $this->actingAs($data['staff'])
        ->get('/store/pos')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/pos/Index')
            ->has('products', 1)
            ->where('products.0.ten_san_pham', 'Rau Cải Xanh VietGAP')
            ->where('products.0.ton_kho', 20)
        );
});

test('pos checkout creates order and triggers fefo stock deduction', function () {
    $data = setupStoreOpsPrerequisites();

    // Tạo 2 lô hàng
    $lot1 = LoHang::create([
        'ma_lo' => 'LO-POS-EXP-SOON',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(2)->toDateString(),
        'so_luong_nhap' => 5,
        'so_luong_con_lai' => 5,
        'gia_nhap' => 15000,
        'trang_thai' => 1,
    ]);

    $lot2 = LoHang::create([
        'ma_lo' => 'LO-POS-EXP-LATER',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(10)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 15000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 15,
    ]);

    // Bán 7kg tại quầy POS (5kg từ lô 1, 2kg từ lô 2)
    $response = $this->actingAs($data['staff'])
        ->postJson('/store/pos/thanh-toan', [
            'items' => [
                [
                    'san_pham_id' => $data['product']->id,
                    'so_luong' => 7,
                    'don_gia' => 25000,
                ],
            ],
            'phuong_thuc_thanh_toan' => 'tien_mat',
            'tien_khach_dua' => 200000,
            'ghi_chu' => 'Khách mua tại quầy',
        ]);

    $response->assertSuccessful();
    expect($response->json('success'))->toBeTrue();

    // Kiểm tra trừ kho FEFO
    $lot1->refresh();
    $lot2->refresh();
    expect((float) $lot1->so_luong_con_lai)->toBe(0.0);
    expect((float) $lot2->so_luong_con_lai)->toBe(8.0);

    // Kiểm tra TonKho
    $tonKho = TonKho::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->first();
    expect((float) $tonKho->so_luong_ton)->toBe(8.0);

    // Kiểm tra Đơn hàng POS
    $order = DonHang::where('cua_hang_id', $data['store']->id)->latest('id')->first();
    expect($order)->not->toBeNull();
    expect($order->trang_thai_don_hang)->toBe('da_giao');
    expect((float) $order->tong_thanhtoan)->toBe(175000.0);
});

test('pos checkout supports chuyen_khoan bank transfer payment', function () {
    $data = setupStoreOpsPrerequisites();

    LoHang::create([
        'ma_lo' => 'LO-POS-QR-1',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(5)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 15000,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 10,
    ]);

    $response = $this->actingAs($data['staff'])
        ->postJson('/store/pos/thanh-toan', [
            'items' => [
                [
                    'san_pham_id' => $data['product']->id,
                    'so_luong' => 2,
                    'don_gia' => 25000,
                ],
            ],
            'phuong_thuc_thanh_toan' => 'chuyen_khoan',
            'store_id' => $data['store']->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);

    $order = DonHang::where('cua_hang_id', $data['store']->id)->latest('id')->first();
    expect($order)->not->toBeNull();
    expect($order->phuong_thuc_thanh_toan)->toBe('chuyen_khoan');
    expect((float) $order->tong_thanhtoan)->toBe(50000.0);
});

test('staff can view inventory and near-expiry dashboard and apply quick discount', function () {
    $data = setupStoreOpsPrerequisites();

    // Tạo lô cận date 2 ngày
    LoHang::create([
        'ma_lo' => 'LO-NEAR-EXP',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->subDay()->toDateString(),
        'ngay_het_han' => now()->addDays(2)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 15000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 10,
    ]);

    // 1. Xem màn hình tồn kho
    $this->actingAs($data['staff'])
        ->get('/store/ton-kho')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('store/inventory/Index')->has('inventory', 1));

    // 2. Xem màn hình cảnh báo cận date
    $this->actingAs($data['staff'])
        ->get('/store/canh-bao-can-date')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('store/inventory/NearExpiry')->has('nearExpiryLots', 1));

    // 3. Áp dụng giá xả hàng nhanh (giảm từ 25.000đ xuống 12.000đ)
    $this->actingAs($data['staff'])
        ->post('/store/canh-bao-can-date/giam-gia', [
            'san_pham_id' => $data['product']->id,
            'gia_giam' => 12000,
        ])
        ->assertRedirect();

    $storePrice = GiaSanPhamCuaHang::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->first();
    expect((float) $storePrice->gia_giam)->toBe(12000.0);
});

test('staff can create inbound receipt which generates batch lots and updates stock', function () {
    $data = setupStoreOpsPrerequisites();

    $response = $this->actingAs($data['staff'])
        ->post('/store/phieu-nhap', [
            'nha_cung_cap_id' => $data['supplier']->id,
            'ghi_chu' => 'Nhập rau củ buổi sáng',
            'items' => [
                [
                    'san_pham_id' => $data['product']->id,
                    'ma_lo' => 'LO-NHAP-TEST-01',
                    'ngay_san_xuat' => now()->toDateString(),
                    'ngay_het_han' => now()->addDays(5)->toDateString(),
                    'so_luong' => 25,
                    'don_gia_nhap' => 15000,
                ],
            ],
        ]);

    $response->assertRedirect(route('store.inbound.index'));

    // Kiểm tra phiếu nhập được tạo
    $pn = PhieuNhap::where('cua_hang_id', $data['store']->id)->latest('id')->first();
    expect($pn)->not->toBeNull();
    expect((float) $pn->tong_tien)->toBe(375000.0);

    // Kiểm tra lô hàng sinh ra
    $lot = LoHang::where('ma_lo', 'LO-NHAP-TEST-01')->first();
    expect($lot)->not->toBeNull();
    expect((float) $lot->so_luong_nhap)->toBe(25.0);
    expect((float) $lot->so_luong_con_lai)->toBe(25.0);

    // Kiểm tra TonKho tăng lên 25
    $ton = TonKho::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->first();
    expect((float) $ton->so_luong_ton)->toBe(25.0);

    // Kiểm tra Sổ Thẻ Kho (TheKho)
    $theKho = TheKho::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->where('loai_thao_tac', 'nhap_kho')
        ->first();
    expect($theKho)->not->toBeNull();
    expect((float) $theKho->so_luong_thay_doi)->toBe(25.0);
});

test('staff can create stock audit and balance discrepancies', function () {
    $data = setupStoreOpsPrerequisites();

    $lot = LoHang::create([
        'ma_lo' => 'LO-AUDIT',
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'ngay_san_xuat' => now()->toDateString(),
        'ngay_het_han' => now()->addDays(7)->toDateString(),
        'so_luong_nhap' => 10,
        'so_luong_con_lai' => 10,
        'gia_nhap' => 15000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $data['store']->id,
        'san_pham_id' => $data['product']->id,
        'so_luong_ton' => 10,
    ]);

    // Kiểm kê thực tế chỉ đếm được 8kg (hao hụt 2kg do dập nát)
    $response = $this->actingAs($data['staff'])
        ->post('/store/kiem-ke', [
            'ghi_chu' => 'Kiểm kê cuối tuần',
            'items' => [
                [
                    'san_pham_id' => $data['product']->id,
                    'lo_hang_id' => $lot->id,
                    'so_luong_he_thong' => 10,
                    'so_luong_thuc_te' => 8,
                    'ly_do_lech' => 'Hao hụt dập nát khi trưng bày',
                ],
            ],
        ]);

    $response->assertRedirect(route('store.audit.index'));

    // Kiểm tra TonKho đã cân bằng về 8kg
    $tonKho = TonKho::where('cua_hang_id', $data['store']->id)
        ->where('san_pham_id', $data['product']->id)
        ->first();
    expect((float) $tonKho->so_luong_ton)->toBe(8.0);

    // Kiểm tra LoHang đã trừ hao hụt
    $lot->refresh();
    expect((float) $lot->so_luong_con_lai)->toBe(8.0);

    // Kiểm tra TheKho ghi nhận can_bang_kiem_ke
    $theKho = TheKho::where('cua_hang_id', $data['store']->id)
        ->where('loai_thao_tac', 'can_bang_kiem_ke')
        ->first();
    expect($theKho)->not->toBeNull();
    expect((float) $theKho->so_luong_thay_doi)->toBe(-2.0);
    expect((float) $theKho->ton_sau_thay_doi)->toBe(8.0);
});

test('manager can approve customer 24h return request', function () {
    $data = setupStoreOpsPrerequisites();

    $order = DonHang::create([
        'ma_don_hang' => 'DH-RETURN-01',
        'user_id' => $data['customer']->id,
        'cua_hang_id' => $data['store']->id,
        'tong_tien_hang' => 50000,
        'phi_giao_hang' => 0,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 50000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'da_thanh_toan',
        'trang_thai_don_hang' => 'da_giao',
        'ten_nguoi_nhan' => 'Khách Hàng',
        'so_dien_thoai_nhan' => '0901112233',
        'dia_chi_giao' => '120 Hai Bà Trưng, Q.1',
    ]);

    $ticket = PhieuDoiTra::create([
        'ma_phieu' => 'DT-TEST-001',
        'don_hang_id' => $order->id,
        'cua_hang_id' => $data['store']->id,
        'user_id' => $data['customer']->id,
        'nguoi_tao_id' => $data['customer']->id,
        'loai_doi_tra' => 'hoan_tien', // Hoàn tiền
        'tong_tien_hoan' => 50000,
        'trang_thai' => 0, // Chờ duyệt
        'ly_do' => 'Rau bị dập nát khi giao',
    ]);

    ChiTietDoiTra::create([
        'phieu_doi_tra_id' => $ticket->id,
        'san_pham_id' => $data['product']->id,
        'so_luong' => 2,
        'don_gia' => 25000,
        'thanh_tien' => 50000,
        'tinh_trang_hang' => 'hu_hong_huy',
    ]);

    // Nhân viên không được phép duyệt -> 403 Forbidden
    $this->actingAs($data['staff'])
        ->post("/store/doi-tra/{$ticket->id}/duyet")
        ->assertForbidden();

    // Quản lý duyệt yêu cầu đổi trả thành công
    $this->actingAs($data['manager'])
        ->post("/store/doi-tra/{$ticket->id}/duyet")
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->trang_thai)->toBe(1); // Đã chấp thuận
    expect($ticket->nguoi_duyet_id)->toBe($data['manager']->id);
});

test('staff can view outbound vouchers index and show page', function () {
    $data = setupStoreOpsPrerequisites();

    $phieuXuat = PhieuXuat::create([
        'ma_phieu' => 'PX-TEST-001',
        'cua_hang_id' => $data['store']->id,
        'loai_xuat' => PhieuXuat::LOAI_XUAT_BAN,
        'nguoi_tao_id' => $data['staff']->id,
        'trang_thai' => PhieuXuat::TRANG_THAI_DA_XUAT,
        'tong_so_luong' => 5,
        'tong_gia_tri' => 125000,
        'xuat_luc' => now(),
    ]);

    $this->actingAs($data['staff'])
        ->get('/store/phieu-xuat')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('store/outbound/Index')->has('phieuXuats', 1));

    $this->actingAs($data['staff'])
        ->get("/store/phieu-xuat/{$phieuXuat->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('store/outbound/Show')->where('phieuXuat.ma_phieu', 'PX-TEST-001'));
});
