<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\PhieuXuat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isManager = $user && $user->isQuanLy() && $user->store_id;

        $storeId = $isManager ? (int) $user->store_id : $request->input('cua_hang_id');
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->format('Y-m-d'));

        // Query doanh thu theo khoảng thời gian
        $orderQuery = DonHang::whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao', 'dang_giao'])
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        if ($storeId) {
            $orderQuery->where('cua_hang_id', $storeId);
        }

        $tongDoanhThu = (float) $orderQuery->sum('tong_thanhtoan');
        $tongDonHang = $orderQuery->count();

        // Báo cáo tồn kho theo chi nhánh
        $storeInventoryQuery = CuaHang::withSum('tonKhos', 'so_luong_ton');
        if ($isManager) {
            $storeInventoryQuery->where('id', $user->store_id);
        }

        $tonKhoTheoChiNhanh = $storeInventoryQuery->get()
            ->map(fn ($ch) => [
                'id' => $ch->id,
                'ten_cua_hang' => $ch->ten_cua_hang,
                'tong_ton' => (float) ($ch->ton_khos_sum_so_luong_ton ?? 0),
            ]);

        // Thống kê hao hụt & hủy date theo chi nhánh
        $xuatHuyQuery = PhieuXuat::where('loai_xuat', PhieuXuat::LOAI_XUAT_HUY)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        if ($storeId) {
            $xuatHuyQuery->where('cua_hang_id', $storeId);
        }

        $tongTienXuatHuy = (float) $xuatHuyQuery->sum('tong_gia_tri');

        // Lô hàng cận hạn sử dụng hiện tại
        $loCanDateQuery = LoHang::with(['sanPham', 'cuaHang'])
            ->where('ngay_het_han', '<=', Carbon::now()->addDays(3))
            ->where('so_luong_con_lai', '>', 0);

        if ($storeId) {
            $loCanDateQuery->where('cua_hang_id', $storeId);
        }

        $danhSachLoCanDate = $loCanDateQuery->orderBy('ngay_het_han', 'asc')
            ->limit(10)
            ->get()
            ->map(fn ($lo) => [
                'id' => $lo->id,
                'so_lo' => $lo->ma_lo,
                'ma_lo' => $lo->ma_lo,
                'ten_san_pham' => $lo->sanPham?->ten_san_pham ?? 'N/A',
                'ten_cua_hang' => $lo->cuaHang?->ten_cua_hang ?? 'N/A',
                'so_luong_ton' => (float) $lo->so_luong_con_lai,
                'so_luong_con_lai' => (float) $lo->so_luong_con_lai,
                'ngay_het_han' => $lo->ngay_het_han?->format('d/m/Y'),
                'da_het_han' => $lo->ngay_het_han < Carbon::today(),
            ]);

        $stores = $isManager
            ? CuaHang::where('id', $user->store_id)->get(['id', 'ten_cua_hang'])
            : CuaHang::where('trang_thai', 1)->get(['id', 'ten_cua_hang']);

        $categories = DanhMuc::withCount('sanPhams')->get(['id', 'ten_danh_muc']);

        return Inertia::render('admin/reports/Index', [
            'stores' => $stores,
            'categories' => $categories,
            'metrics' => [
                'tong_doanh_thu' => $tongDoanhThu,
                'tong_don_hang' => $tongDonHang,
                'tong_tien_xuat_huy' => $tongTienXuatHuy,
            ],
            'inventory_by_store' => $tonKhoTheoChiNhanh,
            'near_expiry_lots' => $danhSachLoCanDate,
            'filters' => [
                'cua_hang_id' => $storeId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'is_scoped_manager' => $isManager,
        ]);
    }

    /**
     * Xuất báo cáo doanh thu ra file CSV (Excel UTF-8).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $isManager = $user && $user->isQuanLy() && $user->store_id;

        $storeId = $isManager ? (int) $user->store_id : $request->input('cua_hang_id');
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->format('Y-m-d'));

        $orderQuery = DonHang::with('cuaHang')
            ->whereIn('trang_thai_don_hang', ['hoan_thanh', 'da_giao', 'dang_giao'])
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        if ($storeId) {
            $orderQuery->where('cua_hang_id', $storeId);
        }

        $orders = $orderQuery->orderBy('id', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bao-cao-doanh-thu-'.$fromDate.'-den-'.$toDate.'.csv"',
        ];

        return response()->stream(function () use ($orders, $fromDate, $toDate) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['BÁO CÁO DOANH THU FRESHFOOD', "Từ ngày {$fromDate} đến ngày {$toDate}"]);
            fputcsv($handle, []);
            fputcsv($handle, [
                'Mã đơn hàng',
                'Chi nhánh',
                'Khách hàng',
                'Số điện thoại',
                'Tiền hàng',
                'Phí vận chuyển',
                'Giảm giá',
                'Thực thu',
                'Phương thức TT',
                'Trạng thái đơn',
                'Thời gian đặt',
            ]);

            $totalRevenue = 0;
            foreach ($orders as $o) {
                $totalRevenue += (float) $o->tong_thanhtoan;
                fputcsv($handle, [
                    $o->ma_don_hang,
                    $o->cuaHang?->ten_cua_hang ?? 'N/A',
                    $o->ten_nguoi_nhan,
                    $o->so_dien_thoai_nhan,
                    $o->tong_tien_hang,
                    $o->phi_giao_hang,
                    $o->tien_giam_gia,
                    $o->tong_thanhtoan,
                    $o->phuong_thuc_thanh_toan,
                    $o->trang_thai_don_hang,
                    $o->created_at?->format('d/m/Y H:i:s'),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['TỔNG DOANH THU', '', '', '', '', '', '', $totalRevenue]);

            fclose($handle);
        }, 200, $headers);
    }
}
