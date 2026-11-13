<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSanPham;
use App\Models\DonHang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Lưu đánh giá và bình luận sản phẩm của khách hàng.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'san_pham_id' => ['required', 'exists:san_pham,id'],
            'so_sao' => ['required', 'integer', 'min:1', 'max:5'],
            'noi_dung' => ['nullable', 'string', 'max:1000'],
            'don_hang_id' => ['nullable', 'exists:don_hang,id'],
        ]);

        $user = $request->user();

        // Kiểm tra nếu có don_hang_id thì đơn hàng phải thuộc về user này
        if (! empty($validated['don_hang_id'])) {
            $donHang = DonHang::where('id', $validated['don_hang_id'])
                ->where('user_id', $user->id)
                ->first();

            if (! $donHang) {
                return back()->withErrors(['error' => 'Đơn hàng không hợp lệ.']);
            }
        }

        // Tạo hoặc cập nhật đánh giá của khách hàng cho sản phẩm này
        DanhGiaSanPham::updateOrCreate(
            [
                'user_id' => $user->id,
                'san_pham_id' => $validated['san_pham_id'],
                'don_hang_id' => $validated['don_hang_id'] ?? null,
            ],
            [
                'so_sao' => $validated['so_sao'],
                'noi_dung' => $validated['noi_dung'] ?? '',
                'trang_thai' => 1,
            ]
        );

        return back()->with('success', 'Cảm ơn bạn đã gửi đánh giá sản phẩm!');
    }
}
