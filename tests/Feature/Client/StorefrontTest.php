<?php

use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\GiaSanPhamCuaHang;
use App\Models\GioHang;
use App\Models\MaGiamGia;
use App\Models\PhieuDoiTra;
use App\Models\SanPham;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setupStorefrontPrerequisites(): array
{
    $store = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Quận 1 Test',
        'dia_chi' => '120 Hai Bà Trưng, Q.1',
        'so_dien_thoai' => '0281234567',
        'gio_mo_cua' => '06:00:00',
        'gio_dong_cua' => '22:00:00',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create([
        'ten_danh_muc' => 'Rau Củ Hữu Cơ',
        'duong_dan_seo' => 'rau-cu-huu-co',
    ]);

    $product = SanPham::create([
        'danh_muc_id' => $category->id,
        'ma_va_vach' => '893600000001',
        'ten_san_pham' => 'Rau Cải Cúc Hữu Cơ Test',
        'duong_dan_seo' => 'rau-cai-cuc-huu-co-test',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 35000,
        'gia_giam' => 30000,
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    return compact('store', 'category', 'product');
}

test('home page renders with expected storefront data', function () {
    setupStorefrontPrerequisites();

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/Home')
        ->has('stores')
        ->has('categories')
        ->has('flashSales')
        ->has('freshToday')
        ->has('coupons')
    );
});

test('products catalog page can filter by search keyword', function () {
    $data = setupStorefrontPrerequisites();

    $response = $this->get('/san-pham?q='.urlencode($data['product']->ten_san_pham));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/Products')
        ->has('products.data')
    );
});

test('product detail page displays product information and related products', function () {
    $data = setupStorefrontPrerequisites();

    $response = $this->get('/san-pham/'.$data['product']->duong_dan_seo);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/ProductDetail')
        ->where('product.id', $data['product']->id)
        ->has('relatedProducts')
        ->has('stores')
    );
});

test('category page displays products belonging to category', function () {
    $data = setupStorefrontPrerequisites();

    $response = $this->get('/danh-muc/'.$data['category']->duong_dan_seo);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/Category')
        ->where('currentCategory.id', $data['category']->id)
        ->has('products.data')
    );
});

test('cart coupon verification api validates code correctly', function () {
    // Tạo mã giảm giá test tiền mặt
    MaGiamGia::create([
        'ma_khuyen_mai' => 'TESTFRESH20',
        'ten_chuong_trinh' => 'Giảm 20K đơn hàng test',
        'loai_giam_gia' => 'tien_mat',
        'gia_tri_giam' => 20000,
        'don_hang_toi_thieu' => 50000,
        'giam_toi_da' => 20000,
        'ngay_bat_dau' => now()->subDay(),
        'ngay_ket_thuc' => now()->addDays(5),
        'so_luong' => 100,
        'da_su_dung' => 0,
        'trang_thai' => 1,
    ]);

    $response = $this->postJson('/api/cart/check-coupon', [
        'code' => 'TESTFRESH20',
        'total' => 100000,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'coupon' => [
                'code' => 'TESTFRESH20',
                'discount_amount' => 20000,
            ],
        ]);

    // Test trường hợp không đủ giá trị tối thiểu
    $responseSubtotalLow = $this->postJson('/api/cart/check-coupon', [
        'code' => 'TESTFRESH20',
        'total' => 30000,
    ]);

    $responseSubtotalLow->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('checkout processes order creation successfully', function () {
    $data = setupStorefrontPrerequisites();

    $orderPayload = [
        'cua_hang_id' => $data['store']->id,
        'ten_nguoi_nhan' => 'Nguyễn Văn Test',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => 'Số 123 Đường Test, Quận 1, TP.HCM',
        'phuong_thuc_thanh_toan' => 'cod',
        'use_points' => false,
        'items' => [
            [
                'san_pham_id' => $data['product']->id,
                'so_luong' => 2,
            ],
        ],
    ];

    $response = $this->post('/thanh-toan', $orderPayload);

    $response->assertRedirect();
    $order = DonHang::where('so_dien_thoai_nhan', '0912345678')->latest()->first();
    expect($order)->not->toBeNull();
    expect((float) $order->tong_tien_hang)->toBeGreaterThan(0);
    expect($order->chiTiets)->toHaveCount(1);
});

test('authenticated customer can view account dashboard and order history', function () {
    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
        'loyalty_points' => 120,
    ]);

    $responseDashboard = $this->actingAs($user)->get('/tai-khoan');
    $responseDashboard->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('client/account/Index'));

    $responseOrders = $this->actingAs($user)->get('/tai-khoan/don-hang');
    $responseOrders->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('client/account/Orders'));

    $responsePoints = $this->actingAs($user)->get('/tai-khoan/diem-thuong');
    $responsePoints->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('client/account/Points'));

    $responseAddresses = $this->actingAs($user)->get('/tai-khoan/dia-chi');
    $responseAddresses->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('client/account/Addresses'));
});

