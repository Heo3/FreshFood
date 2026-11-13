<?php

namespace App\Http\Controllers\Store;

use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\GiaSanPhamCuaHang;
use App\Models\LichSuTichDiem;
use App\Models\LoHang;
use App\Models\SanPham;
use App\Models\TonKho;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StoreOpsController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    protected function layStoreId(Request $request): int
    {
        $user = $request->user();
        if ($user && ! $user->isAdmin() && $user->store_id) {
            return (int) $user->store_id;
        }

        if ($request->filled('store_id') && $user?->isAdmin()) {
            return (int) $request->input('store_id');
        }

        return (int) ($user?->store_id ?? CuaHang::layIdCuaHangHienTai($request));
    }

    /**
     * Màn hình bán hàng tại quầy POS.
     */
    public function pos(Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        // Danh sách sản phẩm đang kinh doanh có tồn kho tại chi nhánh
        $products = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->with(['danhMuc'])
            ->get()
            ->map(function ($p) use ($storeId) {
                $ton = TonKho::where('cua_hang_id', $storeId)
                    ->where('san_pham_id', $p->id)
                    ->value('so_luong_ton') ?? 0;

                return [
                    'id' => $p->id,
                    'ten_san_pham' => $p->ten_san_pham,
                    'ma_va_vach' => $p->ma_va_vach,
                    'duong_dan_seo' => $p->duong_dan_seo,
                    'hinh_anh_chinh' => $p->hinh_anh_chinh,
                    'don_vi_tinh' => $p->don_vi_tinh,
                    'danh_muc' => $p->danhMuc?->ten_danh_muc,
                    'gia_ban' => (float) $p->gia_ban,
                    'gia_giam' => $p->gia_giam !== null ? (float) $p->gia_giam : null,
                    'ton_kho' => (float) $ton,
                ];
            });

        return Inertia::render('store/pos/Index', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'products' => $products,
        ]);
    }

    /**
     * Tra cứu thông tin khách hàng và số điểm tích lũy theo số điện thoại tại quầy POS.
     */
    public function findCustomer(Request $request): JsonResponse
    {
        $phone = trim($request->input('phone', ''));
        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp số điện thoại.',
            ], 422);
        }

        $customer = User::where('phone', $phone)->first();

        if ($customer) {
            $points = (int) $customer->loyalty_points;

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'loyalty_points' => $points,
                    'points_value' => $points * 1000,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Khách hàng chưa có trong hệ thống thành viên. Khi thanh toán sẽ tự động tạo tài khoản tích điểm.',
        ]);
    }

    /**
     * Xử lý thanh toán đơn hàng tại quầy POS và xuất kho FEFO ngay lập tức, hỗ trợ tiêu điểm & tích điểm (10.000đ = 1 điểm, 1 điểm = 1.000đ).
     */
    public function checkoutPos(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.san_pham_id' => ['required', 'exists:san_pham,id'],
            'items.*.so_luong' => ['required', 'numeric', 'min:0.1'],
            'items.*.don_gia' => ['required', 'numeric', 'min:0'],
            'phuong_thuc_thanh_toan' => ['required', 'string', 'in:tien_mat,chuyen_khoan,vnpay'],
            'tien_khach_dua' => ['nullable', 'numeric'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'use_points' => ['nullable', 'boolean'],
            'points_to_use' => ['nullable', 'integer', 'min:0'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
        ]);

        $storeId = $this->layStoreId($request);
        $user = $request->user();

        try {
            $result = DB::transaction(function () use ($validated, $storeId, $user, $request) {
                $tongTien = 0.0;
                foreach ($validated['items'] as $item) {
                    $tongTien += ($item['so_luong'] * $item['don_gia']);
                }

                $customer = null;
                $phone = ! empty($validated['customer_phone']) ? trim($validated['customer_phone']) : null;
                if ($phone) {
                    $customer = User::where('phone', $phone)->first();
                    if (! $customer) {
                        $customer = User::create([
                            'name' => ! empty($validated['customer_name']) ? trim($validated['customer_name']) : 'Khách hàng '.$phone,
                            'phone' => $phone,
                            'email' => 'pos_'.$phone.'_'.time().'@freshfood.vn',
                            'password' => bcrypt('FreshFood@123'),
                            'role' => 'khach_hang',
                            'status' => 1,
                            'loyalty_points' => 0,
                        ]);
                    }
                }

                // Xử lý tiêu điểm (1 điểm = 1.000đ)
                $pointsDiscount = 0;
                $pointsUsed = 0;
                if ($customer && $request->boolean('use_points') && $customer->loyalty_points > 0) {
                    $maxPossiblePoints = (int) floor($tongTien / 1000);
                    $requestedPoints = (int) ($request->input('points_to_use') ?? $customer->loyalty_points);
                    $pointsUsed = min((int) $customer->loyalty_points, $maxPossiblePoints, $requestedPoints);

                    if ($pointsUsed > 0) {
                        $pointsDiscount = $pointsUsed * 1000;
                        $customer->decrement('loyalty_points', $pointsUsed);
                    }
                }

                $finalTotal = max(0, $tongTien - $pointsDiscount);

                // Tích điểm: 10.000 VNĐ thực trả = 1 điểm
                $newPointsEarned = 0;
                if ($customer) {
                    $newPointsEarned = (int) floor($finalTotal / 10000);
                    if ($newPointsEarned > 0) {
                        $customer->increment('loyalty_points', $newPointsEarned);
                    }
                }

                $maDon = 'POS-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

                $donHang = DonHang::create([
                    'ma_don_hang' => $maDon,
                    'user_id' => $customer ? $customer->id : $user->id,
                    'cua_hang_id' => $storeId,
                    'tong_tien_hang' => $tongTien,
                    'phi_giao_hang' => 0,
                    'tien_giam_gia' => $pointsDiscount,
                    'tong_thanhtoan' => $finalTotal,
                    'phuong_thuc_thanh_toan' => in_array($validated['phuong_thuc_thanh_toan'], ['vnpay', 'chuyen_khoan'])
                        ? ($validated['phuong_thuc_thanh_toan'] === 'vnpay' ? 'vnpay' : 'chuyen_khoan')
                        : 'cod',
                    'trang_thai_thanh_toan' => 'da_thanh_toan',
                    'trang_thai_don_hang' => 'da_giao',
                    'ten_nguoi_nhan' => $customer ? $customer->name : 'Khách mua tại quầy POS',
                    'so_dien_thoai_nhan' => $customer ? $customer->phone : '0000000000',
                    'dia_chi_giao' => 'Mua trực tiếp tại chi nhánh #'.$storeId,
                    'ghi_chu' => $validated['ghi_chu'] ?? 'Đơn hàng bán lẻ POS',
                ]);

                foreach ($validated['items'] as $item) {
                    $prod = SanPham::find($item['san_pham_id']);
                    ChiTietDonHang::create([
                        'don_hang_id' => $donHang->id,
                        'san_pham_id' => $item['san_pham_id'],
                        'ten_san_pham' => $prod?->ten_san_pham ?? 'Sản phẩm',
                        'so_luong' => $item['so_luong'],
                        'don_gia' => $item['don_gia'],
                        'thanh_tien' => $item['so_luong'] * $item['don_gia'],
                    ]);
                }

                // Ghi nhật ký tiêu điểm
                if ($customer && $pointsUsed > 0) {
                    LichSuTichDiem::create([
                        'user_id' => $customer->id,
                        'loai_giao_dich' => 'tieu_diem',
                        'so_diem_thay_doi' => -$pointsUsed,
                        'so_diem_sau_giao_dich' => (int) $customer->loyalty_points - $newPointsEarned,
                        'don_hang_id' => $donHang->id,
                        'ghi_chu' => "Tiêu {$pointsUsed} điểm trừ vào hóa đơn POS #{$donHang->ma_don_hang} (1 điểm = 1.000đ)",
                    ]);
                }

                // Ghi nhật ký tích điểm mới (10.000đ = 1 điểm)
                if ($customer && $newPointsEarned > 0) {
                    LichSuTichDiem::create([
                        'user_id' => $customer->id,
                        'loai_giao_dich' => 'tich_diem',
                        'so_diem_thay_doi' => $newPointsEarned,
                        'so_diem_sau_giao_dich' => (int) $customer->loyalty_points,
                        'don_hang_id' => $donHang->id,
                        'ghi_chu' => "Tích {$newPointsEarned} điểm từ đơn POS #{$donHang->ma_don_hang} (10.000đ = 1 điểm)",
                    ]);
                }

                // Thực hiện xuất kho FEFO tự động
                $this->inventoryService->xuatKhoDonHang($donHang, $user->id);

                return [
                    'don_hang' => $donHang->load('chiTiets'),
                    'customer' => $customer ? [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'points_used' => $pointsUsed,
                        'discount_from_points' => $pointsDiscount,
                        'points_earned' => $newPointsEarned,
                        'current_points' => (int) $customer->loyalty_points,
                    ] : null,
                ];
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán và xuất kho FEFO thành công!',
                    'don_hang' => $result['don_hang'],
                    'customer' => $result['customer'],
                ]);
            }

            return back()->with('success', "Thanh toán thành công đơn POS #{$result['don_hang']->ma_don_hang}!");
        } catch (InsufficientInventoryException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi xử lý thanh toán: '.$e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Màn hình quản lý tồn kho và lô hàng chi nhánh.
     */
    public function inventory(Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        $inventory = TonKho::query()
            ->where('cua_hang_id', $storeId)
            ->with(['sanPham.danhMuc'])
            ->get()
            ->map(function ($item) use ($storeId) {
                $lots = LoHang::query()
                    ->where('cua_hang_id', $storeId)
                    ->where('san_pham_id', $item->san_pham_id)
                    ->where('so_luong_con_lai', '>', 0)
                    ->orderBy('ngay_het_han', 'asc')
                    ->get();

                return [
                    'id' => $item->id,
                    'san_pham_id' => $item->san_pham_id,
                    'ten_san_pham' => $item->sanPham?->ten_san_pham,
                    'ma_va_vach' => $item->sanPham?->ma_va_vach,
                    'danh_muc' => $item->sanPham?->danhMuc?->ten_danh_muc,
                    'don_vi_tinh' => $item->sanPham?->don_vi_tinh,
                    'so_luong_ton' => (float) $item->so_luong_ton,
                    'so_luong_tam_giu' => (float) $item->so_luong_tam_giu,
                    'nguong_canh_bao_thap' => (float) $item->nguong_canh_bao_thap,
                    'canh_bao' => (float) $item->so_luong_ton <= (float) $item->nguong_canh_bao_thap,
                    'lots' => $lots,
                ];
            });

        return Inertia::render('store/inventory/Index', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'inventory' => $inventory,
        ]);
    }

    /**
     * Màn hình cảnh báo cận hạn sử dụng & xả hàng.
     */
    public function nearExpiry(Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        $today = Carbon::today();
        $in3Days = Carbon::today()->addDays(3);

        $lots = LoHang::query()
            ->where('cua_hang_id', $storeId)
            ->where('so_luong_con_lai', '>', 0)
            ->where('trang_thai', 1)
            ->where('ngay_het_han', '<=', $in3Days->toDateString())
            ->with(['sanPham.danhMuc'])
            ->orderBy('ngay_het_han', 'asc')
            ->get()
            ->map(function ($lot) use ($today) {
                $hsd = $lot->ngay_het_han ? Carbon::parse($lot->ngay_het_han) : $today;
                $diffDays = $today->diffInDays($hsd, false);

                $level = 'safe';
                if ($diffDays < 0) {
                    $level = 'expired'; // Đã hết hạn
                } elseif ($diffDays <= 1) {
                    $level = 'critical'; // Đỏ: <= 1 ngày
                } elseif ($diffDays <= 3) {
                    $level = 'warning'; // Vàng: 2 - 3 ngày
                }

                return [
                    'id' => $lot->id,
                    'ma_lo' => $lot->ma_lo,
                    'san_pham_id' => $lot->san_pham_id,
                    'ten_san_pham' => $lot->sanPham?->ten_san_pham,
                    'danh_muc' => $lot->sanPham?->danhMuc?->ten_danh_muc,
                    'don_vi_tinh' => $lot->sanPham?->don_vi_tinh,
                    'gia_ban_goc' => (float) ($lot->sanPham?->gia_ban ?? 0),
                    'so_luong_con_lai' => (float) $lot->so_luong_con_lai,
                    'ngay_san_xuat' => $lot->ngay_san_xuat ? $lot->ngay_san_xuat->format('d/m/Y') : null,
                    'ngay_het_han' => $lot->ngay_het_han ? $lot->ngay_het_han->format('d/m/Y') : null,
                    'so_ngay_con_lai' => (int) $diffDays,
                    'muc_do_canh_bao' => $level,
                ];
            });

        return Inertia::render('store/inventory/NearExpiry', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'nearExpiryLots' => $lots,
        ]);
    }

    /**
     * Áp dụng giá xả hàng nhanh cho sản phẩm cận date tại chi nhánh.
     */
    public function applyDiscount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'san_pham_id' => ['required', 'exists:san_pham,id'],
            'gia_giam' => ['required', 'numeric', 'min:0'],
        ]);

        $storeId = $this->layStoreId($request);
        $sanPhamId = (int) $validated['san_pham_id'];
        $giaGiam = (float) $validated['gia_giam'];

        $sanPham = SanPham::findOrFail($sanPhamId);
        $giaBan = (float) $sanPham->gia_ban;

        GiaSanPhamCuaHang::updateOrCreate(
            [
                'cua_hang_id' => $storeId,
                'san_pham_id' => $sanPhamId,
            ],
            [
                'gia_ban' => $giaBan,
                'gia_giam' => $giaGiam,
            ]
        );

        return back()->with('success', 'Đã áp dụng giá xả hàng '.number_format($giaGiam)."đ cho {$sanPham->ten_san_pham}!");
    }

    /**
     * Xuất hủy lô hàng bị quá date hoặc dập nát.
     */
    public function discardLot(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user?->isAdmin() && ! $user?->isQuanLy()) {
            abort(403, 'Chỉ Quản lý chi nhánh hoặc Admin mới có thẩm quyền xuất hủy lô hàng.');
        }

        $validated = $request->validate([
            'lo_hang_id' => ['required', 'exists:lo_hang,id'],
            'so_luong' => ['required', 'numeric', 'min:0.1'],
            'ly_do' => ['required', 'string', 'max:255'],
        ]);

        $storeId = $this->layStoreId($request);

        $loHang = LoHang::findOrFail($validated['lo_hang_id']);
        if (! $user->isAdmin() && (int) $loHang->cua_hang_id !== $storeId) {
            abort(403, 'Bạn không thể thao tác trên lô hàng của chi nhánh khác.');
        }

        try {
            $phieuXuat = $this->inventoryService->xuatHuyHang(
                cuaHangId: $storeId,
                loHangId: (int) $validated['lo_hang_id'],
                soLuong: (float) $validated['so_luong'],
                nguoiThucHienId: $user->id,
                lyDo: $validated['ly_do']
            );

            return back()->with('success', "Đã tạo phiếu xuất hủy #{$phieuXuat->ma_phieu} thành công!");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
