<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\LichSuTichDiem;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        protected ?InventoryService $inventoryService = null
    ) {
        $this->inventoryService = $inventoryService ?? app(InventoryService::class);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isManager = $user && $user->isQuanLy() && $user->store_id;

        $search = $request->input('search');
        $status = $request->input('trang_thai_don_hang');
        $storeId = $isManager ? (int) $user->store_id : $request->input('cua_hang_id');
        $paymentStatus = $request->input('trang_thai_thanh_toan');

        $query = DonHang::with(['user', 'cuaHang', 'chiTietDonHangs']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ma_don_hang', 'like', "%{$search}%")
                    ->orWhere('ten_nguoi_nhan', 'like', "%{$search}%")
                    ->orWhere('so_dien_thoai_nhan', 'like', "%{$search}%");
            });
        }

        if ($status) {
            if (in_array($status, ['hoan_thanh', 'da_giao'])) {
                $query->whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao']);
            } else {
                $query->where('trang_thai_don_hang', $status);
            }
        }

        if ($storeId) {
            $query->where('cua_hang_id', $storeId);
        }

        if ($paymentStatus) {
            $query->where('trang_thai_thanh_toan', $paymentStatus);
        }

        $orders = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $stores = $isManager
            ? CuaHang::where('id', $user->store_id)->get(['id', 'ten_cua_hang'])
            : CuaHang::where('trang_thai', 1)->get(['id', 'ten_cua_hang']);

        return Inertia::render('admin/orders/Index', [
            'orders' => $orders,
            'stores' => $stores,
            'filters' => [
                'search' => $search,
                'trang_thai_don_hang' => $status,
                'cua_hang_id' => $storeId,
                'trang_thai_thanh_toan' => $paymentStatus,
            ],
            'is_scoped_manager' => $isManager,
        ]);
    }

    public function show(Request $request, DonHang $donHang): Response
    {
        $user = $request->user();
        if ($user && $user->isQuanLy() && $user->store_id && (int) $donHang->cua_hang_id !== (int) $user->store_id) {
            abort(403, 'Bạn không có quyền truy cập vào đơn hàng của chi nhánh khác.');
        }

        $donHang->load(['user', 'cuaHang', 'chiTietDonHangs.sanPham', 'maGiamGia']);

        return Inertia::render('admin/orders/Show', [
            'order' => $donHang,
        ]);
    }

    public function updateStatus(Request $request, DonHang $donHang): RedirectResponse
    {
        $user = $request->user();
        if ($user && $user->isQuanLy() && $user->store_id && (int) $donHang->cua_hang_id !== (int) $user->store_id) {
            abort(403, 'Bạn không có quyền cập nhật trạng thái đơn hàng của chi nhánh khác.');
        }

        $validated = $request->validate([
            'trang_thai_don_hang' => ['required', 'in:cho_xac_nhan,dang_chuan_bi,dang_giao,da_giao,hoan_thanh,da_huy'],
            'trang_thai_thanh_toan' => ['nullable', 'in:chua_thanh_toan,da_thanh_toan,hoan_tien'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);

        $updateData = [
            'trang_thai_don_hang' => $validated['trang_thai_don_hang'],
        ];

        if (! empty($validated['trang_thai_thanh_toan'])) {
            $updateData['trang_thai_thanh_toan'] = $validated['trang_thai_thanh_toan'];
        }

        if (in_array($validated['trang_thai_don_hang'], ['hoan_thanh', 'da_giao']) && empty($validated['trang_thai_thanh_toan'])) {
            $updateData['trang_thai_thanh_toan'] = 'da_thanh_toan';
        }

        if (! empty($validated['ghi_chu'])) {
            $updateData['ghi_chu'] = $validated['ghi_chu'];
        }

        // Cố gắng tự động sinh Phiếu Xuất Kho (FEFO) và trừ lô hàng khi đơn hàng được chuẩn bị / đóng gói
        $fefoWarning = '';
        if (in_array($validated['trang_thai_don_hang'], ['dang_chuan_bi', 'dang_giao']) && ! $donHang->phieuXuats()->exists() && $donHang->chiTiets()->exists()) {
            try {
                $this->inventoryService->xuatKhoDonHang($donHang, $user?->id);
            } catch (InsufficientInventoryException $e) {
                $fefoWarning = ' (Lưu ý: Chưa thể tự động xuất kho FEFO do: '.$e->getMessage().')';
            } catch (\Throwable $e) {
                $fefoWarning = ' (Lưu ý: Không thể xuất kho tự động: '.$e->getMessage().')';
            }
        }

        // Xử lý hoàn điểm đã tiêu, hoàn voucher và trừ lại điểm đã cộng khi hủy đơn hàng
        if ($validated['trang_thai_don_hang'] === 'da_huy' && $donHang->trang_thai_don_hang !== 'da_huy') {
            DB::transaction(function () use ($donHang) {
                // 1. Hoàn lại lượt sử dụng mã giảm giá nếu có
                if ($donHang->ma_giam_gia_id && $donHang->maGiamGia) {
                    if ($donHang->maGiamGia->da_su_dung > 0) {
                        $donHang->maGiamGia->decrement('da_su_dung');
                    }
                }

                $customer = $donHang->user;
                if ($customer) {
                    // 2. Hoàn lại điểm tích lũy nếu đơn hàng có tiêu điểm
                    $tieuDiemRecord = LichSuTichDiem::where('don_hang_id', $donHang->id)
                        ->where('user_id', $customer->id)
                        ->where('loai_giao_dich', 'tieu_diem')
                        ->first();

                    $alreadyRefunded = LichSuTichDiem::where('don_hang_id', $donHang->id)
                        ->where('loai_giao_dich', 'hoan_diem')
                        ->exists();

                    if (! $alreadyRefunded && $tieuDiemRecord && abs($tieuDiemRecord->so_diem_thay_doi) > 0) {
                        $diemHoan = abs($tieuDiemRecord->so_diem_thay_doi);
                        $customer->increment('loyalty_points', $diemHoan);

                        LichSuTichDiem::create([
                            'user_id' => $customer->id,
                            'don_hang_id' => $donHang->id,
                            'loai_giao_dich' => 'hoan_diem',
                            'so_diem_thay_doi' => $diemHoan,
                            'so_diem_sau_giao_dich' => (int) $customer->loyalty_points,
                            'ghi_chu' => "Hoàn lại điểm do admin hủy đơn hàng #{$donHang->ma_don_hang}",
                        ]);
                    }

                    // 3. Trừ lại số điểm đã cộng (tích lũy) từ đơn hàng này khi hủy đơn
                    $tichDiemRecord = LichSuTichDiem::where('don_hang_id', $donHang->id)
                        ->where('user_id', $customer->id)
                        ->where('loai_giao_dich', 'tich_diem')
                        ->first();

                    $alreadyRevoked = LichSuTichDiem::where('don_hang_id', $donHang->id)
                        ->where('loai_giao_dich', 'admin_dieu_chinh')
                        ->where('so_diem_thay_doi', '<', 0)
                        ->exists();

                    if (! $alreadyRevoked && $tichDiemRecord && $tichDiemRecord->so_diem_thay_doi > 0) {
                        $diemDaCong = $tichDiemRecord->so_diem_thay_doi;
                        $customer->loyalty_points = max(0, (int) $customer->loyalty_points - $diemDaCong);
                        $customer->save();

                        LichSuTichDiem::create([
                            'user_id' => $customer->id,
                            'don_hang_id' => $donHang->id,
                            'loai_giao_dich' => 'admin_dieu_chinh',
                            'so_diem_thay_doi' => -$diemDaCong,
                            'so_diem_sau_giao_dich' => (int) $customer->loyalty_points,
                            'ghi_chu' => "Thu hồi {$diemDaCong} điểm tích lũy do hủy đơn hàng #{$donHang->ma_don_hang}",
                        ]);
                    }
                }
            });
        }

        $donHang->update($updateData);

        return back()->with('success', "Đã cập nhật trạng thái đơn hàng {$donHang->ma_don_hang} thành công!{$fefoWarning}");
    }
}
