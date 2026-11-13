<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDoiTra;
use App\Models\DiaChiGiaoHang;
use App\Models\DonHang;
use App\Models\LichSuTichDiem;
use App\Models\PhieuDoiTra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $recentOrders = DonHang::where('user_id', $user->id)
            ->with(['chiTiets'])
            ->latest()
            ->limit(5)
            ->get();

        $totalOrders = DonHang::where('user_id', $user->id)->count();
        $totalSpent = DonHang::where('user_id', $user->id)
            ->where('trang_thai_don_hang', 'da_giao')
            ->sum('tong_thanhtoan');

        return Inertia::render('client/account/Index', [
            'recentOrders' => $recentOrders,
            'stats' => [
                'totalOrders' => $totalOrders,
                'totalSpent' => (float) $totalSpent,
                'points' => $user->loyalty_points,
            ],
        ]);
    }

    public function orders(Request $request): Response
    {
        $user = $request->user();

        $status = $request->input('status');

        $query = DonHang::where('user_id', $user->id)
            ->with(['chiTiets', 'cuaHang'])
            ->latest();

        if ($status && $status !== 'all') {
            if (in_array($status, ['da_giao', 'hoan_thanh'])) {
                $query->whereIn('trang_thai_don_hang', ['da_giao', 'hoan_thanh']);
            } else {
                $query->where('trang_thai_don_hang', $status);
            }
        }

        $orders = $query->paginate(10)->withQueryString();

        return Inertia::render('client/account/Orders', [
            'orders' => $orders,
            'currentStatus' => $status ?? 'all',
        ]);
    }

    public function orderDetail(Request $request, string $maDonHang): Response
    {
        $user = $request->user();

        $order = DonHang::where('user_id', $user->id)
            ->where('ma_don_hang', $maDonHang)
            ->with(['chiTiets.sanPham', 'cuaHang', 'maGiamGia', 'phieuDoiTras.chiTiets'])
            ->firstOrFail();

        return Inertia::render('client/account/OrderDetail', [
            'order' => $order,
        ]);
    }

    public function addresses(Request $request): Response
    {
        $user = $request->user();

        $addresses = DiaChiGiaoHang::where('user_id', $user->id)
            ->orderBy('la_mac_dinh', 'desc')
            ->latest()
            ->get();

        return Inertia::render('client/account/Addresses', [
            'addresses' => $addresses,
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $request->validate([
            'ten_nguoi_nhan' => ['required', 'string', 'max:100'],
            'so_dien_thoai_nhan' => ['required', 'string', 'max:15'],
            'dia_chi_chi_tiet' => ['required', 'string', 'max:255'],
            'phuong_xa' => ['required', 'string', 'max:100'],
            'quan_huyen' => ['required', 'string', 'max:100'],
            'tinh_thanh' => ['required', 'string', 'max:100'],
            'la_mac_dinh' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if ($request->boolean('la_mac_dinh')) {
            DiaChiGiaoHang::where('user_id', $user->id)->update(['la_mac_dinh' => false]);
        }

        DiaChiGiaoHang::create([
            'user_id' => $user->id,
            'ten_nguoi_nhan' => $request->input('ten_nguoi_nhan'),
            'so_dien_thoai_nhan' => $request->input('so_dien_thoai_nhan'),
            'dia_chi_chi_tiet' => $request->input('dia_chi_chi_tiet'),
            'phuong_xa' => $request->input('phuong_xa'),
            'quan_huyen' => $request->input('quan_huyen'),
            'tinh_thanh' => $request->input('tinh_thanh'),
            'la_mac_dinh' => $request->boolean('la_mac_dinh'),
        ]);

        return back()->with('success', 'Đã thêm địa chỉ giao hàng mới.');
    }

    public function deleteAddress(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        DiaChiGiaoHang::where('user_id', $user->id)->where('id', $id)->delete();

        return back()->with('success', 'Đã xóa địa chỉ.');
    }

    public function points(Request $request): Response
    {
        $user = $request->user();

        $history = LichSuTichDiem::where('user_id', $user->id)
            ->with(['donHang'])
            ->latest()
            ->paginate(15);

        return Inertia::render('client/account/Points', [
            'points' => $user->loyalty_points,
            'history' => $history,
        ]);
    }

    public function requestReturn(Request $request): RedirectResponse
    {
        $request->validate([
            'don_hang_id' => ['required', 'exists:don_hang,id'],
            'loai_doi_tra' => ['required', 'in:doi_hang,hoan_tien'],
            'ly_do' => ['required', 'string', 'min:10'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.san_pham_id' => ['required', 'exists:san_pham,id'],
            'items.*.so_luong' => ['required', 'numeric', 'min:0.1'],
            'items.*.don_gia' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $order = DonHang::where('user_id', $user->id)->findOrFail($request->input('don_hang_id'));

        $totalRefund = 0;
        foreach ($request->input('items') as $it) {
            $totalRefund += ((float) $it['don_gia']) * ((float) $it['so_luong']);
        }

        $phieu = PhieuDoiTra::create([
            'ma_phieu' => 'DT'.date('ymd').strtoupper(bin2hex(random_bytes(2))),
            'don_hang_id' => $order->id,
            'cua_hang_id' => $order->cua_hang_id,
            'user_id' => $user->id,
            'nguoi_tao_id' => $user->id,
            'loai_doi_tra' => $request->input('loai_doi_tra'),
            'tong_tien_hoan' => $totalRefund,
            'trang_thai' => 0, // Chờ duyệt
            'ly_do' => $request->input('ly_do'),
        ]);

        foreach ($request->input('items') as $it) {
            ChiTietDoiTra::create([
                'phieu_doi_tra_id' => $phieu->id,
                'san_pham_id' => $it['san_pham_id'],
                'so_luong' => $it['so_luong'],
                'don_gia' => $it['don_gia'],
                'thanh_tien' => ((float) $it['don_gia']) * ((float) $it['so_luong']),
                'tinh_trang_hang' => 'hu_hong_huy',
            ]);
        }

        return back()->with('success', 'Yêu cầu đổi/trả hàng đã được gửi thành công. Cửa hàng sẽ liên hệ xác nhận trong vòng 2h.');
    }

    public function cancelOrder(Request $request, DonHang $donHang): RedirectResponse
    {
        $user = $request->user();

        if ((int) $donHang->user_id !== (int) $user->id) {
            abort(403, 'Bạn không có quyền thao tác trên đơn hàng này.');
        }

        if ($donHang->trang_thai_don_hang === DonHang::TRANG_THAI_DANG_CHUAN_BI) {
            return back()->with('error', 'Đơn hàng đã được duyệt và đang chuẩn bị (đóng gói), không thể hủy đơn hàng.');
        }

        if ($donHang->trang_thai_don_hang === DonHang::TRANG_THAI_DA_HUY) {
            return back()->with('error', 'Đơn hàng này đã được hủy trước đó.');
        }

        if (! $donHang->coTheHuyBoiKhachHang()) {
            return back()->with('error', 'Đơn hàng chỉ có thể hủy trong thời gian chờ xác nhận.');
        }

        $validated = $request->validate([
            'ly_do' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $validated['ly_do'] ?? 'Khách hàng yêu cầu hủy đơn';

        DB::transaction(function () use ($donHang, $user, $reason) {
            // 1. Hoàn lại lượt sử dụng mã giảm giá nếu có
            if ($donHang->ma_giam_gia_id && $donHang->maGiamGia) {
                if ($donHang->maGiamGia->da_su_dung > 0) {
                    $donHang->maGiamGia->decrement('da_su_dung');
                }
            }

            // 2. Hoàn lại điểm tích lũy nếu đơn hàng có tiêu điểm
            $tieuDiemRecord = LichSuTichDiem::where('don_hang_id', $donHang->id)
                ->where('user_id', $user->id)
                ->where('loai_giao_dich', 'tieu_diem')
                ->first();

            $alreadyRefunded = LichSuTichDiem::where('don_hang_id', $donHang->id)
                ->where('loai_giao_dich', 'hoan_diem')
                ->exists();

            if (! $alreadyRefunded && $tieuDiemRecord && abs($tieuDiemRecord->so_diem_thay_doi) > 0) {
                $diemHoan = abs($tieuDiemRecord->so_diem_thay_doi);
                $user->increment('loyalty_points', $diemHoan);

                LichSuTichDiem::create([
                    'user_id' => $user->id,
                    'don_hang_id' => $donHang->id,
                    'loai_giao_dich' => 'hoan_diem',
                    'so_diem_thay_doi' => $diemHoan,
                    'so_diem_sau_giao_dich' => (int) $user->loyalty_points,
                    'ghi_chu' => "Hoàn lại điểm do hủy đơn hàng #{$donHang->ma_don_hang}",
                ]);
            }

            // 3. Trừ lại số điểm đã cộng (tích lũy) từ đơn hàng này khi hủy đơn
            $tichDiemRecord = LichSuTichDiem::where('don_hang_id', $donHang->id)
                ->where('user_id', $user->id)
                ->where('loai_giao_dich', 'tich_diem')
                ->first();

            $alreadyRevoked = LichSuTichDiem::where('don_hang_id', $donHang->id)
                ->where('loai_giao_dich', 'admin_dieu_chinh')
                ->where('so_diem_thay_doi', '<', 0)
                ->exists();

            if (! $alreadyRevoked && $tichDiemRecord && $tichDiemRecord->so_diem_thay_doi > 0) {
                $diemDaCong = $tichDiemRecord->so_diem_thay_doi;
                $user->loyalty_points = max(0, (int) $user->loyalty_points - $diemDaCong);
                $user->save();

                LichSuTichDiem::create([
                    'user_id' => $user->id,
                    'don_hang_id' => $donHang->id,
                    'loai_giao_dich' => 'admin_dieu_chinh',
                    'so_diem_thay_doi' => -$diemDaCong,
                    'so_diem_sau_giao_dich' => (int) $user->loyalty_points,
                    'ghi_chu' => "Thu hồi {$diemDaCong} điểm tích lũy do hủy đơn hàng #{$donHang->ma_don_hang}",
                ]);
            }

            // 4. Cập nhật trạng thái đơn hàng thành đã hủy (da_huy)
            $ghiChuHienTai = $donHang->ghi_chu ? $donHang->ghi_chu.' | ' : '';
            $donHang->update([
                'trang_thai_don_hang' => DonHang::TRANG_THAI_DA_HUY,
                'ghi_chu' => $ghiChuHienTai."[Khách hủy: {$reason}]",
            ]);
        });

        return back()->with('success', "Đơn hàng #{$donHang->ma_don_hang} đã được hủy thành công.");
    }
}
