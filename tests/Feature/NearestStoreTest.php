<?php

use App\Models\CuaHang;
use App\Models\SanPham;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds the nearest store using gps coordinates', function () {
    // Branch 1: HCM Q1
    $storeQ1 = CuaHang::factory()->create([
        'ten_cua_hang' => 'FreshFood Quận 1',
        'dia_chi' => '128 Hai Bà Trưng, P. Đa Kao, Q1, TP.HCM',
        'vi_do' => 10.7854,
        'kinh_do' => 106.6975,
        'trang_thai' => 1,
    ]);

    // Branch 2: Hanoi Cầu Giấy
    $storeHN = CuaHang::factory()->create([
        'ten_cua_hang' => 'FreshFood Cầu Giấy',
        'dia_chi' => '72 Trần Thái Tông, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
        'vi_do' => 21.0322,
        'kinh_do' => 105.7876,
        'trang_thai' => 1,
    ]);

    // User is located in HCM (near Ben Thanh, lat: 10.7719, lon: 106.6983)
    $response = $this->postJson(route('store.find-nearest'), [
        'lat' => 10.7719,
        'lon' => 106.6983,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'store' => [
            'id' => $storeQ1->id,
            'ten_cua_hang' => 'FreshFood Quận 1',
        ],
    ]);
    expect($response->json('distance_km'))->toBeLessThan(5);
});

it('finds the nearest store using address keywords', function () {
    $storeHn = CuaHang::factory()->create([
        'ten_cua_hang' => 'FreshFood Cầu Giấy',
        'dia_chi' => '72 Trần Thái Tông, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
        'trang_thai' => 1,
    ]);

    $storeHcm = CuaHang::factory()->create([
        'ten_cua_hang' => 'FreshFood Quận 7',
        'dia_chi' => '85 Nguyễn Đức Cảnh, P. Tân Phong, Q7, TP.HCM',
        'trang_thai' => 1,
    ]);

    // 1. Hanoi address
    $resHn = $this->postJson(route('store.find-nearest'), [
        'address' => 'Số 15 ngõ 20 Hoàng Quốc Việt, Cầu Giấy, Hà Nội',
    ]);
    $resHn->assertSuccessful();
    expect($resHn->json('store.id'))->toBe($storeHn->id);

    // 2. HCM address
    $resHcm = $this->postJson(route('store.find-nearest'), [
        'address' => 'Chung cư Scenic Valley, Quận 7, TP Hồ Chí Minh',
    ]);
    $resHcm->assertSuccessful();
    expect($resHcm->json('store.id'))->toBe($storeHcm->id);
});

it('prevents checkout when delivery address is in a different geographic region from selected store', function () {
    $storeHcm = CuaHang::factory()->create([
        'ten_cua_hang' => 'FreshFood Quận 1',
        'dia_chi' => '128 Hai Bà Trưng, P. Đa Kao, Q1, TP.HCM',
        'vi_do' => 10.7854,
        'kinh_do' => 106.6975,
        'trang_thai' => 1,
    ]);

    $storeHn = CuaHang::factory()->create([
        'ten_cua_hang' => 'FreshFood Cầu Giấy',
        'dia_chi' => '72 Trần Thái Tông, Cầu Giấy, Hà Nội',
        'vi_do' => 21.0322,
        'kinh_do' => 105.7876,
        'trang_thai' => 1,
    ]);

    $product = SanPham::factory()->create();

    // Customer delivers to Hanoi but mistakenly chooses HCM store
    $payload = [
        'ten_nguoi_nhan' => 'Nguyễn Văn A',
        'so_dien_thoai_nhan' => '0987654321',
        'dia_chi_giao' => '12 Cầu Giấy, Hà Nội',
        'cua_hang_id' => $storeHcm->id,
        'phuong_thuc_thanh_toan' => 'cod',
        'items' => [
            ['san_pham_id' => $product->id, 'so_luong' => 1],
        ],
    ];

    $response = $this->from(route('checkout.index'))
        ->post(route('checkout.store'), $payload);

    $response->assertSessionHasErrors('cua_hang_id');
});

it('allows checkout when delivery address matches the store region', function () {
    $storeHcm = CuaHang::factory()->create([
        'ten_cua_hang' => 'FreshFood Quận 1',
        'dia_chi' => '128 Hai Bà Trưng, P. Đa Kao, Q1, TP.HCM',
        'vi_do' => 10.7854,
        'kinh_do' => 106.6975,
        'trang_thai' => 1,
    ]);

    $product = SanPham::factory()->create();

    $payload = [
        'ten_nguoi_nhan' => 'Nguyễn Văn A',
        'so_dien_thoai_nhan' => '0987654321',
        'dia_chi_giao' => '85 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
        'cua_hang_id' => $storeHcm->id,
        'phuong_thuc_thanh_toan' => 'cod',
        'items' => [
            ['san_pham_id' => $product->id, 'so_luong' => 1],
        ],
    ];

    $response = $this->from(route('checkout.index'))
        ->post(route('checkout.store'), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
});
