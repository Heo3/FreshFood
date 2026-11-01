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
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(Request $request): Response
    {
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

        return Inertia::render('client/Checkout', [
            'stores' => $stores,
            'selectedStoreId' => $storeId,
            'addresses' => $addresses,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'loyalty_points' => $user->loyalty_points,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
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
        ]);

        $user = $request->user();
        $cuaHangId = (int) $request->input('cua_hang_id');

        // Bắt đầu transaction
        $donHang = DB::transaction(function () use ($request, $user, $cuaHangId) {
            $totalGoods = 0;
            $itemsData = [];

            foreach ($request->input('items') as $item) {
                $product = SanPham::with(['giaTheoCuaHang' => function ($q) use ($cuaHangId) {
                    $q->where('cua_hang_id', $cuaHangId);
                }])->findOrFail($item['san_pham_id']);

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

            // Xử lý tiêu điểm (1 điểm = 1.000đ, tối đa 50% tiền hàng)
            $pointsDiscount = 0;
            if ($user && $request->boolean('use_points') && $user->loyalty_points > 0) {
                $maxPointsUsable = min($user->loyalty_points, (int) floor(($totalGoods * 0.5) / 1000));
                $pointsDiscount = $maxPointsUsable * 1000;
                $user->decrement('loyalty_points', $maxPointsUsable);

                LichSuTichDiem::create([
                    'user_id' => $user->id,
                    'loai_giao_dich' => 'tieu_diem',
                    'so_diem_thay_doi' => -$maxPointsUsable,
                    'so_diem_sau_giao_dich' => $user->loyalty_points,
                    'ghi_chu' => 'Tiêu điểm thanh toán đơn hàng',
                ]);
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
            ]);

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

        return redirect()->route('checkout.success', ['maDonHang' => $donHang->ma_don_hang]);
    }

    public function success(string $maDonHang): Response
    {
        $order = DonHang::where('ma_don_hang', $maDonHang)
            ->with(['chiTiets.sanPham', 'cuaHang', 'maGiamGia'])
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
                ->with(['chiTiets', 'cuaHang'])
                ->first();
        }

        return Inertia::render('client/OrderTracking', [
            'searchedCode' => $request->input('code', ''),
            'searchedPhone' => $request->input('phone', ''),
            'order' => $order,
        ]);
    }
}
