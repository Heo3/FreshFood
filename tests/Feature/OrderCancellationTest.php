<?php

use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LichSuTichDiem;
use App\Models\MaGiamGia;
use App\Models\SanPham;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCancelTestOrder(User $customer, string $status = 'cho_xac_nhan', ?int $couponId = null): DonHang
{
    $store = CuaHang::first() ?? CuaHang::create([
        'ten_cua_hang' => 'FreshFood Store Test',
        'dia_chi' => '12 Nguyễn Trãi, Q.1',
        'so_dien_thoai' => '0909999888',
        'gio_mo_cua' => '07:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::first() ?? DanhMuc::create([
        'ten_danh_muc' => 'Thịt tươi hữu cơ',
        'duong_dan_seo' => 'thit-tuoi-huu-co-'.uniqid(),
    ]);

    $product = SanPham::first() ?? SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => 'CANCEL'.rand(1000, 9999),
        'ten_san_pham' => 'Thịt Heo Sinh Học VietGAP',
        'duong_dan_seo' => 'thit-heo-sinh-hoc-'.uniqid(),
        'don_vi_tinh' => 'kg',
        'gia_ban' => 160000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'FF-TEST-'.strtoupper(uniqid()),
        'user_id' => $customer->id,
        'cua_hang_id' => $store->id,
        'ma_giam_gia_id' => $couponId,
        'tong_tien_hang' => 160000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => $couponId ? 20000 : 0,
        'tong_thanhtoan' => $couponId ? 155000 : 175000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => $status,
        'ten_nguoi_nhan' => $customer->name,
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '123 Đường Test, Quận 1',
    ]);

    ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $product->id,
        'ten_san_pham' => $product->ten_san_pham,
        'don_vi_tinh' => 'kg',
        'don_gia' => 160000,
        'gia_von' => 110000,
        'so_luong' => 1,
        'thanh_tien' => 160000,
    ]);

    return $order;
}

test('khách hàng có thể chuyển trạng thái đơn hàng sang da_huy trong thời gian cho_xac_nhan', function () {
    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $order = createCancelTestOrder($customer, DonHang::TRANG_THAI_CHO_XAC_NHAN);

    $response = $this->actingAs($customer)
        ->from(route('account.orders.show', $order->ma_don_hang))
        ->post(route('account.orders.cancel', $order->id), [
            'ly_do' => 'Đổi ý không muốn mua nữa',
        ]);

    $response->assertRedirect(route('account.orders.show', $order->ma_don_hang));
    $response->assertSessionHas('success');

    $order->refresh();
    expect($order->trang_thai_don_hang)->toBe(DonHang::TRANG_THAI_DA_HUY);
    expect($order->ghi_chu)->toContain('[Khách hủy: Đổi ý không muốn mua nữa]');
});

test('khi đã duyệt và chuyển sang dang_chuan_bi thì hệ thống KHÔNG CHO PHÉP da_huy', function () {
    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $order = createCancelTestOrder($customer, DonHang::TRANG_THAI_DANG_CHUAN_BI);

    $response = $this->actingAs($customer)
        ->from(route('account.orders.show', $order->ma_don_hang))
        ->post(route('account.orders.cancel', $order->id), [
            'ly_do' => 'Muốn hủy đơn hàng',
        ]);

    $response->assertRedirect(route('account.orders.show', $order->ma_don_hang));
    $response->assertSessionHas('error', 'Đơn hàng đã được duyệt và đang chuẩn bị (đóng gói), không thể hủy đơn hàng.');

    $order->refresh();
    // Đơn hàng giữ nguyên trạng thái dang_chuan_bi, không cho chuyển sang da_huy
    expect($order->trang_thai_don_hang)->toBe(DonHang::TRANG_THAI_DANG_CHUAN_BI);
});

