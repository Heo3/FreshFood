<?php

use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LichSuTichDiem;
use App\Models\LoHang;
use App\Models\MaGiamGia;
use App\Models\NhaCungCap;
use App\Models\SanPham;
use App\Models\TonKho;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setupLoyaltyTestEnvironment(): array
{
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Loyalty Test',
        'dia_chi' => '123 Đường Test, Q.1',
        'so_dien_thoai' => '02899998888',
        'gio_mo_cua' => '06:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Trái Cây',
        'duong_dan_seo' => 'trai-cay-'.uniqid(),
    ]);

    $supplier = NhaCungCap::create([
        'ma_ncc' => 'NCC-'.uniqid(),
        'ten_nha_cung_cap' => 'NCC Trái Cây',
        'so_dien_thoai' => '02811112222',
        'dia_chi' => 'Tiền Giang',
        'trang_thai' => 1,
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => '893600000999',
        'ten_san_pham' => 'Xoài Cát Hòa Lộc',
        'duong_dan_seo' => 'xoai-cat-hoa-loc-'.uniqid(),
        'don_vi_tinh' => 'kg',
        'gia_ban' => 50000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $batch = LoHang::create([
        'ma_lo' => 'LO-TEST-'.uniqid(),
        'san_pham_id' => $product->id,
        'cua_hang_id' => $store->id,
        'nha_cung_cap_id' => $supplier->id,
        'ngay_san_xuat' => now()->subDays(2)->toDateString(),
        'ngay_het_han' => now()->addDays(10)->toDateString(),
        'so_luong_nhap' => 100,
        'so_luong_con_lai' => 100,
        'gia_nhap' => 30000,
        'trang_thai' => 1,
    ]);

    TonKho::create([
        'cua_hang_id' => $store->id,
        'san_pham_id' => $product->id,
        'so_luong_ton' => 100,
    ]);

    $staff = User::factory()->create([
        'name' => 'Thu Ngân POS',
        'email' => 'cashier.'.uniqid().'@freshfood.vn',
        'role' => 'nhan_vien',
        'store_id' => $store->id,
        'status' => 1,
    ]);

    return compact('store', 'product', 'batch', 'staff');
}

test('cart page provides available active coupons', function () {
    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    MaGiamGia::create([
        'ma_khuyen_mai' => 'FRESH10K',
        'ten_chuong_trinh' => 'Giảm 10.000đ cho đơn từ 100.000đ',
        'loai_giam_gia' => 'tien_mat',
        'gia_tri_giam' => 10000,
        'don_hang_toi_thieu' => 100000,
        'ngay_bat_dau' => now()->subDay(),
        'ngay_ket_thuc' => now()->addDays(5),
        'so_luong' => 100,
        'da_su_dung' => 0,
        'trang_thai' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('cart.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/Cart')
        ->has('availableCoupons', 1)
        ->where('availableCoupons.0.ma_khuyen_mai', 'FRESH10K')
    );
});

test('pos can search customer by phone and get loyalty points', function () {
    $data = setupLoyaltyTestEnvironment();

    $customer = User::factory()->create([
        'name' => 'Nguyễn Văn Thành Viên',
        'phone' => '0912345678',
        'loyalty_points' => 25, // 25 points = 25.000đ
        'role' => 'khach_hang',
    ]);

    $response = $this->actingAs($data['staff'])
        ->getJson(route('store.pos.customer', ['phone' => '0912345678']));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => 'Nguyễn Văn Thành Viên',
                'phone' => '0912345678',
                'loyalty_points' => 25,
                'points_value' => 25000,
            ],
        ]);
});

