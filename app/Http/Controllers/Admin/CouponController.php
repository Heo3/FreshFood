<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaGiamGia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('trang_thai');

        $query = MaGiamGia::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ma_khuyen_mai', 'like', "%{$search}%")
                    ->orWhere('ten_chuong_trinh', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('trang_thai', (int) $status);
        }

        $coupons = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('admin/coupons/Index', [
            'coupons' => $coupons,
            'filters' => [
                'search' => $search,
                'trang_thai' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khuyen_mai' => ['required', 'string', 'max:50', 'unique:ma_giam_gia,ma_khuyen_mai'],
            'ten_chuong_trinh' => ['required', 'string', 'max:255'],
            'loai_giam_gia' => ['required', 'in:phan_tram,so_tien'],
            'gia_tri_giam' => ['required', 'numeric', 'min:0'],
            'don_hang_toi_thieu' => ['nullable', 'numeric', 'min:0'],
            'giam_toi_da' => ['nullable', 'numeric', 'min:0'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['required', 'date', 'after_or_equal:ngay_bat_dau'],
            'so_luong' => ['required', 'integer', 'min:1'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        $validated['ma_khuyen_mai'] = strtoupper(trim($validated['ma_khuyen_mai']));
        $validated['da_su_dung'] = 0;

        MaGiamGia::create($validated);

        return back()->with('success', "Đã tạo mã giảm giá {$validated['ma_khuyen_mai']} thành công!");
    }

    public function update(Request $request, MaGiamGia $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khuyen_mai' => ['required', 'string', 'max:50', 'unique:ma_giam_gia,ma_khuyen_mai,'.$coupon->id],
            'ten_chuong_trinh' => ['required', 'string', 'max:255'],
            'loai_giam_gia' => ['required', 'in:phan_tram,so_tien'],
            'gia_tri_giam' => ['required', 'numeric', 'min:0'],
            'don_hang_toi_thieu' => ['nullable', 'numeric', 'min:0'],
            'giam_toi_da' => ['nullable', 'numeric', 'min:0'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['required', 'date', 'after_or_equal:ngay_bat_dau'],
            'so_luong' => ['required', 'integer', 'min:1'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        $validated['ma_khuyen_mai'] = strtoupper(trim($validated['ma_khuyen_mai']));

        $coupon->update($validated);

        return back()->with('success', "Đã cập nhật mã giảm giá {$coupon->ma_khuyen_mai} thành công!");
    }

    public function destroy(Request $request, MaGiamGia $coupon): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Chỉ Quản trị viên cấp cao (Admin) mới có quyền xóa mã giảm giá.');
        }

        $coupon->delete();

        return back()->with('success', 'Đã xóa mã giảm giá thành công!');
    }
}