test('customer can submit 24h return exchange request for eligible order', function () {
    $data = setupStorefrontPrerequisites();

    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $order = DonHang::create([
        'ma_don_hang' => 'ORD-RETURN-TEST',
        'user_id' => $user->id,
        'cua_hang_id' => $data['store']->id,
        'tong_tien_hang' => 120000,
        'phi_giao_hang' => 0,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 120000,
        'trang_thai_don_hang' => 'da_giao',
        'trang_thai_thanh_toan' => 'da_thanh_toan',
        'phuong_thuc_thanh_toan' => 'cod',
        'ten_nguoi_nhan' => $user->name,
        'so_dien_thoai_nhan' => '0988776655',
        'dia_chi_giao' => 'Địa chỉ test',
    ]);

    ChiTietDonHang::create([
        'don_hang_id' => $order->id,
        'san_pham_id' => $data['product']->id,
        'ten_san_pham' => $data['product']->ten_san_pham,
        'don_vi_tinh' => $data['product']->don_vi_tinh,
        'so_luong' => 2,
        'don_gia' => 60000,
        'gia_von' => 40000,
        'thanh_tien' => 120000,
    ]);

    $response = $this->actingAs($user)->post('/tai-khoan/yeu-cau-doi-tra', [
        'don_hang_id' => $order->id,
        'loai_doi_tra' => 'doi_hang',
        'ly_do' => 'Rau củ dập nát do lỗi vận chuyển hàng hóa',
        'items' => [
            [
                'san_pham_id' => $data['product']->id,
                'so_luong' => 1,
                'don_gia' => 60000,
            ],
        ],
    ]);

    $response->assertRedirect();
    $returnTicket = PhieuDoiTra::where('don_hang_id', $order->id)->first();
    expect($returnTicket)->not->toBeNull();
    expect($returnTicket->trang_thai)->toBe(0);
});

test('product catalog prioritizes price from table gia_san_pham_cua_hang when branch is selected', function () {
    $data = setupStorefrontPrerequisites();

    $store2 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Cầu Giấy Test',
        'dia_chi' => '88 Cầu Giấy, HN',
        'trang_thai' => 1,
    ]);

    // Giá đặc thù cho chi nhánh 2
    GiaSanPhamCuaHang::create([
        'cua_hang_id' => $store2->id,
        'san_pham_id' => $data['product']->id,
        'gia_ban' => 45000,
        'gia_giam' => 40000,
    ]);

    // Xem sản phẩm với chi nhánh 2
    $response = $this->withSession(['selected_store_id' => $store2->id])
        ->get('/san-pham?q='.urlencode($data['product']->ten_san_pham));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/Products')
        ->where('selectedStoreId', $store2->id)
        ->where('products.data.0.gia_ban', '45000.00')
        ->where('products.data.0.gia_giam', '40000.00')
    );
});

test('product detail prioritizes price from table gia_san_pham_cua_hang for selected store', function () {
    $data = setupStorefrontPrerequisites();

    $store2 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Đống Đa Test',
        'dia_chi' => '50 Chùa Bộc, HN',
        'trang_thai' => 1,
    ]);

    GiaSanPhamCuaHang::create([
        'cua_hang_id' => $store2->id,
        'san_pham_id' => $data['product']->id,
        'gia_ban' => 48000,
        'gia_giam' => 43000,
    ]);

    $response = $this->withSession(['selected_store_id' => $store2->id])
        ->get('/san-pham/'.$data['product']->duong_dan_seo);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('client/ProductDetail')
        ->where('selectedStoreId', $store2->id)
        ->where('product.gia_ban', '48000.00')
        ->where('product.gia_giam', '43000.00')
    );
});

