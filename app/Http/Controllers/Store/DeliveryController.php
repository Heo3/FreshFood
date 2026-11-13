<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\PhieuXuat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryController extends Controller
{
    /**
     * Màn hình quản lý đội xe và nhân viên giao hàng (Dành cho Quản lý & Admin).
     */
    public function fleetIndex(Request $request): Response
    {
        $user = $request->user();
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();

        $selectedStoreId = $request->input('store_id', $user?->store_id ?? $stores->first()?->id);
        $currentStore = CuaHang::find($selectedStoreId) ?? $stores->first();

        $drivers = User::query()
            ->where('role', 'giao_hang')
            ->when($selectedStoreId && ! $user?->isAdmin(), function ($q) use ($selectedStoreId) {
                $q->where('store_id', $selectedStoreId);
            })
            ->with(['cuaHang'])
            ->withCount(['donHangsGiao as don_hang_dang_giao_count' => function ($q) {
                $q->where('trang_thai_don_hang', 'dang_giao');
            }])
            ->get();

        return Inertia::render('store/delivery/Fleet', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Cập nhật thông tin phương tiện hoặc trạng thái nhân viên giao hàng.
     */
    public function updateDriver(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'loai_phuong_tien' => ['required', 'in:xe_may,xe_tai'],
            'bien_so_xe' => ['required', 'string', 'max:30'],
            'tai_trong_kg' => ['nullable', 'integer', 'min:1'],
            'trang_thai_giao' => ['required', 'in:san_sang,dang_giao,nghi'],
        ]);

        $driver = User::where('role', 'giao_hang')->findOrFail($id);
        $driver->update($validated);

        return back()->with('success', "Đã cập nhật thông tin phương tiện cho tài xế {$driver->name}!");
    }

    /**
     * Điều phối nhân viên và phương tiện giao hàng cho đơn hàng khách.
     */
    public function assignDelivery(Request $request, int $id): RedirectResponse
    {
        $donHang = DonHang::with(['chiTiets'])->findOrFail($id);

        $validated = $request->validate([
            'nguoi_giao_hang_id' => ['required', 'exists:users,id'],
            'loai_phuong_tien_giao' => ['required', 'in:xe_may,xe_tai'],
            'bien_so_xe_giao' => ['nullable', 'string', 'max:30'],
            'ghi_chu_giao_hang' => ['nullable', 'string', 'max:500'],
        ]);

        $shipper = User::where('id', $validated['nguoi_giao_hang_id'])
            ->where('role', 'giao_hang')
            ->firstOrFail();

        $bienSoXe = $validated['bien_so_xe_giao'] ?: $shipper->bien_so_xe;

        // Cập nhật thông tin giao hàng cho đơn hàng
        $donHang->update([
            'nguoi_giao_hang_id' => $shipper->id,
            'loai_phuong_tien_giao' => $validated['loai_phuong_tien_giao'],
            'bien_so_xe_giao' => $bienSoXe,
            'ghi_chu_giao_hang' => $validated['ghi_chu_giao_hang'] ?? null,
            'trang_thai_don_hang' => 'dang_giao',
            'thoi_gian_bat_dau_giao' => now(),
        ]);

        // Cập nhật trạng thái shipper
        $shipper->update(['trang_thai_giao' => 'dang_giao']);

        $phuongTienText = $validated['loai_phuong_tien_giao'] === 'xe_tai' ? 'Xe Tải 🚚' : 'Xe Máy 🏍️';

        return back()->with('success', "Đã điều phối đơn #{$donHang->ma_don_hang} cho tài xế {$shipper->name} ({$phuongTienText} - BKS: {$bienSoXe}) xuất phát giao hàng!");
    }

    /**
     * Cổng thông tin giao hàng dành riêng cho Nhân viên giao hàng (Shipper / Tài xế).
     */
    public function myDeliveries(Request $request): Response
    {
        $user = $request->user();

        // Danh sách đơn hàng được gán cho nhân viên này
        $activeDeliveries = DonHang::query()
            ->where('nguoi_giao_hang_id', $user->id)
            ->whereIn('trang_thai_don_hang', ['dang_chuan_bi', 'dang_giao'])
            ->with(['cuaHang', 'chiTiets.sanPham'])
            ->latest('thoi_gian_bat_dau_giao')
            ->get();

        $historyDeliveries = DonHang::query()
            ->where('nguoi_giao_hang_id', $user->id)
            ->whereIn('trang_thai_don_hang', ['da_giao', 'hoan_thanh', 'da_huy'])
            ->with(['cuaHang', 'chiTiets.sanPham'])
            ->latest('thoi_gian_giao_thanh_cong')
            ->limit(20)
            ->get();

        // Đối với tài xế xe tải: Xem thêm các chuyến điều chuyển liên chi nhánh
        $transferTrips = [];
        if ($user->isXeTai() || $user->isAdmin()) {
            $transferTrips = PhieuXuat::query()
                ->where('loai_xuat', PhieuXuat::LOAI_XUAT_CHUYEN)
                ->where(function ($q) use ($user) {
                    if (! $user->isAdmin()) {
                        $q->where('nguoi_giao_hang_id', $user->id);
                    }
                })
                ->with(['cuaHang', 'cuaHangNhan', 'chiTiets.sanPham'])
                ->latest('id')
                ->limit(10)
                ->get();
        }

        return Inertia::render('store/delivery/MyDeliveries', [
            'activeDeliveries' => $activeDeliveries,
            'historyDeliveries' => $historyDeliveries,
            'transferTrips' => $transferTrips,
            'driverInfo' => $user,
        ]);
    }

    /**
     * Xác nhận giao hàng thành công đến tay khách hàng.
     */
    public function completeDelivery(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $donHang = DonHang::findOrFail($id);

        if (! $user->isAdmin() && (int) $donHang->nguoi_giao_hang_id !== (int) $user->id) {
            return back()->withErrors(['error' => 'Bạn không được phân công giao đơn hàng này.']);
        }

        $updateData = [
            'trang_thai_don_hang' => 'da_giao',
            'thoi_gian_giao_thanh_cong' => now(),
        ];

        // Nếu là COD thì tự động ghi nhận thanh toán tiền mặt thành công
        if ($donHang->phuong_thuc_thanh_toan === 'cod') {
            $updateData['trang_thai_thanh_toan'] = 'da_thanh_toan';
        }

        $donHang->update($updateData);

        // Kiểm tra xem shipper còn đơn nào đang giao không
        $conDonDangGiao = DonHang::where('nguoi_giao_hang_id', $donHang->nguoi_giao_hang_id)
            ->where('trang_thai_don_hang', 'dang_giao')
            ->exists();

        if (! $conDonDangGiao && $donHang->nguoi_giao_hang_id) {
            User::where('id', $donHang->nguoi_giao_hang_id)->update(['trang_thai_giao' => 'san_sang']);
        }

        return back()->with('success', "Đã ghi nhận giao thành công đơn hàng #{$donHang->ma_don_hang}!");
    }

    /**
     * Báo cáo sự cố hoặc giao hàng thất bại (khách hẹn lại / không liên lạc được).
     */
    public function failDelivery(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'ly_do' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $donHang = DonHang::findOrFail($id);

        if (! $user->isAdmin() && (int) $donHang->nguoi_giao_hang_id !== (int) $user->id) {
            return back()->withErrors(['error' => 'Bạn không được phân công giao đơn hàng này.']);
        }

        $donHang->update([
            'ghi_chu_giao_hang' => 'Giao chưa thành công: '.$validated['ly_do'],
        ]);

        return back()->with('info', "Đã cập nhật ghi chú sự cố cho đơn hàng #{$donHang->ma_don_hang}.");
    }
}
