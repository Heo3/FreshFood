<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isScopedManager = $user && $user->isQuanLy() && $user->store_id;
        $scopedStoreId = $isScopedManager ? (int) $user->store_id : null;
        $scopedStore = $scopedStoreId ? CuaHang::find($scopedStoreId) : null;

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Chỉ số tổng quan
        $tongDoanhThuQuery = DonHang::whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao', 'dang_giao']);
        $homNayQuery = DonHang::whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao', 'dang_giao'])->whereDate('created_at', $today);
        $thangNayQuery = DonHang::whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao', 'dang_giao'])->where('created_at', '>=', $startOfMonth);

        $donHomNayQuery = DonHang::whereDate('created_at', $today);
        $donChoXacNhanQuery = DonHang::where('trang_thai_don_hang', 'cho_xac_nhan');
        $donHoanThanhQuery = DonHang::whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao']);
        $donDaHuyQuery = DonHang::where('trang_thai_don_hang', 'da_huy');

        $loHetHanQuery = LoHang::where('ngay_het_han', '<', $today)->where('so_luong_con_lai', '>', 0);
        $loCanDateQuery = LoHang::where('ngay_het_han', '>=', $today)->where('ngay_het_han', '<=', Carbon::now()->addDays(3))->where('so_luong_con_lai', '>', 0);

        $xuatHuyQuery = PhieuXuat::where('loai_xuat', PhieuXuat::LOAI_XUAT_HUY)->where('trang_thai', PhieuXuat::TRANG_THAI_DA_XUAT);

        if ($scopedStoreId) {
            $tongDoanhThuQuery->where('cua_hang_id', $scopedStoreId);
            $homNayQuery->where('cua_hang_id', $scopedStoreId);
            $thangNayQuery->where('cua_hang_id', $scopedStoreId);
            $donHomNayQuery->where('cua_hang_id', $scopedStoreId);
            $donChoXacNhanQuery->where('cua_hang_id', $scopedStoreId);
            $donHoanThanhQuery->where('cua_hang_id', $scopedStoreId);
            $donDaHuyQuery->where('cua_hang_id', $scopedStoreId);
            $loHetHanQuery->where('cua_hang_id', $scopedStoreId);
            $loCanDateQuery->where('cua_hang_id', $scopedStoreId);
            $xuatHuyQuery->where('cua_hang_id', $scopedStoreId);
        }

        $tongDoanhThu = (float) $tongDoanhThuQuery->sum('tong_thanhtoan');
        $doanhThuHomNay = (float) $homNayQuery->sum('tong_thanhtoan');
        $doanhThuThangNay = (float) $thangNayQuery->sum('tong_thanhtoan');

        $donHangHomNay = $donHomNayQuery->count();
        $donChoXacNhan = $donChoXacNhanQuery->count();
        $donHoanThanh = $donHoanThanhQuery->count();
        $donDaHuy = $donDaHuyQuery->count();

        $soLoHetHan = $loHetHanQuery->count();
        $soLoCanDate = $loCanDateQuery->count();
        $tongTienXuatHuy = (float) $xuatHuyQuery->sum('tong_gia_tri');

        // 2. Doanh thu 7 ngày gần nhất
        $doanhThu7Ngay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $label = $date->format('d/m');

            $revQuery = DonHang::whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao', 'dang_giao'])->whereDate('created_at', $dateStr);
            $cntQuery = DonHang::whereDate('created_at', $dateStr);

            if ($scopedStoreId) {
                $revQuery->where('cua_hang_id', $scopedStoreId);
                $cntQuery->where('cua_hang_id', $scopedStoreId);
            }

            $doanhThu7Ngay[] = [
                'ngay' => $label,
                'doanh_thu' => (float) $revQuery->sum('tong_thanhtoan'),
                'so_don' => $cntQuery->count(),
            ];
        }

        // 3. Doanh thu theo từng chi nhánh
        $storesQuery = CuaHang::query();
        if ($scopedStoreId) {
            $storesQuery->where('id', $scopedStoreId);
        }
        $doanhThuChiNhanh = $storesQuery->withCount('donHangs')
            ->get()
            ->map(function ($store) {
                $rev = (float) DonHang::where('cua_hang_id', $store->id)
                    ->whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao', 'dang_giao'])
                    ->sum('tong_thanhtoan');

                return [
                    'id' => $store->id,
                    'ten_cua_hang' => $store->ten_cua_hang,
                    'so_don' => $store->don_hangs_count,
                    'doanh_thu' => $rev,
                ];
            });

        // 4. Top 5 sản phẩm bán chạy nhất
        $topSanPham = ChiTietDonHang::select(
            'san_pham_id',
            'ten_san_pham',
            DB::raw('SUM(so_luong) as tong_ban'),
            DB::raw('SUM(thanh_tien) as tong_tien')
        )
            ->whereHas('donHang', function ($q) use ($scopedStoreId) {
                $q->where('trang_thai_don_hang', '!=', 'da_huy');
                if ($scopedStoreId) {
                    $q->where('cua_hang_id', $scopedStoreId);
                }
            })
            ->groupBy('san_pham_id', 'ten_san_pham')
            ->orderByDesc('tong_ban')
            ->limit(5)
            ->get();

        // 5. Đơn hàng gần đây
        $donHangMoiQuery = DonHang::with(['user', 'cuaHang'])->latest()->limit(6);
        if ($scopedStoreId) {
            $donHangMoiQuery->where('cua_hang_id', $scopedStoreId);
        }

        $donHangMoi = $donHangMoiQuery->get()->map(fn ($dh) => [
            'id' => $dh->id,
            'ma_don_hang' => $dh->ma_don_hang,
            'khach_hang' => $dh->ten_nguoi_nhan ?: ($dh->user?->name ?? 'Khách vãng lai'),
            'cua_hang' => $dh->cuaHang?->ten_cua_hang ?? 'N/A',
            'tong_thanhtoan' => (float) $dh->tong_thanhtoan,
            'trang_thai_don_hang' => $dh->trang_thai_don_hang,
            'created_at' => $dh->created_at?->format('H:i d/m/Y'),
        ]);

        return Inertia::render('admin/Dashboard', [
            'metrics' => [
                'tong_doanh_thu' => $tongDoanhThu,
                'doanh_thu_hom_nay' => $doanhThuHomNay,
                'doanh_thu_thang_nay' => $doanhThuThangNay,
                'don_hang_hom_nay' => $donHangHomNay,
                'don_cho_xac_nhan' => $donChoXacNhan,
                'don_hoan_thanh' => $donHoanThanh,
                'don_da_huy' => $donDaHuy,
                'so_lo_het_han' => $soLoHetHan,
                'so_lo_can_date' => $soLoCanDate,
                'tong_tien_xuat_huy' => $tongTienXuatHuy,
                'tong_san_pham' => SanPham::count(),
                'tong_chi_nhanh' => $scopedStoreId ? 1 : CuaHang::count(),
            ],
            'chart_revenue' => $doanhThu7Ngay,
            'stores_revenue' => $doanhThuChiNhanh,
            'top_products' => $topSanPham,
            'recent_orders' => $donHangMoi,
            'scope' => [
                'is_manager' => (bool) $isScopedManager,
                'store_id' => $scopedStoreId,
                'store_name' => $scopedStore?->ten_cua_hang,
            ],
        ]);
    }
}
