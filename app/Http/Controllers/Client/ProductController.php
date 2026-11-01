<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\SanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = CuaHang::layIdCuaHangHienTai($request);

        $query = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->with(['danhMuc', 'giaTheoCuaHang']);

        // Tìm kiếm theo từ khóa
        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('san_pham.ten_san_pham', 'like', "%{$keyword}%")
                    ->orWhere('san_pham.mo_ta', 'like', "%{$keyword}%")
                    ->orWhere('san_pham.ma_va_vach', 'like', "%{$keyword}%");
            });
        }

        // Lọc theo danh mục
        if ($request->filled('category')) {
            $categorySlug = $request->input('category');
            $category = DanhMuc::where('duong_dan_seo', $categorySlug)->first();
            if ($category) {
                // Lấy cả danh mục con nếu có
                $catIds = [$category->id];
                $subCatIds = DanhMuc::where('danh_muc_cha_id', $category->id)->pluck('id')->toArray();
                $catIds = array_merge($catIds, $subCatIds);

                $query->whereIn('san_pham.danh_muc_id', $catIds);
            }
        }

        // Lọc theo khoảng giá (ưu tiên giá của chi nhánh)
        if ($request->filled('min_price')) {
            $minPrice = (float) $request->input('min_price');
            $query->whereRaw('COALESCE(gch.gia_ban, san_pham.gia_ban) >= ?', [$minPrice]);
        }
        if ($request->filled('max_price')) {
            $maxPrice = (float) $request->input('max_price');
            $query->whereRaw('COALESCE(gch.gia_ban, san_pham.gia_ban) <= ?', [$maxPrice]);
        }

        // Lọc theo thực phẩm tươi sống
        if ($request->boolean('is_fresh')) {
            $query->where('san_pham.la_tuoi_song', true);
        }

        // Lọc theo điều kiện bảo quản
        if ($request->filled('preservation')) {
            $query->where('san_pham.dieu_kien_bao_quan', $request->input('preservation'));
        }

        // Sắp xếp theo giá của chi nhánh
        match ($request->input('sort')) {
            'price_asc' => $query->orderByRaw('COALESCE(gch.gia_ban, san_pham.gia_ban) ASC'),
            'price_desc' => $query->orderByRaw('COALESCE(gch.gia_ban, san_pham.gia_ban) DESC'),
            'name_asc' => $query->orderBy('san_pham.ten_san_pham', 'asc'),
            default => $query->latest('san_pham.created_at'),
        };

        $products = $query->paginate(12)->withQueryString();

        $categories = DanhMuc::whereNull('danh_muc_cha_id')
            ->with(['danhMucCon'])
            ->get();

        $stores = CuaHang::where('trang_thai', 1)->get();

        return Inertia::render('client/Products', [
            'products' => $products,
            'categories' => $categories,
            'stores' => $stores,
            'selectedStoreId' => $storeId,
            'filters' => $request->only(['q', 'category', 'min_price', 'max_price', 'is_fresh', 'preservation', 'sort']),
        ]);
    }

    public function show(string $slug, Request $request): Response
    {
        $storeId = CuaHang::layIdCuaHangHienTai($request);

        $product = SanPham::where('duong_dan_seo', $slug)
            ->with([
                'danhMuc.danhMucCha',
                'hinhAnhs',
                'giaTheoCuaHang.cuaHang',
                'tonKhos.cuaHang',
                'danhGias.user',
            ])
            ->firstOrFail();

        $product->apDungGiaCuaHang($storeId);

        // Sản phẩm liên quan cùng danh mục
        $relatedProducts = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->where('san_pham.danh_muc_id', $product->danh_muc_id)
            ->where('san_pham.id', '!=', $product->id)
            ->with(['danhMuc'])
            ->latest('san_pham.created_at')
            ->limit(4)
            ->get();

        $stores = CuaHang::where('trang_thai', 1)->get();

        return Inertia::render('client/ProductDetail', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'stores' => $stores,
            'selectedStoreId' => $storeId,
        ]);
    }

    public function category(string $slug, Request $request): Response
    {
        $category = DanhMuc::where('duong_dan_seo', $slug)
            ->with(['danhMucCha', 'danhMucCon'])
            ->firstOrFail();

        // Gán tham số category và gọi hàm index
        $request->merge(['category' => $slug]);

        $response = $this->index($request);

        return Inertia::render('client/Category', array_merge(
            $response->toResponse($request)->getOriginalContent()['page']['props'],
            ['currentCategory' => $category]
        ));
    }

    /**
     * API tìm kiếm nhanh gợi ý sản phẩm khi gõ ký tự
     */
    public function searchSuggestions(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('q', ''));
        if ($keyword === '') {
            return response()->json(['products' => []]);
        }

        $storeId = CuaHang::layIdCuaHangHienTai($request);

        $products = SanPham::dangKinhDoanh()
            ->voiGiaCuaHang($storeId)
            ->where(function ($q) use ($keyword) {
                $q->where('san_pham.ten_san_pham', 'like', "%{$keyword}%")
                    ->orWhere('san_pham.mo_ta', 'like', "%{$keyword}%")
                    ->orWhere('san_pham.ma_va_vach', 'like', "%{$keyword}%");
            })
            ->with(['danhMuc'])
            ->limit(6)
            ->get();

        $data = $products->map(function ($item) {
            return [
                'id' => $item->id,
                'ten_san_pham' => $item->ten_san_pham,
                'duong_dan_seo' => $item->duong_dan_seo,
                'hinh_anh' => $item->hinh_anh,
                'gia_ban' => (float) $item->gia_ban,
                'gia_giam' => $item->gia_giam !== null ? (float) $item->gia_giam : null,
                'don_vi_tinh' => $item->don_vi_tinh,
                'ten_danh_muc' => $item->danhMuc?->ten_danh_muc,
            ];
        });

        return response()->json(['products' => $data]);
    }
}
