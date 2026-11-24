<?php

use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\User;
use App\Services\VNPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('vnpay service generates valid sandbox url with sha512 secure hash', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Fresh Food Quận 1',
        'so_dien_thoai' => '0901112233',
        'dia_chi' => '123 Lê Lợi, Q1, TP.HCM',
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create();

    $order = DonHang::create([
        'ma_don_hang' => 'ORD-TEST-VNPAY-01',
        'user_id' => $user->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 150000,
        'phi_giao_hang' => 20000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 170000,
        'phuong_thuc_thanh_toan' => 'vnpay',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Nguyen Van Test',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '123 Lê Lợi, Q1, TP.HCM',
    ]);

    $service = new VNPayService(
        tmnCode: '2QXUI4B4',
        hashSecret: 'secret',
        url: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        returnUrl: 'http://localhost/thanh-toan/vnpay-callback',
    );

    $url = $service->createPaymentUrl($order, '127.0.0.1');

    expect($url)->toContain('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    expect($url)->toContain('vnp_TmnCode=2QXUI4B4');
    expect($url)->toContain('vnp_Amount=17000000'); // 170,000 * 100
    expect($url)->toContain('vnp_TxnRef=ORD-TEST-VNPAY-01');
    expect($url)->toContain('vnp_SecureHash=');
});

test('vnpay service verifies genuine and tampered callback signatures', function () {
    $service = new VNPayService(
        tmnCode: '2QXUI4B4',
        hashSecret: 'secret',
        url: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        returnUrl: 'http://localhost/thanh-toan/vnpay-callback',
    );

    $params = [
        'vnp_Amount' => '17000000',
        'vnp_BankCode' => 'NCB',
        'vnp_CardType' => 'ATM',
        'vnp_OrderInfo' => 'Thanh toan don hang ORD-TEST-VNPAY-01',
        'vnp_PayDate' => '20261116170000',
        'vnp_ResponseCode' => '00',
        'vnp_TmnCode' => '2QXUI4B4',
        'vnp_TransactionNo' => '14000123',
        'vnp_TxnRef' => 'ORD-TEST-VNPAY-01',
    ];

    // Tạo hash chuẩn
    ksort($params);
    $hashData = '';
    $i = 0;
    foreach ($params as $k => $v) {
        $hashData .= ($i === 1 ? '&' : '').urlencode($k).'='.urlencode((string) $v);
        $i = 1;
    }
    $validHash = hash_hmac('sha512', $hashData, 'secret');

    // 1. Chữ ký hợp lệ
    $genuineParams = $params;
    $genuineParams['vnp_SecureHash'] = $validHash;
    $verifyResult = $service->verifyReturn($genuineParams);
    expect($verifyResult['is_valid'])->toBeTrue();
    expect($verifyResult['success'])->toBeTrue();
    expect($verifyResult['order_code'])->toBe('ORD-TEST-VNPAY-01');

    // 2. Chữ ký giả mạo / bị thay đổi số tiền
    $tamperedParams = $genuineParams;
    $tamperedParams['vnp_Amount'] = '99999999';
    $tamperedResult = $service->verifyReturn($tamperedParams);
    expect($tamperedResult['is_valid'])->toBeFalse();
    expect($tamperedResult['success'])->toBeFalse();
});

test('checkout with vnpay creates order and redirects to vnpay sandbox gateway', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Fresh Food Quận 1',
        'so_dien_thoai' => '0901112233',
        'dia_chi' => '123 Lê Lợi, Q1, TP.HCM',
        'trang_thai' => 1,
    ]);

    $category = DanhMuc::create(['ten_danh_muc' => 'Thực phẩm', 'duong_dan_seo' => 'thuc-pham']);
    $product = SanPham::create([
        'ten_san_pham' => 'Rau Cải Xanh VietGAP',
        'duong_dan_seo' => 'rau-cai-xanh-vietgap',
        'danh_muc_id' => $category->id,
        'gia_ban' => 25000,
        'don_vi_tinh' => 'Bó',
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.store'), [
        'ten_nguoi_nhan' => 'Nguyen Van A',
        'so_dien_thoai_nhan' => '0912345678',
        'dia_chi_giao' => '123 Hai Bà Trưng, Phường Bến Nghé, Quận 1, TP.HCM',
        'cua_hang_id' => $store->id,
        'phuong_thuc_thanh_toan' => 'vnpay',
        'items' => [
            [
                'san_pham_id' => $product->id,
                'so_luong' => 2,
            ],
        ],
    ], [
        'X-Inertia' => 'true',
    ]);

    // Inertia::location returns 409 or redirect
    $response->assertStatus(409);
    $location = $response->headers->get('X-Inertia-Location');
    expect($location)->toContain('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    expect($location)->toContain('vnp_TmnCode='.config('services.vnpay.tmn_code'));

    $this->assertDatabaseHas('don_hang', [
        'phuong_thuc_thanh_toan' => 'vnpay',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'ten_nguoi_nhan' => 'Nguyen Van A',
    ]);
});

