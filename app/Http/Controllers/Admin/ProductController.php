<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\GiaSanPhamCuaHang;
use App\Models\SanPham;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $categoryId = $request->input('danh_muc_id');
        $isFresh = $request->input('la_tuoi_song');
        $status = $request->input('trang_thai');

        $query = SanPham::with(['danhMuc', 'giaTheoCuaHang.cuaHang', 'tonKhos'])
            ->withSum('tonKhos', 'so_luong_ton');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ten_san_pham', 'like', "%{$search}%")
                    ->orWhere('ma_va_vach', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('danh_muc_id', $categoryId);
        }

        if ($isFresh !== null && $isFresh !== '') {
            $query->where('la_tuoi_song', (bool) $isFresh);
        }

        if ($status !== null && $status !== '') {
            $query->where('trang_thai', (int) $status);
        }

        $products = $query->orderBy('id', 'desc')->paginate(12)->withQueryString();
        $categories = DanhMuc::all(['id', 'ten_danh_muc']);
        $stores = CuaHang::where('trang_thai', 1)->get(['id', 'ten_cua_hang']);

        return Inertia::render('admin/products/Index', [
            'products' => $products,
            'categories' => $categories,
            'stores' => $stores,
            'filters' => [
                'search' => $search,
                'danh_muc_id' => $categoryId,
                'la_tuoi_song' => $isFresh,
                'trang_thai' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $categories = DanhMuc::all(['id', 'ten_danh_muc']);
        $stores = CuaHang::where('trang_thai', 1)->get(['id', 'ten_cua_hang']);

        return Inertia::render('admin/products/Create', [
            'categories' => $categories,
            'stores' => $stores,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ten_san_pham' => ['required', 'string', 'max:255'],
            'ma_va_vach' => ['nullable', 'string', 'max:100', 'unique:san_pham,ma_va_vach'],
            'duong_dan_seo' => ['nullable', 'string', 'max:255', 'unique:san_pham,duong_dan_seo'],
            'danh_muc_id' => ['required', 'exists:danh_muc,id'],
            'gia_ban' => ['required', 'numeric', 'min:0'],
            'gia_giam' => ['nullable', 'numeric', 'min:0', 'lt:gia_ban'],
            'don_vi_tinh' => ['required', 'string', 'max:50'],
            'quy_cach' => ['nullable', 'string', 'max:100'],
            'dieu_kien_bao_quan' => ['nullable', 'string', 'max:255'],
            'han_su_dung_ngay' => ['nullable', 'integer', 'min:1'],
            'mo_ta' => ['nullable', 'string'],
            'hinh_anh_chinh' => ['nullable', 'string', 'max:500'],
            'la_tuoi_song' => ['boolean'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        if (empty($validated['duong_dan_seo'])) {
            $validated['duong_dan_seo'] = Str::slug($validated['ten_san_pham']).'-'.time();
        }

        if (empty($validated['ma_va_vach'])) {
            $validated['ma_va_vach'] = 'SP'.str_pad((string) (SanPham::max('id') + 1), 6, '0', STR_PAD_LEFT);
        }

        $product = SanPham::create($validated);

        return redirect()->route('admin.products.index')->with('success', "Đã tạo sản phẩm {$product->ten_san_pham} thành công!");
    }

    public function edit(SanPham $sanPham): Response
    {
        $sanPham->load(['danhMuc', 'giaTheoCuaHang.cuaHang']);
        $categories = DanhMuc::all(['id', 'ten_danh_muc']);
        $stores = CuaHang::where('trang_thai', 1)->get(['id', 'ten_cua_hang']);

        return Inertia::render('admin/products/Edit', [
            'product' => $sanPham,
            'categories' => $categories,
            'stores' => $stores,
        ]);
    }

    public function update(Request $request, SanPham $sanPham): RedirectResponse
    {
        $validated = $request->validate([
            'ten_san_pham' => ['required', 'string', 'max:255'],
            'ma_va_vach' => ['nullable', 'string', 'max:100', 'unique:san_pham,ma_va_vach,'.$sanPham->id],
            'duong_dan_seo' => ['nullable', 'string', 'max:255', 'unique:san_pham,duong_dan_seo,'.$sanPham->id],
            'danh_muc_id' => ['required', 'exists:danh_muc,id'],
            'gia_ban' => ['required', 'numeric', 'min:0'],
            'gia_giam' => ['nullable', 'numeric', 'min:0', 'lt:gia_ban'],
            'don_vi_tinh' => ['required', 'string', 'max:50'],
            'quy_cach' => ['nullable', 'string', 'max:100'],
            'dieu_kien_bao_quan' => ['nullable', 'string', 'max:255'],
            'han_su_dung_ngay' => ['nullable', 'integer', 'min:1'],
            'mo_ta' => ['nullable', 'string'],
            'hinh_anh_chinh' => ['nullable', 'string', 'max:500'],
            'la_tuoi_song' => ['boolean'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        if (empty($validated['duong_dan_seo'])) {
            $validated['duong_dan_seo'] = Str::slug($validated['ten_san_pham']);
        }

        $sanPham->update($validated);

        return redirect()->route('admin.products.index')->with('success', "Đã cập nhật sản phẩm {$sanPham->ten_san_pham} thành công!");
    }

    public function destroy(Request $request, SanPham $sanPham): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Chỉ Quản trị viên cấp cao (Admin) mới có quyền xóa sản phẩm khỏi hệ thống.');
        }

        $sanPham->delete();

        return back()->with('success', 'Đã xóa sản phẩm thành công!');
    }

    public function updateBranchPrice(Request $request, SanPham $sanPham): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'cua_hang_id' => ['required', 'exists:cua_hang,id'],
            'gia_ban' => ['required', 'numeric', 'min:0'],
            'gia_giam' => ['nullable', 'numeric', 'min:0', 'lt:gia_ban'],
        ]);

        if ($user && $user->isQuanLy() && $user->store_id && (int) $validated['cua_hang_id'] !== (int) $user->store_id) {
            abort(403, 'Bạn chỉ có quyền điều chỉnh giá bán cho chi nhánh được phân công.');
        }

        GiaSanPhamCuaHang::updateOrCreate(
            [
                'san_pham_id' => $sanPham->id,
                'cua_hang_id' => $validated['cua_hang_id'],
            ],
            [
                'gia_ban' => $validated['gia_ban'],
                'gia_giam' => $validated['gia_giam'] ?? null,
            ]
        );

        return back()->with('success', 'Đã lưu cấu hình giá riêng cho chi nhánh!');
    }
}