test('pos checkout with points redemption deducts 1000đ per point and accumulates 1 point per 10k paid', function () {
    $data = setupLoyaltyTestEnvironment();

    $customer = User::factory()->create([
        'name' => 'Khách Hàng Thân Thiết',
        'phone' => '0988777666',
        'loyalty_points' => 20, // 20 điểm = 20.000đ
        'role' => 'khach_hang',
    ]);

    // Mua 2kg xoài: 2 * 50.000đ = 100.000đ
    // Dùng 15 điểm = giảm 15.000đ -> Thực trả: 85.000đ
    // Điểm tích mới: floor(85.000 / 10.000) = 8 điểm
    // Điểm còn lại: 20 - 15 + 8 = 13 điểm
    $response = $this->actingAs($data['staff'])
        ->postJson(route('store.pos.checkout'), [
            'store_id' => $data['store']->id,
            'items' => [
                [
                    'san_pham_id' => $data['product']->id,
                    'so_luong' => 2,
                    'don_gia' => 50000,
                ],
            ],
            'phuong_thuc_thanh_toan' => 'tien_mat',
            'tien_khach_dua' => 100000,
            'customer_phone' => '0988777666',
            'use_points' => true,
            'points_to_use' => 15,
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $customer->refresh();
    expect((int) $customer->loyalty_points)->toBe(13);

    // Verify order in database
    $donHang = DonHang::where('cua_hang_id', $data['store']->id)->latest('id')->first();
    expect($donHang)->not->toBeNull();
    expect((float) $donHang->tong_tien_hang)->toBe(100000.0);
    expect((float) $donHang->tien_giam_gia)->toBe(15000.0);
    expect((float) $donHang->tong_thanhtoan)->toBe(85000.0);

    // Verify transaction logs
    $tieuDiem = LichSuTichDiem::where('don_hang_id', $donHang->id)->where('loai_giao_dich', 'tieu_diem')->first();
    expect($tieuDiem)->not->toBeNull();
    expect($tieuDiem->so_diem_thay_doi)->toBe(-15);

    $tichDiem = LichSuTichDiem::where('don_hang_id', $donHang->id)->where('loai_giao_dich', 'tich_diem')->first();
    expect($tichDiem)->not->toBeNull();
    expect($tichDiem->so_diem_thay_doi)->toBe(8);
});

test('pos checkout with new customer phone creates user and earns 1 point per 10k paid', function () {
    $data = setupLoyaltyTestEnvironment();

    $newPhone = '0901234999';

    // Mua 1kg xoài = 50.000đ -> tích floor(50.000 / 10.000) = 5 điểm
    $response = $this->actingAs($data['staff'])
        ->postJson(route('store.pos.checkout'), [
            'store_id' => $data['store']->id,
            'items' => [
                [
                    'san_pham_id' => $data['product']->id,
                    'so_luong' => 1,
                    'don_gia' => 50000,
                ],
            ],
            'phuong_thuc_thanh_toan' => 'tien_mat',
            'tien_khach_dua' => 50000,
            'customer_phone' => $newPhone,
            'customer_name' => 'Chị Hoa',
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $newUser = User::where('phone', $newPhone)->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('Chị Hoa');
    expect((int) $newUser->loyalty_points)->toBe(5);

    $tichDiem = LichSuTichDiem::where('user_id', $newUser->id)->where('loai_giao_dich', 'tich_diem')->first();
    expect($tichDiem)->not->toBeNull();
    expect($tichDiem->so_diem_thay_doi)->toBe(5);
});

test('coupon applied in cart carries over to checkout page seamlessly', function () {
    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    MaGiamGia::create([
        'ma_khuyen_mai' => 'VOUCHER20K',
        'ten_chuong_trinh' => 'Giảm 20.000đ cho đơn từ 150.000đ',
        'loai_giam_gia' => 'tien_mat',
        'gia_tri_giam' => 20000,
        'don_hang_toi_thieu' => 150000,
        'ngay_bat_dau' => now()->subDay(),
        'ngay_ket_thuc' => now()->addDays(5),
        'so_luong' => 100,
        'da_su_dung' => 0,
        'trang_thai' => 1,
    ]);

    // 1. Áp dụng mã trong giỏ hàng
    $checkResponse = $this->actingAs($user)->postJson(route('cart.check-coupon'), [
        'code' => 'VOUCHER20K',
        'total' => 200000,
    ]);

    $checkResponse->assertOk()->assertJson([
        'success' => true,
        'coupon' => [
            'code' => 'VOUCHER20K',
            'discount_amount' => 20000,
        ],
    ]);

    // 2. Chuyển sang trang thanh toán: mã được tự động mang theo
    $checkoutResponse = $this->actingAs($user)->get(route('checkout.index'));

    $checkoutResponse->assertOk();
    $checkoutResponse->assertInertia(fn (Assert $page) => $page
        ->component('client/Checkout')
        ->where('appliedCoupon.code', 'VOUCHER20K')
        ->where('appliedCoupon.value', 20000)
    );

    // 3. Cũng hỗ trợ mang theo qua query parameter ?coupon=...
    $queryResponse = $this->actingAs($user)->get(route('checkout.index', ['coupon' => 'VOUCHER20K']));
    $queryResponse->assertOk();
    $queryResponse->assertInertia(fn (Assert $page) => $page
        ->component('client/Checkout')
        ->where('appliedCoupon.code', 'VOUCHER20K')
    );
});

test('customer can remove applied coupon from session', function () {
    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $this->actingAs($user)->withSession(['applied_coupon_code' => 'TESTCODE'])
        ->postJson(route('cart.remove-coupon'))
        ->assertOk()
        ->assertJson(['success' => true]);

    $response = $this->actingAs($user)->get(route('checkout.index'));
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/Checkout')
        ->where('appliedCoupon', null)
    );
});