test('vnpay callback updates order payment status to paid upon successful transaction', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Fresh Food Quận 1',
        'so_dien_thoai' => '0901112233',
        'dia_chi' => '123 Lê Lợi, Q1, TP.HCM',
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create();

    $order = DonHang::create([
        'ma_don_hang' => 'ORD-VNPAY-SUCCESS',
        'user_id' => $user->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 50000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 65000,
        'phuong_thuc_thanh_toan' => 'vnpay',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Tran Thi B',
        'so_dien_thoai_nhan' => '0988776655',
        'dia_chi_giao' => '456 Lê Duẩn, Q1, TP.HCM',
    ]);

    $params = [
        'vnp_Amount' => '6500000',
        'vnp_BankCode' => 'NCB',
        'vnp_CardType' => 'ATM',
        'vnp_OrderInfo' => 'Thanh toan don hang ORD-VNPAY-SUCCESS',
        'vnp_PayDate' => '20261116170000',
        'vnp_ResponseCode' => '00',
        'vnp_TmnCode' => '2QXUI4B4',
        'vnp_TransactionNo' => '99887766',
        'vnp_TxnRef' => 'ORD-VNPAY-SUCCESS',
    ];

    ksort($params);
    $hashData = '';
    $i = 0;
    foreach ($params as $k => $v) {
        $hashData .= ($i === 1 ? '&' : '').urlencode($k).'='.urlencode((string) $v);
        $i = 1;
    }
    $hashSecret = config('services.vnpay.hash_secret');
    $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $hashSecret);

    $response = $this->get(route('checkout.vnpay-callback', $params));
    $response->assertRedirect(route('checkout.success', ['maDonHang' => 'ORD-VNPAY-SUCCESS']));

    $order->refresh();
    expect($order->trang_thai_thanh_toan)->toBe('da_thanh_toan');
});

test('vnpay callback handles user cancellation cleanly', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Fresh Food Quận 1',
        'so_dien_thoai' => '0901112233',
        'dia_chi' => '123 Lê Lợi, Q1, TP.HCM',
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create();

    $order = DonHang::create([
        'ma_don_hang' => 'ORD-VNPAY-CANCEL',
        'user_id' => $user->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 50000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 65000,
        'phuong_thuc_thanh_toan' => 'vnpay',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'cho_xac_nhan',
        'ten_nguoi_nhan' => 'Tran Thi B',
        'so_dien_thoai_nhan' => '0988776655',
        'dia_chi_giao' => '456 Lê Duẩn, Q1, TP.HCM',
    ]);

    $params = [
        'vnp_Amount' => '6500000',
        'vnp_OrderInfo' => 'Thanh toan don hang ORD-VNPAY-CANCEL',
        'vnp_ResponseCode' => '24', // Khách hủy giao dịch
        'vnp_TmnCode' => '2QXUI4B4',
        'vnp_TxnRef' => 'ORD-VNPAY-CANCEL',
    ];

    ksort($params);
    $hashData = '';
    $i = 0;
    foreach ($params as $k => $v) {
        $hashData .= ($i === 1 ? '&' : '').urlencode($k).'='.urlencode((string) $v);
        $i = 1;
    }
    $hashSecret = config('services.vnpay.hash_secret');
    $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $hashSecret);

    $response = $this->get(route('checkout.vnpay-callback', $params));
    $response->assertRedirect(route('checkout.success', ['maDonHang' => 'ORD-VNPAY-CANCEL']));
    $response->assertSessionHas('error');

    $order->refresh();
    expect($order->trang_thai_thanh_toan)->toBe('chua_thanh_toan');
});
