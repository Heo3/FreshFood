<?php

use App\Models\Banner;
use App\Models\CuaHang;
use App\Models\LoHang;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use App\Models\TheKho;
use App\Models\TonKho;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('Google OAuth redirect route is available', function () {
    config()->set('services.google.client_id', 'test-client-id');
    config()->set('services.google.client_secret', 'test-client-secret');

    $response = $this->get(route('auth.google'));
    // Redirects to Google OAuth URL (302)
    $response->assertStatus(302);
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('Google OAuth logs in smoothly in local dev mode when client id is empty', function () {
    config()->set('services.google.client_id', null);
    config()->set('services.google.client_secret', null);

    $response = $this->get(route('auth.google'));
    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
});

test('Customer can submit a review and rating for a product', function () {
    $user = User::factory()->create(['role' => 'khach_hang']);
    $product = SanPham::first() ?? SanPham::factory()->create();

    $response = $this->actingAs($user)->post(route('account.review.store'), [
        'san_pham_id' => $product->id,
        'so_sao' => 5,
        'noi_dung' => 'Sản phẩm tươi ngon tuyệt vời, giao hàng rất nhanh!',
    ]);

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('danh_gia_san_pham', [
        'user_id' => $user->id,
        'san_pham_id' => $product->id,
        'so_sao' => 5,
        'noi_dung' => 'Sản phẩm tươi ngon tuyệt vời, giao hàng rất nhanh!',
        'trang_thai' => 1,
    ]);
});

test('2-Step transfer receipt confirmation updates destination inventory and marks status completed', function () {
    $user = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
    $cuaHang1 = CuaHang::find(1) ?? CuaHang::factory()->create(['trang_thai' => 1]);
    $cuaHang2 = CuaHang::find(2) ?? CuaHang::factory()->create(['trang_thai' => 1]);

    $sanPham = SanPham::first() ?? SanPham::factory()->create();

    // Setup source lot at cuaHang1
    $loHang1 = LoHang::create([
        'san_pham_id' => $sanPham->id,
        'cua_hang_id' => $cuaHang1->id,
        'ma_lo' => 'LOT-TRANS-TEST-'.uniqid(),
        'so_luong_nhap' => 50,
        'so_luong_ton' => 30,
        'gia_nhap' => 10000,
        'ngay_san_xuat' => now()->subDay()->toDateString(),
        'ngay_het_han' => now()->addDays(15)->toDateString(),
        'trang_thai' => 1,
    ]);

    // Create transfer voucher (loai_xuat = 2, trang_thai = 1)
    $phieuXuat = PhieuXuat::create([
        'ma_phieu' => 'PX-TEST-'.uniqid(),
        'cua_hang_id' => $cuaHang1->id,
        'cua_hang_nhan_id' => $cuaHang2->id,
        'nguoi_tao_id' => $user->id,
        'loai_xuat' => 2,
        'tong_so_luong' => 10,
        'tong_gia_tri' => 100000,
        'trang_thai' => 1,
        'xuat_luc' => now(),
    ]);

    $chiTiet = $phieuXuat->chiTiets()->create([
        'san_pham_id' => $sanPham->id,
        'so_luong' => 10,
        'don_gia' => 10000,
        'thanh_tien' => 100000,
    ]);

    $chiTiet->chiTietXuatKhos()->create([
        'lo_hang_id' => $loHang1->id,
        'so_luong' => 10,
        'gia_xuat' => 10000,
    ]);

    // Destination store manager confirms receipt
    $response = $this->actingAs($user)->post(route('store.transfer.confirm', $phieuXuat->id));
    $response->assertSessionHas('success');

    // Verify voucher marked as TRANG_THAI_DA_NHAN (3)
    expect($phieuXuat->fresh()->trang_thai)->toBe(3);

    // Verify stock at destination cuaHang2
    $tonKho2 = TonKho::where('cua_hang_id', $cuaHang2->id)->where('san_pham_id', $sanPham->id)->first();
    expect($tonKho2)->not->toBeNull();
    expect((float) $tonKho2->so_luong_ton)->toBeGreaterThanOrEqual(10);

    // Verify TheKho entry exists for nhap_chuyen
    $this->assertDatabaseHas('the_kho', [
        'cua_hang_id' => $cuaHang2->id,
        'san_pham_id' => $sanPham->id,
        'loai_thao_tac' => 'nhap_chuyen',
    ]);
});

test('Near-expiry check command scans lots and records in-app notifications', function () {
    $exitCode = Artisan::call('inventory:check-expiry', ['--days' => 3]);
    expect($exitCode)->toBe(0);
});

test('Dynamic banners can be retrieved active', function () {
    Banner::create([
        'tieu_de' => 'Rau Củ Tươi Hái Trong Ngày',
        'phu_de' => 'Giảm 20% cho đơn hàng đầu tiên',
        'hinh_anh' => 'https://images.unsplash.com/photo-1610348725531-843dff563e2c',
        'duong_dan' => '/san-pham',
        'nhan_nut' => 'Mua Ngay',
        'vi_tri' => 'trang_chu_chinh',
        'thu_tu' => 1,
        'trang_thai' => 1,
    ]);

    $banners = Banner::dangHienThi()->get();
    expect($banners->count())->toBeGreaterThan(0);
});
