<?php

use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\PhienChat;
use App\Models\SanPham;
use App\Models\TinNhanChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('client can initialize a chat session and receive ai welcome greeting', function () {
    $response = $this->postJson(route('chat.session'), [
        'session_id' => 'test-session-uuid-001',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'session' => ['id', 'session_id', 'loai_chat'],
        'messages' => [
            ['id', 'noi_dung', 'nguoi_gui_type'],
        ],
    ]);

    $this->assertDatabaseHas('phien_chat', [
        'session_id' => 'test-session-uuid-001',
        'loai_chat' => 'ai',
    ]);

    $this->assertDatabaseHas('tin_nhan_chat', [
        'nguoi_gui_type' => 'ai',
    ]);
});

test('user can send message to ai and receive suggestions with products', function () {
    $category = DanhMuc::create(['ten_danh_muc' => 'Rau củ hữu cơ', 'duong_dan_seo' => 'rau-cu-huu-co']);
    $product = SanPham::create([
        'ten_san_pham' => 'Rau Cải Ngọt VietGAP',
        'duong_dan_seo' => 'rau-cai-ngot-vietgap',
        'danh_muc_id' => $category->id,
        'gia_ban' => 22000,
        'don_vi_tinh' => 'Bó',
        'la_tuoi_song' => true,
        'trang_thai' => 1,
    ]);

    $session = PhienChat::create([
        'session_id' => 'test-session-uuid-002',
        'loai_chat' => 'ai',
    ]);

    $response = $this->postJson(route('chat.send'), [
        'session_id' => 'test-session-uuid-002',
        'noi_dung' => 'Hôm nay ăn gì, gợi ý món rau cải giúp tôi với',
        'loai_chat' => 'ai',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'user_message' => ['id', 'noi_dung'],
        'replies' => [
            ['id', 'noi_dung', 'nguoi_gui_type'],
        ],
    ]);

    $reply = $response->json('replies.0');
    expect($reply['nguoi_gui_type'])->toBe('ai');
    expect($reply['noi_dung'])->toContain('Gợi ý');
});

test('ai automatically looks up order tracking when order code is sent', function () {
    $store = CuaHang::create([
        'ten_cua_hang' => 'Fresh Food Quận 1',
        'so_dien_thoai' => '0901234567',
        'dia_chi' => '123 Lê Lợi',
        'trang_thai' => 1,
    ]);

    $user = User::factory()->create();

    $order = DonHang::create([
        'ma_don_hang' => 'FF2611009988',
        'user_id' => $user->id,
        'cua_hang_id' => $store->id,
        'tong_tien_hang' => 120000,
        'phi_giao_hang' => 15000,
        'tien_giam_gia' => 0,
        'tong_thanhtoan' => 135000,
        'phuong_thuc_thanh_toan' => 'cod',
        'trang_thai_thanh_toan' => 'chua_thanh_toan',
        'trang_thai_don_hang' => 'dang_giao',
        'ten_nguoi_nhan' => 'Nguyen Van TraCuu',
        'so_dien_thoai_nhan' => '0987654321',
        'dia_chi_giao' => '456 Hai Bà Trưng, Q1',
    ]);

    $session = PhienChat::create([
        'session_id' => 'test-session-uuid-003',
        'loai_chat' => 'ai',
    ]);

    $response = $this->postJson(route('chat.send'), [
        'session_id' => 'test-session-uuid-003',
        'noi_dung' => 'Kiểm tra giúp đơn hàng FF2611009988 của tôi',
        'loai_chat' => 'ai',
    ]);

    $response->assertStatus(200);
    $reply = $response->json('replies.0');
    expect($reply['noi_dung'])->toContain('FF2611009988');
    expect($reply['noi_dung'])->toContain('Đang giao hỏa tốc');
    expect($reply['du_lieu_dinh_kem']['type'])->toBe('order');
});

