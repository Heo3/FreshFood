<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DiaChiGiaoHang;
use App\Models\DonHang;
use App\Models\GioHang;
use App\Models\LichSuTichDiem;
use App\Models\MaGiamGia;
use App\Models\SanPham;
use App\Models\TonKho;
use App\Models\User;
use App\Services\VNPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'))->with('info', 'Vui lòng đăng nhập để tiến hành đặt mua hàng.');
        }

        $stores = CuaHang::where('trang_thai', 1)->get();
        $storeId = CuaHang::layIdCuaHangHienTai($request);
        $user = $request->user();

        $addresses = [];
        if ($user) {
            $addresses = DiaChiGiaoHang::where('user_id', $user->id)
                ->orderBy('la_mac_dinh', 'desc')
                ->latest()
                ->get();
        }

        $availableCoupons = MaGiamGia::hieuLuc()->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'ma_khuyen_mai' => $c->ma_khuyen_mai,
                'ten_chuong_trinh' => $c->ten_chuong_trinh,
                'loai_giam_gia' => $c->loai_giam_gia,
                'gia_tri_giam' => (float) $c->gia_tri_giam,
                'don_hang_toi_thieu' => (float) $c->don_hang_toi_thieu,
                'giam_toi_da' => $c->giam_toi_da ? (float) $c->giam_toi_da : null,
                'ngay_ket_thuc' => $c->ngay_ket_thuc?->format('d/m/Y'),
            ];
        });

        // Nhận mã giảm giá từ giỏ hàng qua query parameter hoặc session
        $couponCode = $request->query('coupon') ?? $request->session()->get('applied_coupon_code');
        $initialAppliedCoupon = null;
        if ($couponCode) {
            $couponModel = MaGiamGia::where('ma_khuyen_mai', trim(strtoupper($couponCode)))->first();
            if ($couponModel && $couponModel->isAvailable()) {
                $initialAppliedCoupon = [
                    'id' => $couponModel->id,
                    'code' => $couponModel->ma_khuyen_mai,
                    'program' => $couponModel->ten_chuong_trinh,
                    'type' => $couponModel->loai_giam_gia,
                    'value' => (float) $couponModel->gia_tri_giam,
                    'don_hang_toi_thieu' => (float) $couponModel->don_hang_toi_thieu,
                    'giam_toi_da' => $couponModel->giam_toi_da ? (float) $couponModel->giam_toi_da : null,
                ];
                $request->session()->put('applied_coupon_code', $couponModel->ma_khuyen_mai);
            }
        }

        return Inertia::render('client/Checkout', [
            'stores' => $stores,
            'selectedStoreId' => $storeId,
            'addresses' => $addresses,
            'availableCoupons' => $availableCoupons,
            'appliedCoupon' => $initialAppliedCoupon,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'loyalty_points' => $user->loyalty_points,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse|SymfonyResponse
    {
        $request->validate([
            'ten_nguoi_nhan' => ['required', 'string', 'max:100'],
            'so_dien_thoai_nhan' => ['required', 'string', 'max:15'],
            'dia_chi_giao' => ['required', 'string', 'max:255'],
            'cua_hang_id' => ['required', 'exists:cua_hang,id'],
            'phuong_thuc_thanh_toan' => ['required', 'in:cod,momo,vnpay,chuyen_khoan'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.san_pham_id' => ['required', 'exists:san_pham,id'],
            'items.*.so_luong' => ['required', 'numeric', 'min:0.1'],
            'ma_giam_gia_code' => ['nullable', 'string'],
            'use_points' => ['nullable', 'boolean'],
            'khung_gio_giao' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $cuaHangId = (int) $request->input('cua_hang_id');

        // Kiểm tra an toàn: Nếu khách chọn chi nhánh lệch vùng địa lý nghiêm trọng (ví dụ giao HN nhưng chọn HCM)
        $cuaHang = CuaHang::find($cuaHangId);
        if ($cuaHang && $cuaHang->laLechVungDiaLy($request->input('dia_chi_giao'))) {
            $suggestedStore = CuaHang::timChiNhanhPhuHopTheoDiaChi($request->input('dia_chi_giao'));
            $suggestMsg = $suggestedStore ? " Hệ thống đề xuất chọn: {$suggestedStore->ten_cua_hang}." : ' Vui lòng chọn chi nhánh gần khu vực nhận hàng!';

            return back()->withErrors([
                'cua_hang_id' => "Chi nhánh {$cuaHang->ten_cua_hang} không thể giao thực phẩm tươi sống đến địa chỉ của bạn do khoảng cách quá xa.{$suggestMsg}",
            ]);
        }

        // Bắt đầu transaction
        $donHang = DB::transaction(function () use ($request, $user, $cuaHangId) {
            $totalGoods = 0;
            $itemsData = [];

            foreach ($request->input('items') as $item) {
                $product = SanPham::with(['giaTheoCuaHang' => function ($q) use ($cuaHangId) {
                    $q->where('cua_hang_id', $cuaHangId);
                }])->findOrFail($item['san_pham_id']);

                // Kiểm tra tồn kho tại chi nhánh trước khi tạo đơn
                $tonKho = TonKho::where('cua_hang_id', $cuaHangId)
                    ->where('san_pham_id', $product->id)
                    ->first();

                $hasStoreInventory = TonKho::where('cua_hang_id', $cuaHangId)->exists();
                $hasProductInventory = TonKho::where('san_pham_id', $product->id)->exists();

                $isOutOfStock = false;
                if ($tonKho && $tonKho->so_luong_kha_dung < $item['so_luong']) {
                    $isOutOfStock = true;
                } elseif (! $tonKho && ($hasStoreInventory || $hasProductInventory)) {
                    $isOutOfStock = true;
                }

                if ($isOutOfStock) {
                    $remain = $tonKho ? $tonKho->so_luong_kha_dung : 0;
                    throw ValidationException::withMessages([
                        'items' => "Sản phẩm '{$product->ten_san_pham}' đã hết hoặc không đủ số lượng trong kho tại chi nhánh đã chọn (còn {$remain}).",
                    ]);
                }

                // Ưu tiên chọn giá ở table gia_san_pham_cua_hang theo chi nhánh được chọn
                $storeGia = $product->giaTheoCuaHang->first();
                $giaBan = $storeGia ? (float) $storeGia->gia_ban : (float) $product->gia_ban;
                $giaGiam = $storeGia
                    ? ($storeGia->gia_giam !== null ? (float) $storeGia->gia_giam : null)
                    : ($product->gia_giam !== null ? (float) $product->gia_giam : null);

                $price = $giaGiam ?? $giaBan;
                $lineTotal = $price * $item['so_luong'];
                $totalGoods += $lineTotal;

                $itemsData[] = [
                    'san_pham_id' => $product->id,
                    'ten_san_pham' => $product->ten_san_pham,
                    'hinh_anh_snapshot' => $product->hinh_anh_chinh,
                    'don_vi_tinh' => $product->don_vi_tinh,
                    'don_gia' => $price,
                    'gia_von' => round($price * 0.7, -2),
                    'so_luong' => $item['so_luong'],
                    'thanh_tien' => $lineTotal,
                ];
            }

            // Phí ship mặc định 25.000đ (miễn phí từ 300.000đ)
            $shippingFee = ($totalGoods >= 300000) ? 0 : 25000;

            // Xử lý mã giảm giá
            $discountAmount = 0;
            $couponId = null;
            if ($request->filled('ma_giam_gia_code')) {
                $coupon = MaGiamGia::where('ma_khuyen_mai', trim($request->input('ma_giam_gia_code')))->first();
                if ($coupon && $coupon->isAvailable()) {
                    $discountAmount = $coupon->tinhSoTienGiam($totalGoods);
                    $couponId = $coupon->id;
                    $coupon->increment('da_su_dung');
                }
            }

            // Xử lý tiêu điểm (1 điểm = 1.000đ)
            $pointsDiscount = 0;
            $pointsUsed = 0;
            if ($user && $request->boolean('use_points') && $user->loyalty_points > 0) {
                $maxPointsUsable = min((int) $user->loyalty_points, (int) floor(max(0, $totalGoods - $discountAmount) / 1000));
                if ($maxPointsUsable > 0) {
                    $pointsDiscount = $maxPointsUsable * 1000;
                    $pointsUsed = $maxPointsUsable;
                    $user->decrement('loyalty_points', $maxPointsUsable);
                }
            }

            $totalDiscount = $discountAmount + $pointsDiscount;
            $finalTotal = max(0, $totalGoods + $shippingFee - $totalDiscount);

            // Sinh mã đơn hàng dạng FF-NămThángNgày-SốNgẫuNhiên
            $orderCode = 'FF'.date('ymd').strtoupper(bin2hex(random_bytes(2)));

            $orderUserId = $user ? $user->id : (User::firstOrCreate(
                ['email' => 'khach@freshfood.vn'],
                [
                    'name' => $request->input('ten_nguoi_nhan', 'Khách Hàng'),
                    'password' => bcrypt('FreshFood@123'),
                    'role' => 'khach_hang',
                    'status' => 1,
                ]
            )->id);

            $order = DonHang::create([
                'ma_don_hang' => $orderCode,
                'user_id' => $orderUserId,
                'cua_hang_id' => $request->input('cua_hang_id'),
                'dia_chi_giao_hang_id' => $request->input('dia_chi_giao_hang_id'),
                'ma_giam_gia_id' => $couponId,
                'tong_tien_hang' => $totalGoods,
                'phi_giao_hang' => $shippingFee,
                'tien_giam_gia' => $totalDiscount,
                'tong_thanhtoan' => $finalTotal,
                'phuong_thuc_thanh_toan' => $request->input('phuong_thuc_thanh_toan'),
                'trang_thai_thanh_toan' => ($request->input('phuong_thuc_thanh_toan') === 'cod') ? 'chua_thanh_toan' : 'chua_thanh_toan',
                'trang_thai_don_hang' => 'cho_xac_nhan',
                'ten_nguoi_nhan' => $request->input('ten_nguoi_nhan'),
                'so_dien_thoai_nhan' => $request->input('so_dien_thoai_nhan'),
                'dia_chi_giao' => $request->input('dia_chi_giao'),
                'ghi_chu' => $request->input('ghi_chu'),
                'khung_gio_giao' => $request->input('khung_gio_giao'),
            ]);

            // Ghi nhật ký tiêu điểm
            if ($user && $pointsUsed > 0) {
                LichSuTichDiem::create([
                    'user_id' => $user->id,
                    'loai_giao_dich' => 'tieu_diem',
                    'so_diem_thay_doi' => -$pointsUsed,
                    'so_diem_sau_giao_dich' => (int) $user->loyalty_points,
                    'don_hang_id' => $order->id,
                    'ghi_chu' => "Tiêu {$pointsUsed} điểm thanh toán đơn hàng #{$order->ma_don_hang} (1 điểm = 1.000đ)",
                ]);
            }

            // Tích điểm: Mỗi 10.000đ tiền hàng thực thanh toán = 1 điểm
            $tienThucTra = max(0, $totalGoods - $discountAmount - $pointsDiscount);
            $pointsEarned = (int) floor($tienThucTra / 10000);
            if ($user && $pointsEarned > 0) {
                $user->increment('loyalty_points', $pointsEarned);

                LichSuTichDiem::create([
                    'user_id' => $user->id,
                    'loai_giao_dich' => 'tich_diem',
                    'so_diem_thay_doi' => $pointsEarned,
                    'so_diem_sau_giao_dich' => (int) $user->loyalty_points,
                    'don_hang_id' => $order->id,
                    'ghi_chu' => "Tích {$pointsEarned} điểm từ đơn hàng #{$order->ma_don_hang} (10.000đ = 1 điểm)",
                ]);
            }

            foreach ($itemsData as $itemRow) {
                $itemRow['don_hang_id'] = $order->id;
                ChiTietDonHang::create($itemRow);
            }

            // Xóa giỏ hàng trong CSDL (cả user_id và session_id)
            $sessionId = $request->session()->getId();
            if ($user) {
                GioHang::where('user_id', $user->id)->delete();
            }
            if ($sessionId) {
                GioHang::where('session_id', $sessionId)->delete();
            }

            // Đồng thời dọn dẹp các sản phẩm vừa đặt khỏi giỏ nếu là khách vãng lai
            if (! $user && ! empty($itemsData)) {
                $orderedProductIds = collect($itemsData)->pluck('san_pham_id')->all();
                GioHang::whereNull('user_id')
                    ->whereIn('san_pham_id', $orderedProductIds)
                    ->delete();
            }

            return $order;
        });

        // Xóa mã giảm giá đã dùng khỏi session
        $request->session()->forget('applied_coupon_code');

        if ($donHang->phuong_thuc_thanh_toan === 'vnpay') {
            $vnpayService = app(VNPayService::class);
            $paymentUrl = $vnpayService->createPaymentUrl($donHang, $request->ip());

            return Inertia::location($paymentUrl);
        }

        return redirect()->route('checkout.success', ['maDonHang' => $donHang->ma_don_hang]);
    }

    /**
     * Nhận phản hồi thanh toán trả về từ cổng VNPay Sandbox.
     */
    public function vnpayCallback(Request $request, VNPayService $vnpayService): RedirectResponse
    {
        $result = $vnpayService->verifyReturn($request->all());

        if (! $result['is_valid'] || empty($result['order_code'])) {
            return redirect()->route('home')->with('error', $result['message']);
        }

        $order = DonHang::where('ma_don_hang', $result['order_code'])->first();
        if (! $order) {
            return redirect()->route('home')->with('error', 'Không tìm thấy thông tin đơn hàng tương ứng.');
        }

        if ($result['success']) {
            $order->update([
                'trang_thai_thanh_toan' => 'da_thanh_toan',
            ]);

            return redirect()->route('checkout.success', ['maDonHang' => $order->ma_don_hang])
                ->with('success', 'Thanh toán đơn hàng qua VNPay Sandbox thành công!');
        }

        return redirect()->route('checkout.success', ['maDonHang' => $order->ma_don_hang])
            ->with('error', 'Thanh toán VNPay chưa hoàn tất: '.$result['message']);
    }

    /**
     * Tạo liên kết để thanh toán lại bằng VNPay Sandbox.
     */
    public function retryVNPayPayment(string $maDonHang, Request $request, VNPayService $vnpayService): SymfonyResponse|RedirectResponse
    {
        $order = DonHang::where('ma_don_hang', $maDonHang)->firstOrFail();

        if ($order->trang_thai_thanh_toan === 'da_thanh_toan') {
            return redirect()->route('checkout.success', ['maDonHang' => $order->ma_don_hang])
                ->with('info', 'Đơn hàng này đã được thanh toán thành công.');
        }

        $paymentUrl = $vnpayService->createPaymentUrl($order, $request->ip());

        return Inertia::location($paymentUrl);
    }

    public function success(string $maDonHang): Response
    {
        $order = DonHang::where('ma_don_hang', $maDonHang)
            ->with(['chiTiets.sanPham', 'cuaHang', 'maGiamGia', 'nguoiGiaoHang'])
            ->firstOrFail();

        return Inertia::render('client/OrderSuccess', [
            'order' => $order,
        ]);
    }

    public function tracking(Request $request): Response
    {
        $order = null;
        if ($request->filled('code')) {
            $order = DonHang::where('ma_don_hang', trim($request->input('code')))
                ->when($request->filled('phone'), function ($q) use ($request) {
                    $q->where('so_dien_thoai_nhan', trim($request->input('phone')));
                })
                ->with(['chiTiets', 'cuaHang', 'nguoiGiaoHang'])
                ->first();
        }

        return Inertia::render('client/OrderTracking', [
            'searchedCode' => $request->input('code', ''),
            'searchedPhone' => $request->input('phone', ''),
            'order' => $order,
        ]);
    }
}
