<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSanPham;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    /**
     * Danh sách đánh giá sản phẩm của khách hàng.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $rating = $request->input('rating');
        $status = $request->input('status');

        $query = DanhGiaSanPham::with(['user:id,name,email,avatar', 'sanPham:id,ten_san_pham,duong_dan_seo,hinh_anh_chinh']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('noi_dung', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sanPham', function ($pq) use ($search) {
                        $pq->where('ten_san_pham', 'like', "%{$search}%");
                    });
            });
        }

        if ($rating) {
            $query->where('so_sao', (int) $rating);
        }

        if ($status !== null && $status !== '') {
            $query->where('trang_thai', (int) $status);
        }

        $reviews = $query->latest('id')->paginate(15)->withQueryString();

        return Inertia::render('admin/reviews/Index', [
            'reviews' => $reviews,
            'filters' => [
                'search' => $search,
                'rating' => $rating,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Bật/tắt trạng thái hiển thị của đánh giá (ẩn đánh giá spam/xấu).
     */
    public function toggle(DanhGiaSanPham $review): RedirectResponse
    {
        $review->update([
            'trang_thai' => $review->trang_thai === 1 ? 0 : 1,
        ]);

        return back()->with('success', 'Đã thay đổi trạng thái hiển thị đánh giá!');
    }

    /**
     * Xóa đánh giá spam.
     */
    public function destroy(DanhGiaSanPham $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Đã xóa đánh giá thành công!');
    }
}
