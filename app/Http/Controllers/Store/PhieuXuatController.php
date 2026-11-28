<?php

namespace App\Http\Controllers\Store;

use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use App\Models\TonKho;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PhieuXuatController extends Controller
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

    public function index(Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        $phieuXuats = PhieuXuat::query()
            ->where(function ($q) use ($storeId) {
                $q->where('cua_hang_id', $storeId)
                    ->orWhere('cua_hang_nhan_id', $storeId);
            })
            ->with(['nguoiTao', 'donHang', 'cuaHang', 'cuaHangNhan', 'nguoiGiaoHang', 'chiTiets.sanPham', 'chiTiets.chiTietXuatKhos.loHang'])
            ->latest('id')
            ->get();

        $pendingOrders = DonHang::query()
            ->where('cua_hang_id', $storeId)
            ->where('trang_thai_don_hang', 'cho_xac_nhan')
            ->with(['user', 'nguoiGiaoHang', 'chiTietDonHangs.sanPham'])
            ->latest('id')
            ->get();

        $readyOrders = DonHang::query()
            ->where('cua_hang_id', $storeId)
            ->whereIn('trang_thai_don_hang', ['dang_chuan_bi', 'dang_giao', 'da_giao'])
            ->with(['user', 'nguoiGiaoHang', 'chiTietDonHangs.sanPham', 'phieuXuats'])
            ->latest('id')
            ->get();

        $drivers = User::where('role', 'giao_hang')
            ->where(function ($q) use ($storeId, $user) {
                if (! $user?->isAdmin()) {
                    $q->where('store_id', $storeId);
                }
            })
            ->get(['id', 'name', 'phone', 'loai_phuong_tien', 'bien_so_xe', 'tai_trong_kg', 'trang_thai_giao']);

        return Inertia::render('store/outbound/Index', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'phieuXuats' => $phieuXuats,
            'pendingOrders' => $pendingOrders,
            'readyOrders' => $readyOrders,
            'drivers' => $drivers,
        ]);
    }

    public function show(int $id, Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        $phieuXuat = PhieuXuat::query()
            ->where(function ($q) use ($storeId) {
                $q->where('cua_hang_id', $storeId)
                    ->orWhere('cua_hang_nhan_id', $storeId);
            })
            ->with(['nguoiTao', 'donHang', 'cuaHang', 'cuaHangNhan', 'nguoiGiaoHang', 'chiTiets.sanPham', 'chiTiets.chiTietXuatKhos.loHang'])
            ->findOrFail($id);

        return Inertia::render('store/outbound/Show', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'phieuXuat' => $phieuXuat,
        ]);
    }

    /**
     * Màn hình lập phiếu điều chuyển hàng hóa giữa các chi nhánh.
     */
    public function createTransfer(Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        // Danh sách sản phẩm kèm tồn kho khả dụng tại chi nhánh nguồn
        $products = SanPham::dangKinhDoanh()
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
                    'don_vi_tinh' => $p->don_vi_tinh,
                    'danh_muc' => $p->danhMuc?->ten_danh_muc,
                    'so_luong_ton' => (float) $ton,
                ];
            });

        $drivers = User::where('role', 'giao_hang')
            ->where(function ($q) use ($storeId, $user) {
                if (! $user?->isAdmin()) {
                    $q->where('store_id', $storeId);
                }
            })
            ->get(['id', 'name', 'phone', 'loai_phuong_tien', 'bien_so_xe', 'tai_trong_kg', 'trang_thai_giao']);

        return Inertia::render('store/transfer/Create', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'products' => $products,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Xử lý xác nhận điều chuyển hàng hóa chi nhánh theo FEFO.
     */
    public function storeTransfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cua_hang_xuat_id' => ['required', 'exists:cua_hang,id'],
            'cua_hang_nhan_id' => ['required', 'exists:cua_hang,id', 'different:cua_hang_xuat_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.san_pham_id' => ['required', 'exists:san_pham,id'],
            'items.*.so_luong' => ['required', 'numeric', 'min:0.01'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
            'nguoi_giao_hang_id' => ['nullable', 'exists:users,id'],
            'loai_phuong_tien' => ['nullable', 'in:xe_tai,xe_may'],
            'bien_so_xe' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();

        // Kiểm tra quy tắc phương tiện: Điều chuyển số lượng lớn (>= 20) bắt buộc/khuyến nghị dùng Xe Tải
        $totalQty = (float) array_sum(array_column($validated['items'], 'so_luong'));
        $loaiPhuongTien = $validated['loai_phuong_tien'] ?? 'xe_tai';

        if ($totalQty >= 20 && $loaiPhuongTien === 'xe_may') {
            return back()->withErrors([
                'error' => "Số lượng điều chuyển lớn ({$totalQty} sản phẩm) vượt quá tải trọng xe máy. Vui lòng chọn Xe Tải để vận chuyển!",
            ]);
        }

        // Nếu có chọn tài xế, tự động lấy biển số xe từ hồ sơ tài xế nếu chưa nhập
        $bienSoXe = $validated['bien_so_xe'] ?? null;
        if (! empty($validated['nguoi_giao_hang_id']) && empty($bienSoXe)) {
            $driver = User::find($validated['nguoi_giao_hang_id']);
            $bienSoXe = $driver?->bien_so_xe;
        }

        // Nếu là quản lý chi nhánh, chỉ được điều chuyển từ hoặc đến chi nhánh của mình
        if ($user && ! $user->isAdmin() && $user->store_id) {
            if ((int) $user->store_id !== (int) $validated['cua_hang_xuat_id'] && (int) $user->store_id !== (int) $validated['cua_hang_nhan_id']) {
                return back()->withErrors(['error' => 'Bạn chỉ có quyền điều chuyển hàng hóa liên quan đến chi nhánh của mình.']);
            }
        }

        try {
            $phieuXuat = $this->inventoryService->dieuChuyenKhoChiNhanh(
                cuaHangXuatId: (int) $validated['cua_hang_xuat_id'],
                cuaHangNhanId: (int) $validated['cua_hang_nhan_id'],
                items: $validated['items'],
                nguoiThucHienId: (int) $user->id,
                ghiChu: $validated['ghi_chu'] ?? null,
                nguoiGiaoHangId: ! empty($validated['nguoi_giao_hang_id']) ? (int) $validated['nguoi_giao_hang_id'] : null,
                loaiPhuongTien: $loaiPhuongTien,
                bienSoXe: $bienSoXe
            );

            $cuaHangXuat = CuaHang::find($validated['cua_hang_xuat_id']);
            $cuaHangNhan = CuaHang::find($validated['cua_hang_nhan_id']);

            return redirect()
                ->route('store.outbound.show', $phieuXuat->id)
                ->with('success', "Lập phiếu điều chuyển #{$phieuXuat->ma_phieu} thành công từ {$cuaHangXuat?->ten_cua_hang} sang {$cuaHangNhan?->ten_cua_hang}!");
        } catch (InsufficientInventoryException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Lỗi điều chuyển: '.$e->getMessage()]);
        }
    }

    /**
     * Xác nhận đã nhận hàng điều chuyển vào kho chi nhánh nhận.
     */
    public function confirmTransfer(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $phieuXuat = PhieuXuat::findOrFail($id);

        if ($user && ! $user->isAdmin() && $user->store_id && (int) $phieuXuat->cua_hang_nhan_id !== (int) $user->store_id) {
            return back()->withErrors(['error' => 'Bạn không có quyền xác nhận nhận hàng cho chi nhánh khác.']);
        }

        try {
            $this->inventoryService->xacNhanNhapChuyen($phieuXuat, (int) $user->id);

            return back()->with('success', "Đã xác nhận nhập kho thành công cho phiếu điều chuyển #{$phieuXuat->ma_phieu}!");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Lỗi xác nhận: '.$e->getMessage()]);
        }
    }

    /**
     * Soạn hàng & xuất kho FEFO cho đơn hàng online của chi nhánh.
     */
    public function fulfillOrder(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();

        $donHang = DonHang::findOrFail($id);

        if ($user && ! $user->isAdmin() && $user->store_id && (int) $donHang->cua_hang_id !== (int) $user->store_id) {
            return back()->withErrors(['error' => 'Bạn không có quyền xuất kho đơn hàng của chi nhánh khác.']);
        }

        if ($donHang->phieuXuats()->exists()) {
            $existing = $donHang->phieuXuats()->latest('id')->first();

            return redirect()
                ->route('store.outbound.show', $existing->id)
                ->with('info', "Đơn hàng này đã có phiếu xuất kho #{$existing->ma_phieu}.");
        }

        if (in_array($donHang->trang_thai_don_hang, ['da_huy', 'hoan_thanh'])) {
            return back()->withErrors(['error' => "Không thể xuất kho đơn hàng ở trạng thái '{$donHang->trang_thai_don_hang}'."]);
        }

        try {
            $phieuXuat = $this->inventoryService->xuatKhoDonHang($donHang, $user?->id);

            $donHang->update([
                'trang_thai_don_hang' => 'dang_chuan_bi',
                'nhan_vien_len_don_id' => $donHang->nhan_vien_len_don_id ?? $user?->id,
            ]);

            return redirect()
                ->route('store.outbound.show', $phieuXuat->id)
                ->with('success', "Soạn hàng & xuất kho FEFO thành công cho đơn #{$donHang->ma_don_hang}! Mã phiếu: #{$phieuXuat->ma_phieu}");
        } catch (InsufficientInventoryException $e) {
            $donHang->update([
                'trang_thai_don_hang' => 'dang_chuan_bi',
            ]);

            return back()->with('info', "Đơn #{$donHang->ma_don_hang} đã chuyển sang trạng thái 'Đang đóng gói'! (Chưa thể xuất kho tự động do: {$e->getMessage()})");
        } catch (\Exception $e) {
            $donHang->update([
                'trang_thai_don_hang' => 'dang_chuan_bi',
            ]);

            return back()->with('info', "Đơn #{$donHang->ma_don_hang} đã chuyển sang trạng thái 'Đang đóng gói'! (Lỗi xuất kho: {$e->getMessage()})");
        }
    }
}