test('user can switch to staff mode for complaints and attach order code', function () {
    $session = PhienChat::create([
        'session_id' => 'test-session-uuid-004',
        'loai_chat' => 'ai',
    ]);

    $response = $this->postJson(route('chat.switch-mode'), [
        'session_id' => 'test-session-uuid-004',
        'loai_chat' => 'nhan_vien',
        'chu_de' => 'khieu_nai',
        'ma_don_hang' => 'FF26118888',
        'ten_khach_hang' => 'Le Van KhieuNai',
        'so_dien_thoai' => '0911223344',
    ]);

    $response->assertStatus(200);

    $session->refresh();
    expect($session->loai_chat)->toBe('nhan_vien');
    expect($session->chu_de)->toBe('khieu_nai');
    expect($session->ma_don_hang)->toBe('FF26118888');
    expect($session->trang_thai)->toBe('cho_xu_ly');
});

test('admin can view support tickets and reply to customer message', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 1]);

    $session = PhienChat::create([
        'session_id' => 'test-session-uuid-005',
        'loai_chat' => 'nhan_vien',
        'chu_de' => 'khieu_nai',
        'ten_khach_hang' => 'Tran Thi Khach',
        'trang_thai' => 'cho_xu_ly',
    ]);

    TinNhanChat::create([
        'phien_chat_id' => $session->id,
        'nguoi_gui_type' => 'khach_hang',
        'noi_dung' => 'Sản phẩm dưa leo giao đến bị dập, xin hỗ trợ đổi',
    ]);

    // Admin xem danh sách
    $indexRes = $this->actingAs($admin)->get(route('admin.support.index'));
    $indexRes->assertStatus(200);

    // Admin gửi tin nhắn trả lời
    $replyRes = $this->actingAs($admin)->post(route('admin.support.reply', ['session' => $session->id]), [
        'noi_dung' => 'Dạ Fresh Food xin lỗi bạn, bên em gửi đơn đổi mới ngay cho bạn trong 30 phút ạ.',
    ]);

    $replyRes->assertRedirect();

    $this->assertDatabaseHas('tin_nhan_chat', [
        'phien_chat_id' => $session->id,
        'nguoi_gui_type' => 'nhan_vien',
        'nguoi_gui_id' => $admin->id,
    ]);

    $session->refresh();
    expect($session->trang_thai)->toBe('dang_xu_ly');
    expect($session->nhan_vien_id)->toBe($admin->id);
});

test('admin can update support ticket status to resolved', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 1]);

    $session = PhienChat::create([
        'session_id' => 'test-session-uuid-006',
        'loai_chat' => 'nhan_vien',
        'chu_de' => 'khieu_nai',
        'trang_thai' => 'dang_xu_ly',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.support.update-status', ['session' => $session->id]), [
        'trang_thai' => 'da_dong',
    ]);

    $response->assertRedirect();

    $session->refresh();
    expect($session->trang_thai)->toBe('da_dong');
});

test('ai accurately answers beef inquiries with focused products and not generic canned text', function () {
    $category = DanhMuc::create(['ten_danh_muc' => 'Thịt tươi sạch', 'duong_dan_seo' => 'thit-tuoi-sach']);

    SanPham::create([
        'ten_san_pham' => 'Thăn Bò Úc Black Angus Cắt Dày Steak',
        'duong_dan_seo' => 'than-bo-uc-black-angus-steak',
        'danh_muc_id' => $category->id,
        'gia_ban' => 360000,
        'don_vi_tinh' => 'Khay 500g',
        'trang_thai' => 1,
    ]);

    SanPham::create([
        'ten_san_pham' => 'Ba Chỉ Bò Mỹ Cắt Cuộn Nhúng Lẩu / Nướng',
        'duong_dan_seo' => 'ba-chi-bo-my-cat-cuon',
        'danh_muc_id' => $category->id,
        'gia_ban' => 220000,
        'don_vi_tinh' => 'Khay 500g',
        'trang_thai' => 1,
    ]);

    $session = PhienChat::create([
        'session_id' => 'test-session-uuid-007',
        'loai_chat' => 'ai',
    ]);

    $response = $this->postJson(route('chat.send'), [
        'session_id' => 'test-session-uuid-007',
        'noi_dung' => 'Tôi muốn mua thịt bò tươi để nấu ăn',
        'loai_chat' => 'ai',
    ]);

    $response->assertStatus(200);
    $reply = $response->json('replies.0');

    expect($reply['noi_dung'])->toContain('Thăn Bò Úc Black Angus Cắt Dày Steak');
    expect($reply['noi_dung'])->toContain('Ba Chỉ Bò Mỹ Cắt Cuộn');
    expect($reply['noi_dung'])->toContain('Bò Bít Tết (Beefsteak)');
    expect($reply['noi_dung'])->not->toContain('Dạ em tìm thấy các sản phẩm tươi ngon đúng với nhu cầu');
    expect($reply['du_lieu_dinh_kem']['type'])->toBe('products');
    expect(count($reply['du_lieu_dinh_kem']['items']))->toBeGreaterThanOrEqual(2);
});

