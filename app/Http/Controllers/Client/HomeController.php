<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\GioHang;
use App\Models\MaGiamGia;
use App\Models\SanPham;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = CuaHang::layIdCuaHangHienTai($request);
        $stores = CuaHang::where('trang_thai', 1)->get();

        $categories = DanhMuc::whereNull('danh_muc_cha_id')
            ->with(['danhMucCon'])
            ->get();

        // Sản phẩm Flash Sale có giá giảm (ưu tiên giá của chi nhánh được chọn)
        $flashSales = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->coGiamGia($storeId)
            ->with(['danhMuc'])
            ->latest('san_pham.created_at')
            ->limit(8)
            ->get();

        // Sản phẩm tươi mới về sáng nay (ưu tiên hàng tươi sống)
        $freshToday = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->tuoiSong()
            ->with(['danhMuc'])
            ->latest('san_pham.created_at')
            ->limit(8)
            ->get();

        // Thịt cá tươi ngon tuyển chọn
        $meatsAndSeafood = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->whereHas('danhMuc', function ($q) {
                $q->whereIn('duong_dan_seo', [
                    'thit-tuoi-trung-sach',
                    'thit-heo-sinh-hoc-vietgap',
                    'thit-bo-uc-my-thuong-hang',
                    'thit-gia-cam-tha-vuon',
                    'thuy-hai-san-tuoi-song',
                    'ca-hoi-nauy-tuoi-phi-le',
                    'tom-cua-muc-tuoi',
                ]);
            })
            ->with(['danhMuc'])
            ->latest('san_pham.created_at')
            ->limit(8)
            ->get();

        // Trái cây tươi & Rau củ
        $fruitsAndVeggies = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->whereHas('danhMuc', function ($q) {
                $q->whereIn('duong_dan_seo', [
                    'rau-cu-huu-co',
                    'rau-la-xanh-huu-co',
                    'cu-qua-tuoi-da-lat',
                    'trai-cay-tuoi-ngon',
                    'trai-cay-nhiet-doi-viet-nam',
                    'trai-cay-nhap-khau-cao-cap',
                ]);
            })
            ->with(['danhMuc'])
            ->latest('san_pham.created_at')
            ->limit(8)
            ->get();

        // Mã giảm giá đang hiệu lực
        $coupons = MaGiamGia::hieuLuc()->limit(4)->get();

        return Inertia::render('client/Home', [
            'stores' => $stores,
            'selectedStoreId' => $storeId,
            'categories' => $categories,
            'flashSales' => $flashSales,
            'freshToday' => $freshToday,
            'meatsAndSeafood' => $meatsAndSeafood,
            'fruitsAndVeggies' => $fruitsAndVeggies,
            'coupons' => $coupons,
        ]);
    }

    public function selectStore(Request $request)
    {
        $request->validate([
            'store_id' => ['required', 'integer', 'exists:cua_hang,id'],
        ]);

        $storeId = (int) $request->input('store_id');
        session(['selected_store_id' => $storeId]);
        cookie()->queue(cookie('freshfood_store_id', (string) $storeId, 60 * 24 * 30));

        // Nếu có yêu cầu làm mới giỏ hàng khi đổi chi nhánh, xóa sạch giỏ hàng trong CSDL
        $user = $request->user();
        $sessionId = $request->session()->getId();
        $cartQuery = GioHang::query();
        if ($user) {
            $cartQuery->where(function ($q) use ($user, $sessionId) {
                $q->where('user_id', $user->id)
                    ->orWhere('session_id', $sessionId);
            });
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        if ($request->boolean('clear_cart')) {
            $cartQuery->delete();
        } else {
            $cartQuery->update(['cua_hang_id' => $storeId]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'store_id' => $storeId,
                'message' => 'Đã chọn chi nhánh thành công.',
            ]);
        }

        return back()->with('success', 'Đã chuyển sang chi nhánh mới.');
    }
}
