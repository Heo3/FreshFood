<?php

use App\Models\Banner;
use App\Models\CuaHang;
use App\Models\DanhGiaSanPham;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAdminUser(): User
{
    $store = CuaHang::create([
        'ten_cua_hang' => 'Fresh Food Trụ Sở',
        'so_dien_thoai' => '0901112233',
        'dia_chi' => '123 Lê Lợi, Q1, TP.HCM',
        'trang_thai' => 1,
    ]);

    return User::factory()->create([
        'role' => 'admin',
        'store_id' => $store->id,
        'status' => 1,
    ]);
}

test('guest cannot access admin dashboard', function () {
    $response = $this->get('/admin');
    $response->assertRedirect('/login');
});

test('regular customer cannot access admin dashboard', function () {
    $user = User::factory()->create(['role' => 'khach_hang', 'status' => 1]);

    $response = $this->actingAs($user)->get('/admin');
    $response->assertForbidden();
});

test('admin can access dashboard and view metrics', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/Dashboard')
        ->has('metrics')
        ->has('chart_revenue')
        ->has('stores_revenue')
    );
});

test('admin can create and update categories', function () {
    $admin = createAdminUser();

    // Create
    $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
        'ten_danh_muc' => 'Thịt Bò Nhập Khẩu',
        'duong_dan_seo' => 'thit-bo-nhap-khau',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('danh_muc', ['ten_danh_muc' => 'Thịt Bò Nhập Khẩu']);

    $cat = DanhMuc::where('ten_danh_muc', 'Thịt Bò Nhập Khẩu')->first();

    // Update
    $updateResp = $this->actingAs($admin)->put(route('admin.categories.update', $cat->id), [
        'ten_danh_muc' => 'Thịt Bò Úc Thượng Hạng',
        'duong_dan_seo' => 'thit-bo-uc-thuong-hang',
    ]);
    $updateResp->assertRedirect();
    $this->assertDatabaseHas('danh_muc', ['ten_danh_muc' => 'Thịt Bò Úc Thượng Hạng']);
});

test('admin can create product and configure custom branch pricing', function () {
    $admin = createAdminUser();
    $cat = DanhMuc::create(['ten_danh_muc' => 'Trái Cây', 'duong_dan_seo' => 'trai-cay']);
    $store = CuaHang::first();

    // Create product
    $createResp = $this->actingAs($admin)->post(route('admin.products.store'), [
        'ten_san_pham' => 'Táo Envy New Zealand',
        'danh_muc_id' => $cat->id,
        'gia_ban' => 150000,
        'don_vi_tinh' => 'Kg',
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);
    $createResp->assertRedirect(route('admin.products.index'));
    $this->assertDatabaseHas('san_pham', ['ten_san_pham' => 'Táo Envy New Zealand']);

    $prod = SanPham::where('ten_san_pham', 'Táo Envy New Zealand')->first();

    // Configure custom branch price
    $priceResp = $this->actingAs($admin)->post(route('admin.products.branch-price', $prod->id), [
        'cua_hang_id' => $store->id,
        'gia_ban' => 165000,
        'gia_giam' => 155000,
    ]);
    $priceResp->assertRedirect();
    $this->assertDatabaseHas('gia_san_pham_cua_hang', [
        'san_pham_id' => $prod->id,
        'cua_hang_id' => $store->id,
        'gia_ban' => 165000,
    ]);
});

test('admin can create and manage coupons', function () {
    $admin = createAdminUser();

    $resp = $this->actingAs($admin)->post(route('admin.coupons.store'), [
        'ma_khuyen_mai' => 'FRESHVIP',
        'ten_chuong_trinh' => 'Giảm 20% Cho Khách VIP',
        'loai_giam_gia' => 'phan_tram',
        'gia_tri_giam' => 20,
        'don_hang_toi_thieu' => 200000,
        'giam_toi_da' => 50000,
        'ngay_bat_dau' => now()->toDateString(),
        'ngay_ket_thuc' => now()->addDays(30)->toDateString(),
        'so_luong' => 50,
        'trang_thai' => 1,
    ]);
    $resp->assertRedirect();
    $this->assertDatabaseHas('ma_giam_gia', ['ma_khuyen_mai' => 'FRESHVIP']);
});

test('admin can manage chain stores', function () {
    $admin = createAdminUser();

    $resp = $this->actingAs($admin)->post(route('admin.stores.store'), [
        'ten_cua_hang' => 'Fresh Food Thủ Đức',
        'so_dien_thoai' => '0933445566',
        'dia_chi' => '456 Võ Văn Ngân, TP. Thủ Đức',
        'gio_mo_cua' => '06:00',
        'gio_dong_cua' => '22:00',
        'trang_thai' => 1,
    ]);
    $resp->assertRedirect();
    $this->assertDatabaseHas('cua_hang', ['ten_cua_hang' => 'Fresh Food Thủ Đức']);
});

test('admin can manage staff personnel and roles', function () {
    $admin = createAdminUser();
    $store = CuaHang::first();

    $resp = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Trần Thu Ngân',
        'email' => 'thungan@freshfood.vn',
        'phone' => '0912345678',
        'role' => 'nhan_vien',
        'store_id' => $store->id,
        'password' => 'Password123!',
        'status' => 1,
    ]);
    $resp->assertRedirect();
    $this->assertDatabaseHas('users', ['email' => 'thungan@freshfood.vn', 'role' => 'nhan_vien']);
});