test('không cho phép hủy đơn khi đơn hàng đang giao, đã giao hoặc đã hủy', function () {
    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    // Đang giao
    $orderShipping = createCancelTestOrder($customer, DonHang::TRANG_THAI_DANG_GIAO);
    $response = $this->actingAs($customer)->post(route('account.orders.cancel', $orderShipping->id));
    $response->assertSessionHas('error');
    $orderShipping->refresh();
    expect($orderShipping->trang_thai_don_hang)->toBe(DonHang::TRANG_THAI_DANG_GIAO);

    // Đã giao
    $orderDelivered = createCancelTestOrder($customer, DonHang::TRANG_THAI_DA_GIAO);
    $responseDelivered = $this->actingAs($customer)->post(route('account.orders.cancel', $orderDelivered->id));
    $responseDelivered->assertSessionHas('error');
    $orderDelivered->refresh();
    expect($orderDelivered->trang_thai_don_hang)->toBe(DonHang::TRANG_THAI_DA_GIAO);

    // Đã hủy trước đó
    $orderCancelled = createCancelTestOrder($customer, DonHang::TRANG_THAI_DA_HUY);
    $responseCancelled = $this->actingAs($customer)->post(route('account.orders.cancel', $orderCancelled->id));
    $responseCancelled->assertSessionHas('error', 'Đơn hàng này đã được hủy trước đó.');
});

test('khách hàng không thể hủy đơn hàng của khách hàng khác', function () {
    $customerA = User::factory()->create(['role' => 'khach_hang', 'status' => 1]);
    $customerB = User::factory()->create(['role' => 'khach_hang', 'status' => 1]);

    $orderOfA = createCancelTestOrder($customerA, DonHang::TRANG_THAI_CHO_XAC_NHAN);

    $response = $this->actingAs($customerB)
        ->post(route('account.orders.cancel', $orderOfA->id));

    $response->assertStatus(403);
    $orderOfA->refresh();
    expect($orderOfA->trang_thai_don_hang)->toBe(DonHang::TRANG_THAI_CHO_XAC_NHAN);
});

test('khi hủy đơn cho_xac_nhan thành công, mã giảm giá và điểm thưởng được hoàn trả chính xác', function () {
    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
        'loyalty_points' => 30,
    ]);

    $coupon = MaGiamGia::create([
        'ma_khuyen_mai' => 'COUPONREFUND'.rand(100, 999),
        'ten_chuong_trinh' => 'Test Coupon Refund',
        'loai_giam_gia' => 'tien_mat',
        'gia_tri_giam' => 20000,
        'don_hang_toi_thieu' => 100000,
        'ngay_bat_dau' => Carbon::now()->subDays(2),
        'ngay_ket_thuc' => Carbon::now()->addDays(20),
        'so_luong' => 100,
        'da_su_dung' => 5,
        'trang_thai' => 1,
    ]);

    $order = createCancelTestOrder($customer, DonHang::TRANG_THAI_CHO_XAC_NHAN, $coupon->id);

    // Ghi nhận khách đã dùng 20 điểm cho đơn này
    LichSuTichDiem::create([
        'user_id' => $customer->id,
        'don_hang_id' => $order->id,
        'loai_giao_dich' => 'tieu_diem',
        'so_diem_thay_doi' => -20,
        'so_diem_sau_giao_dich' => 30,
        'ghi_chu' => 'Tiêu điểm cho đơn hàng',
    ]);

    $this->actingAs($customer)
        ->post(route('account.orders.cancel', $order->id), [
            'ly_do' => 'Đặt nhầm số lượng',
        ]);

    $coupon->refresh();
    $customer->refresh();
    $order->refresh();

    // Lượt dùng mã giảm giá được giảm đi 1 (5 -> 4)
    expect($coupon->da_su_dung)->toBe(4);

    // Điểm thưởng được hoàn lại (+20 điểm: 30 -> 50)
    expect($customer->loyalty_points)->toBe(50);

    // Có bản ghi hoàn điểm trong lịch sử
    $refundPointLog = LichSuTichDiem::where('don_hang_id', $order->id)
        ->where('loai_giao_dich', 'hoan_diem')
        ->first();

    expect($refundPointLog)->not->toBeNull();
    expect($refundPointLog->so_diem_thay_doi)->toBe(20);
});

