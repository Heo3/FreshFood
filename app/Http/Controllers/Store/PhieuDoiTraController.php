<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\PhieuDoiTra;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PhieuDoiTraController extends Controller
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

        $phieuDoiTras = PhieuDoiTra::query()
            ->where('cua_hang_id', $storeId)
            ->with(['donHang', 'user', 'nguoiDuyet', 'chiTiets.sanPham'])
            ->latest('id')
            ->get();

        return Inertia::render('store/returns/Index', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'phieuDoiTras' => $phieuDoiTras,
        ]);
    }

    public function approve(int $id, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user?->isAdmin() && ! $user?->isQuanLy()) {
            abort(403, 'Chỉ Quản lý chi nhánh hoặc Quản trị viên mới có thẩm quyền duyệt đổi trả hàng.');
        }

        $storeId = $this->layStoreId($request);

        $phieu = PhieuDoiTra::where('id', $id)
            ->where('cua_hang_id', $storeId)
            ->with('chiTiets')
            ->firstOrFail();

        if ($phieu->trang_thai !== 0) {
            return back()->withErrors(['error' => 'Phiếu đổi trả này đã được xử lý trước đó.']);
        }

        DB::transaction(function () use ($phieu, $user, $storeId) {
            $phieu->trang_thai = 1; // Đã chấp thuận
            $phieu->nguoi_duyet_id = $user->id;
            $phieu->save();

            // Nếu là đổi sản phẩm mới (loai_doi_tra = 'doi_hang') -> tự động xuất kho FEFO lô mới bù cho khách
            if ($phieu->loai_doi_tra === 'doi_hang') {
                foreach ($phieu->chiTiets as $chiTiet) {
                    try {
                        $this->inventoryService->xuatKhoSanPham(
                            cuaHangId: $storeId,
                            sanPhamId: $chiTiet->san_pham_id,
                            soLuong: (float) $chiTiet->so_luong,
                            loaiThaoTac: 'xuat_ban',
                            maChungTu: $phieu->ma_phieu,
                            nguoiThucHienId: $user->id,
                            ghiChu: "Xuất bù đổi trả theo phiếu #{$phieu->ma_phieu}"
                        );
                    } catch (\Exception $e) {
                        // Bỏ qua nếu hết tồn kho, chỉ cập nhật trạng thái phiếu đổi trả
                    }
                }
            }
        });

        return back()->with('success', "Đã duyệt thành công yêu cầu đổi trả #{$phieu->ma_phieu}!");
    }

    public function reject(int $id, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user?->isAdmin() && ! $user?->isQuanLy()) {
            abort(403, 'Chỉ Quản lý chi nhánh hoặc Quản trị viên mới có thẩm quyền duyệt đổi trả hàng.');
        }

        $validated = $request->validate([
            'ly_do_tu_choi' => ['required', 'string', 'max:255'],
        ]);

        $storeId = $this->layStoreId($request);

        $phieu = PhieuDoiTra::where('id', $id)
            ->where('cua_hang_id', $storeId)
            ->firstOrFail();

        if ($phieu->trang_thai !== 0) {
            return back()->withErrors(['error' => 'Phiếu đổi trả này đã được xử lý trước đó.']);
        }

        $phieu->trang_thai = 2; // Từ chối
        $phieu->nguoi_duyet_id = $user->id;
        $phieu->ghi_chu = 'Từ chối: '.$validated['ly_do_tu_choi'];
        $phieu->save();

        return back()->with('info', "Đã từ chối yêu cầu đổi trả #{$phieu->ma_phieu}.");
    }
}