test('admin can update staff personnel information', function () {
    $admin = createAdminUser();
    $store = CuaHang::first();

    $staff = User::factory()->create([
        'name' => 'Nguyễn Nhân Viên Cũ',
        'email' => 'nhanvien.cu@freshfood.vn',
        'phone' => '0988776655',
        'role' => 'nhan_vien',
        'store_id' => $store->id,
        'status' => 1,
    ]);

    $resp = $this->actingAs($admin)->put(route('admin.users.update', $staff->id), [
        'name' => 'Nguyễn Nhân Viên Mới',
        'email' => 'nhanvien.cu@freshfood.vn',
        'phone' => '0988776655',
        'role' => 'quan_ly',
        'store_id' => '',
        'password' => '',
        'status' => 1,
    ]);

    $resp->assertRedirect();
    $resp->assertSessionHasNoErrors();
    $staff->refresh();
    expect($staff->name)->toBe('Nguyễn Nhân Viên Mới');
    expect($staff->role)->toBe('quan_ly');
    expect($staff->store_id)->toBeNull();
});

test('admin can toggle user lock status to lock and unlock account while preserving history', function () {
    $admin = createAdminUser();
    $store = CuaHang::first();

    $staff = User::factory()->create([
        'name' => 'Nhân Viên Cần Khóa',
        'email' => 'staff.lock@freshfood.vn',
        'role' => 'nhan_vien',
        'store_id' => $store->id,
        'status' => 1,
    ]);

    // 1. Khóa tài khoản
    $resp = $this->actingAs($admin)->post(route('admin.users.toggle-status', $staff->id));
    $resp->assertRedirect();
    $staff->refresh();
    expect($staff->status)->toBe(0);

    // 2. Mở khóa tài khoản
    $resp2 = $this->actingAs($admin)->post(route('admin.users.toggle-status', $staff->id));
    $resp2->assertRedirect();
    $staff->refresh();
    expect($staff->status)->toBe(1);

    // 3. Admin không thể tự khóa chính mình
    $respSelf = $this->actingAs($admin)->post(route('admin.users.toggle-status', $admin->id));
    $respSelf->assertRedirect();
    $respSelf->assertSessionHas('error');
    $admin->refresh();
    expect($admin->status)->toBe(1);

    // 4. Destroy method sets status to 0 instead of dropping row
    $respDelete = $this->actingAs($admin)->delete(route('admin.users.destroy', $staff->id));
    $respDelete->assertRedirect();
    $staff->refresh();
    expect($staff->status)->toBe(0);
    $this->assertDatabaseHas('users', ['id' => $staff->id, 'deleted_at' => null]);
});