test('khi hủy đơn hàng thì trừ lại số điểm đã cộng (tích lũy) từ đơn hàng đó', function () {
    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
        'loyalty_points' => 50, // Ban đầu có 50 điểm (đã gồm 15 điểm tích từ đơn này)
    ]);

    $order = createCancelTestOrder($customer, DonHang::TRANG_THAI_CHO_XAC_NHAN);

    // Giả sử khi đặt đơn này, khách được cộng +15 điểm
    LichSuTichDiem::create([
        'user_id' => $customer->id,
        'don_hang_id' => $order->id,
        'loai_giao_dich' => 'tich_diem',
        'so_diem_thay_doi' => 15,
        'so_diem_sau_giao_dich' => 50,
        'ghi_chu' => "Tích 15 điểm từ đơn hàng #{$order->ma_don_hang}",
    ]);

    // Khách hàng bấm hủy đơn
    $this->actingAs($customer)
        ->post(route('account.orders.cancel', $order->id), [
            'ly_do' => 'Không còn nhu cầu',
        ]);

    $customer->refresh();
    $order->refresh();

    expect($order->trang_thai_don_hang)->toBe(DonHang::TRANG_THAI_DA_HUY);

    // Điểm của khách bị trừ lại 15 điểm: 50 - 15 = 35 điểm
    expect((int) $customer->loyalty_points)->toBe(35);

    // Có bản ghi thu hồi điểm trong lịch sử tích điểm
    $revokePointLog = LichSuTichDiem::where('don_hang_id', $order->id)
        ->where('loai_giao_dich', 'admin_dieu_chinh')
        ->first();

    expect($revokePointLog)->not->toBeNull();
    expect($revokePointLog->so_diem_thay_doi)->toBe(-15);
    expect($revokePointLog->so_diem_sau_giao_dich)->toBe(35);
    expect($revokePointLog->ghi_chu)->toContain('Thu hồi 15 điểm tích lũy do hủy đơn hàng');
});

test('admin hủy đơn hàng cũng tự động hoàn điểm đã tiêu và trừ lại điểm đã cộng', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 1,
    ]);

    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
        'loyalty_points' => 20,
    ]);

    $order = createCancelTestOrder($customer, DonHang::TRANG_THAI_CHO_XAC_NHAN);

    // Đơn này khách đã tiêu 10 điểm và được tích 8 điểm
    LichSuTichDiem::create([
        'user_id' => $customer->id,
        'don_hang_id' => $order->id,
        'loai_giao_dich' => 'tieu_diem',
        'so_diem_thay_doi' => -10,
        'so_diem_sau_giao_dich' => 12,
        'ghi_chu' => 'Tiêu 10 điểm',
    ]);

    LichSuTichDiem::create([
        'user_id' => $customer->id,
        'don_hang_id' => $order->id,
        'loai_giao_dich' => 'tich_diem',
        'so_diem_thay_doi' => 8,
        'so_diem_sau_giao_dich' => 20,
        'ghi_chu' => 'Tích 8 điểm',
    ]);

    // Admin chuyển trạng thái sang da_huy
    $this->actingAs($admin)
        ->post(route('admin.orders.update-status', $order->id), [
            'trang_thai_don_hang' => 'da_huy',
            'ghi_chu' => 'Hủy do khách yêu cầu qua hotline',
        ]);

    $customer->refresh();
    $order->refresh();

    expect($order->trang_thai_don_hang)->toBe('da_huy');

    // Điểm khách: 20 + 10 (hoàn điểm tiêu) - 8 (trừ điểm tích) = 22 điểm
    expect((int) $customer->loyalty_points)->toBe(22);

    $hoanLog = LichSuTichDiem::where('don_hang_id', $order->id)->where('loai_giao_dich', 'hoan_diem')->first();
    expect($hoanLog)->not->toBeNull();
    expect($hoanLog->so_diem_thay_doi)->toBe(10);

    $thuHoiLog = LichSuTichDiem::where('don_hang_id', $order->id)->where('loai_giao_dich', 'admin_dieu_chinh')->first();
    expect($thuHoiLog)->not->toBeNull();
    expect($thuHoiLog->so_diem_thay_doi)->toBe(-8);
});
