<?php

use App\Models\User;

test('guest is redirected to login when accessing protected areas', function () {
    $this->get('/admin')->assertRedirect(route('login'));
    $this->get('/store/pos')->assertRedirect(route('login'));
    $this->get('/tai-khoan')->assertRedirect(route('login'));
});

test('customer can access customer portal but cannot access admin or store portal', function () {
    $customer = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 1,
    ]);

    // Được phép vào phân hệ khách hàng
    $this->actingAs($customer)->get('/tai-khoan')->assertSuccessful();

    // Bị cấm vào phân hệ Admin và phân hệ Kho/POS
    $this->actingAs($customer)->get('/admin')->assertForbidden();
    $this->actingAs($customer)->get('/store/pos')->assertForbidden();
});

test('staff can access store operations and pos but cannot access admin panel', function () {
    $staff = User::factory()->create([
        'role' => 'nhan_vien',
        'status' => 1,
    ]);

    // Được phép vào phân hệ vận hành chi nhánh
    $this->actingAs($staff)->get('/store/pos')->assertSuccessful();
    $this->actingAs($staff)->get('/store/ton-kho')->assertSuccessful();

    // Bị cấm vào phân hệ Admin
    $this->actingAs($staff)->get('/admin')->assertForbidden();
});

test('store manager can access store operations and admin reports but not root admin settings', function () {
    $manager = User::factory()->create([
        'role' => 'quan_ly',
        'status' => 1,
    ]);

    // Quản lý được vào dashboard admin và phân hệ chi nhánh
    $this->actingAs($manager)->get('/admin')->assertSuccessful();
    $this->actingAs($manager)->get('/admin/bao-cao')->assertSuccessful();
    $this->actingAs($manager)->get('/store/pos')->assertSuccessful();

    // Nhưng không được vào phần cấu hình chuỗi cửa hàng của Admin tối cao
    $this->actingAs($manager)->get('/admin/cua-hang')->assertForbidden();
    $this->actingAs($manager)->get('/admin/nhan-su')->assertForbidden();
});

test('admin can access all areas including system configurations', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 1,
    ]);

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/cua-hang')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/nhan-su')->assertSuccessful();
    $this->actingAs($admin)->get('/store/pos')->assertSuccessful();
});

test('inactive user is logged out and redirected to login', function () {
    $lockedUser = User::factory()->create([
        'role' => 'khach_hang',
        'status' => 0,
    ]);

    $response = $this->actingAs($lockedUser)->get('/tai-khoan');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('store portal root redirects to pos', function () {
    $staff = User::factory()->create(['role' => 'nhan_vien', 'status' => 1]);
    $this->actingAs($staff)->get('/store')->assertRedirect(route('store.pos.index'));
});
