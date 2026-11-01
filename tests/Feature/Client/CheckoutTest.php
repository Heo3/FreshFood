<?php

use App\Models\CuaHang;
use App\Models\GioHang;
use App\Models\SanPham;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * @test
 */
it('clears guest cart after successful checkout', function () {
    // Create a store and a product
    $store = CuaHang::factory()->create(['trang_thai' => 1]);
    $product = SanPham::factory()->create();

    // Guest adds item to cart via API to establish real session
    $addResponse = $this->postJson('/api/cart/add', [
        'san_pham_id' => $product->id,
        'so_luong' => 2,
        'store_id' => $store->id,
    ]);
    $addResponse->assertSuccessful();

    $cartItem = GioHang::where('san_pham_id', $product->id)->first();
    expect($cartItem)->not->toBeNull();
    $sessionId = $cartItem->session_id;

    $payload = [
        'ten_nguoi_nhan' => 'Khách Hàng',
        'so_dien_thoai_nhan' => '0123456789',
        'dia_chi_giao' => '123 Đường ABC',
        'cua_hang_id' => $store->id,
        'phuong_thuc_thanh_toan' => 'cod',
        'items' => [
            ['san_pham_id' => $product->id, 'so_luong' => 2],
        ],
    ];

    $sessionCookie = $addResponse->getCookie(config('session.cookie'))->getValue();

    // Perform checkout request with the same session cookie
    $this->withCookie(config('session.cookie'), $sessionCookie)
        ->postJson('/thanh-toan', $payload)
        ->assertRedirect();

    // After checkout, cart for this session should be empty
    expect(GioHang::where('session_id', $sessionId)->count())->toBe(0);
});

/**
 * @test
 */
it('clears authenticated user cart after successful checkout', function () {
    $user = User::factory()->create();
    $store = CuaHang::factory()->create(['trang_thai' => 1]);
    $product = SanPham::factory()->create();

    // Add item to user cart
    GioHang::create([
        'user_id' => $user->id,
        'cua_hang_id' => $store->id,
        'san_pham_id' => $product->id,
        'so_luong' => 1,
    ]);

    $payload = [
        'ten_nguoi_nhan' => $user->name,
        'so_dien_thoai_nhan' => $user->phone ?? '0123456789',
        'dia_chi_giao' => '456 Đường XYZ',
        'cua_hang_id' => $store->id,
        'phuong_thuc_thanh_toan' => 'cod',
        'items' => [
            ['san_pham_id' => $product->id, 'so_luong' => 1],
        ],
    ];

    actingAs($user)
        ->postJson('/thanh-toan', $payload)
        ->assertRedirect();

    // Cart for this user should be empty
    expect(GioHang::where('user_id', $user->id)->count())->toBe(0);
});