test('admin can update order status', function () {
    $admin = createAdminUser();
    $store = CuaHang::first();

    $order = DonHang::create([
        'ma_don_hang' => 'DH-TEST-ADMIN',
        'user_id' => $admin->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 250000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 265000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Khách Test',
        'so_dien_thoai_nhan' => '0909090909',
        'dia_chi_giao' => '123 Test Street',
    ]);

    $resp = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->id), [
        'trang_thai_don_hang' => 'dang_chuan_bi',
        'ghi_chu' => 'Đã phân bổ lô hàng FEFO và chuyển nhân viên kho đóng gói',
    ]);
    $resp->assertRedirect();
    $this->assertDatabaseHas('don_hang', [
        'id' => $order->id,
        'trang_thai_don_hang' => 'dang_chuan_bi',
    ]);
});

test('admin can view analytical reports', function () {
    $admin = createAdminUser();

    $resp = $this->actingAs($admin)->get(route('admin.reports.index'));
    $resp->assertOk();
    $resp->assertInertia(fn ($page) => $page
        ->component('admin/reports/Index')
        ->has('metrics')
        ->has('inventory_by_store')
        ->has('near_expiry_lots')
    );
});

test('admin can manage promotional banners', function () {
    $admin = createAdminUser();

    // 1. Index
    $resp = $this->actingAs($admin)->get(route('admin.banners.index'));
    $resp->assertOk();
    $resp->assertInertia(fn ($page) => $page->component('admin/banners/Index')->has('banners'));

    // 2. Store
    $storeResp = $this->actingAs($admin)->post(route('admin.banners.store'), [
        'tieu_de' => 'Banner Rau Sạch Ưu Đãi 50%',
        'phu_de' => 'Áp dụng cho mọi đơn hàng hôm nay',
        'hinh_anh' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999',
        'duong_dan' => '/san-pham',
        'nhan_nut' => 'Khám Phá',
        'thu_tu' => 1,
        'trang_thai' => 1,
    ]);
    $storeResp->assertSessionHas('success');

    $banner = Banner::where('tieu_de', 'Banner Rau Sạch Ưu Đãi 50%')->first();
    $this->assertNotNull($banner);

    // 3. Toggle
    $toggleResp = $this->actingAs($admin)->post(route('admin.banners.toggle', $banner->id));
    $toggleResp->assertSessionHas('success');
    $this->assertEquals(0, $banner->fresh()->trang_thai);

    // 4. Destroy
    $deleteResp = $this->actingAs($admin)->delete(route('admin.banners.destroy', $banner->id));
    $deleteResp->assertSessionHas('success');
    $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
});

test('admin can view and moderate customer reviews', function () {
    $admin = createAdminUser();

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Trái Cây',
        'duong_dan_seo' => 'trai-cay-admin-test',
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => '893600000999',
        'ten_san_pham' => 'Táo Envy Size L',
        'duong_dan_seo' => 'tao-envy-size-l',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 120000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $review = DanhGiaSanPham::create([
        'user_id' => $admin->id,
        'san_pham_id' => $product->id,
        'so_sao' => 5,
        'noi_dung' => 'Táo rất tươi và ngọt!',
        'trang_thai' => 1,
    ]);

    // 1. Index
    $resp = $this->actingAs($admin)->get(route('admin.reviews.index'));
    $resp->assertOk();
    $resp->assertInertia(fn ($page) => $page->component('admin/reviews/Index')->has('reviews'));

    // 2. Toggle status
    $toggleResp = $this->actingAs($admin)->post(route('admin.reviews.toggle', $review->id));
    $toggleResp->assertSessionHas('success');
    $this->assertEquals(0, $review->fresh()->trang_thai);

    // 3. Delete
    $delResp = $this->actingAs($admin)->delete(route('admin.reviews.destroy', $review->id));
    $delResp->assertSessionHas('success');
    $this->assertDatabaseMissing('danh_gia_san_pham', ['id' => $review->id]);
});