test('branch select endpoint saves selected store to session and cookie', function () {
    $data = setupStorefrontPrerequisites();

    $response = $this->post('/chi-nhanh/chon', [
        'store_id' => $data['store']->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('selected_store_id', $data['store']->id);
    $response->assertCookie('freshfood_store_id', (string) $data['store']->id);
});

test('cart refresh prices endpoint returns prioritized prices from gia_san_pham_cua_hang', function () {
    $data = setupStorefrontPrerequisites();

    $store2 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Thanh Xuân Test',
        'dia_chi' => '12 Nguyễn Trãi, HN',
        'trang_thai' => 1,
    ]);

    GiaSanPhamCuaHang::create([
        'cua_hang_id' => $store2->id,
        'san_pham_id' => $data['product']->id,
        'gia_ban' => 52000,
        'gia_giam' => 49000,
    ]);

    $response = $this->postJson('/api/cart/refresh-prices', [
        'store_id' => $store2->id,
        'product_ids' => [$data['product']->id],
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'store_id' => $store2->id,
        'prices' => [
            $data['product']->id => [
                'san_pham_id' => $data['product']->id,
                'gia_ban' => 52000,
                'gia_giam' => 49000,
                'co_gia_chi_nhanh' => true,
            ],
        ],
    ]);
});

test('checkout order item price prioritizes gia_san_pham_cua_hang according to chosen branch', function () {
    $data = setupStorefrontPrerequisites();

    $storeBranch = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Tân Bình Test',
        'dia_chi' => '15 Cộng Hòa, Tân Bình',
        'trang_thai' => 1,
    ]);

    // Giá đặc thù chi nhánh Tân Bình: giá bán 60.000, giá giảm 55.000
    GiaSanPhamCuaHang::create([
        'cua_hang_id' => $storeBranch->id,
        'san_pham_id' => $data['product']->id,
        'gia_ban' => 60000,
        'gia_giam' => 55000,
    ]);

    $response = $this->post('/thanh-toan', [
        'ten_nguoi_nhan' => 'Nguyễn Văn Test',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '100 Lê Văn Sỹ, P.1, Q.Tân Bình',
        'cua_hang_id' => $storeBranch->id,
        'phuong_thuc_thanh_toan' => 'cod',
        'items' => [
            [
                'san_pham_id' => $data['product']->id,
                'so_luong' => 2,
            ],
        ],
    ]);

    $response->assertRedirect();
    $order = DonHang::where('cua_hang_id', $storeBranch->id)->latest()->first();
    expect($order)->not->toBeNull();
    // 2 x 55.000 = 110.000
    expect((float) $order->tong_tien_hang)->toEqual(110000.0);

    $orderItem = ChiTietDonHang::where('don_hang_id', $order->id)->first();
    expect($orderItem)->not->toBeNull();
    expect((float) $orderItem->don_gia)->toEqual(55000.0);
    expect((float) $orderItem->thanh_tien)->toEqual(110000.0);
});

test('user can add product to cart and it is saved into database table gio_hang', function () {
    $data = setupStorefrontPrerequisites();

    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $response = $this->actingAs($user)->postJson('/api/cart/add', [
        'san_pham_id' => $data['product']->id,
        'so_luong' => 2,
        'store_id' => $data['store']->id,
    ]);

    $response->assertSuccessful();
    $this->assertDatabaseHas('gio_hang', [
        'user_id' => $user->id,
        'san_pham_id' => $data['product']->id,
        'cua_hang_id' => $data['store']->id,
        'so_luong' => 2,
    ]);
});

test('logged in user adding to cart persists user_id into gio_hang', function () {
    $data = setupStorefrontPrerequisites();

    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $response = $this->actingAs($user)->postJson('/api/cart/add', [
        'san_pham_id' => $data['product']->id,
        'so_luong' => 3,
        'store_id' => $data['store']->id,
    ]);

    $response->assertSuccessful();
    $this->assertDatabaseHas('gio_hang', [
        'user_id' => $user->id,
        'san_pham_id' => $data['product']->id,
        'cua_hang_id' => $data['store']->id,
        'so_luong' => 3,
    ]);
});

test('cart update and remove endpoints properly update and remove database records', function () {
    $data = setupStorefrontPrerequisites();

    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    // Thêm vào giỏ
    $this->actingAs($user)->postJson('/api/cart/add', [
        'san_pham_id' => $data['product']->id,
        'so_luong' => 1,
        'store_id' => $data['store']->id,
    ]);

    // Cập nhật lên 4
    $this->actingAs($user)->postJson('/api/cart/update', [
        'san_pham_id' => $data['product']->id,
        'so_luong' => 4,
        'store_id' => $data['store']->id,
    ]);

    $this->assertDatabaseHas('gio_hang', [
        'user_id' => $user->id,
        'san_pham_id' => $data['product']->id,
        'so_luong' => 4,
    ]);

    // Xóa khỏi giỏ
    $this->actingAs($user)->postJson('/api/cart/remove', [
        'san_pham_id' => $data['product']->id,
        'store_id' => $data['store']->id,
    ]);

    $this->assertDatabaseMissing('gio_hang', [
        'user_id' => $user->id,
        'san_pham_id' => $data['product']->id,
    ]);
});