test('ai provides recipe ingredients separating available fresh food items from missing pantry items for beef steak', function () {
    $category = DanhMuc::create(['ten_danh_muc' => 'Thực phẩm tổng hợp', 'duong_dan_seo' => 'thuc-pham-tong-hop']);

    SanPham::create([
        'ten_san_pham' => 'Thăn Bò Úc Black Angus Cắt Dày Steak',
        'duong_dan_seo' => 'than-bo-uc-black-angus-steak',
        'danh_muc_id' => $category->id,
        'gia_ban' => 360000,
        'don_vi_tinh' => 'Khay 500g',
        'trang_thai' => 1,
    ]);

    SanPham::create([
        'ten_san_pham' => 'Bơ Nhạt Tự Nhiên Anchor New Zealand',
        'duong_dan_seo' => 'bo-nhat-tu-nhien-anchor',
        'danh_muc_id' => $category->id,
        'gia_ban' => 85000,
        'don_vi_tinh' => 'Khối 227g',
        'trang_thai' => 1,
    ]);

    SanPham::create([
        'ten_san_pham' => 'Tỏi Lý Sơn',
        'duong_dan_seo' => 'toi-ly-son',
        'danh_muc_id' => $category->id,
        'gia_ban' => 50000,
        'don_vi_tinh' => 'Túi 300g',
        'trang_thai' => 1,
    ]);

    SanPham::create([
        'ten_san_pham' => 'Dầu Oliu Extra Virgin Địa Trung Hải',
        'duong_dan_seo' => 'dau-oliu-extra-virgin',
        'danh_muc_id' => $category->id,
        'gia_ban' => 165000,
        'don_vi_tinh' => 'Chai 500ml',
        'trang_thai' => 1,
    ]);

    $session = PhienChat::create([
        'session_id' => 'test-session-uuid-008',
        'loai_chat' => 'ai',
    ]);

    $response = $this->postJson(route('chat.send'), [
        'session_id' => 'test-session-uuid-008',
        'noi_dung' => 'Nay tôi muốn làm bò bít tết thì cần những nguyên liệu gì',
        'loai_chat' => 'ai',
    ]);

    $response->assertStatus(200);
    $reply = $response->json('replies.0');

    expect($reply['noi_dung'])->toContain('Bò Bít Tết Thăn Úc Sốt Bơ Tỏi');
    expect($reply['noi_dung'])->toContain('Nguyên liệu CÓ BÁN tại siêu thị Fresh Food');
    expect($reply['noi_dung'])->toContain('Gia vị & nguyên liệu bạn cần chuẩn bị thêm ở nhà');
    expect($reply['noi_dung'])->toContain('Muối biển');
    expect($reply['noi_dung'])->toContain('hương thảo (Rosemary)');
    expect($reply['du_lieu_dinh_kem']['type'])->toBe('products');
    expect($reply['du_lieu_dinh_kem']['missing_ingredients'])->toBeArray();
    expect($reply['du_lieu_dinh_kem']['missing_ingredients'])->toContain('Hạt tiêu đen xay tươi');
    expect(count($reply['du_lieu_dinh_kem']['items']))->toBe(4);
});