test('guest cart items are merged to user account upon login', function () {
    $data = setupStorefrontPrerequisites();

    // Khách vãng lai thêm sản phẩm vào giỏ
    $response = $this->postJson('/api/cart/add', [
        'san_pham_id' => $data['product']->id,
        'so_luong' => 2,
        'store_id' => $data['store']->id,
    ]);

    $cartItem = GioHang::where('san_pham_id', $data['product']->id)->first();
    expect($cartItem)->not->toBeNull();
    expect($cartItem->user_id)->toBeNull();

    // Khách đăng nhập kèm session cookie của khách
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    $sessionCookie = $response->getCookie(config('session.cookie'))?->getValue();

    $this->withCookie(config('session.cookie'), $sessionCookie)
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    // Kiểm tra hàng trong giỏ đã được gắn user_id
    $this->assertDatabaseHas('gio_hang', [
        'user_id' => $user->id,
        'san_pham_id' => $data['product']->id,
    ]);
});

test('products search suggestions api returns matching products', function () {
    $data = setupStorefrontPrerequisites();

    $response = $this->getJson('/api/products/search-suggestions?q=C%E1%BA%A3i');

    $response->assertSuccessful()
        ->assertJsonStructure(['products'])
        ->assertJsonFragment([
            'id' => $data['product']->id,
            'ten_san_pham' => $data['product']->ten_san_pham,
        ]);
});

test('cart items are automatically transitioned when selecting new store or adding from new store', function () {
    $data = setupStorefrontPrerequisites();

    $store2 = CuaHang::create([
        'ten_cua_hang' => 'FreshFood Chi Nhánh 2',
        'dia_chi' => '200 Lê Duẩn, Q.1',
        'trang_thai' => 1,
    ]);

    $product2 = SanPham::create([
        'danh_muc_id' => $data['category']->id,
        'ma_va_vach' => '893600000099',
        'ten_san_pham' => 'Thịt Bò Úc Test',
        'duong_dan_seo' => 'thit-bo-uc-test',
        'don_vi_tinh' => 'kg',
        'gia_ban' => 250000,
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    // 1. Thêm sản phẩm 1 từ chi nhánh 1 -> Thành công
    $resp1 = $this->actingAs($user)->postJson('/api/cart/add', [
        'san_pham_id' => $data['product']->id,
        'so_luong' => 1,
        'store_id' => $data['store']->id,
    ]);
    $resp1->assertSuccessful();

    // 2. Chuyển chi nhánh sang chi nhánh 2 -> Tự động chuyển giỏ hàng sang chi nhánh 2 mà không bắt người dùng xóa
    $this->actingAs($user)->post('/chi-nhanh/chon', [
        'store_id' => $store2->id,
    ]);
    expect(GioHang::where('user_id', $user->id)->first()->cua_hang_id)->toBe($store2->id);

    // 3. Thêm sản phẩm 2 từ chi nhánh 2 -> Cả 2 sản phẩm đều được giữ lại và thuộc chi nhánh 2
    $resp2 = $this->actingAs($user)->postJson('/api/cart/add', [
        'san_pham_id' => $product2->id,
        'so_luong' => 1,
        'store_id' => $store2->id,
    ]);
    $resp2->assertSuccessful();

    expect(GioHang::where('user_id', $user->id)->count())->toBe(2);
    expect(GioHang::where('user_id', $user->id)->where('cua_hang_id', $store2->id)->count())->toBe(2);

    // 4. Đổi chi nhánh kèm cờ clear_cart = true -> Tự động xóa sạch toàn bộ giỏ hàng
    $this->actingAs($user)->post('/chi-nhanh/chon', [
        'store_id' => $data['store']->id,
        'clear_cart' => true,
    ]);
    expect(GioHang::where('user_id', $user->id)->count())->toBe(0);

    // 5. Thêm lại sản phẩm và gọi API clear -> Giỏ hàng rỗng
    $this->actingAs($user)->postJson('/api/cart/add', [
        'san_pham_id' => $data['product']->id,
        'so_luong' => 2,
        'store_id' => $data['store']->id,
    ]);
    expect(GioHang::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)->postJson('/api/cart/clear')->assertSuccessful();
    expect(GioHang::where('user_id', $user->id)->count())->toBe(0);
});
