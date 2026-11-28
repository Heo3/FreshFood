<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\DanhMuc;
use App\Models\GiaSanPhamCuaHang;
use App\Models\HinhAnhSanPham;
use App\Models\SanPham;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->withCount('hinhAnhs')
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
            'hinh_anh_chinh' => ['nullable'],
            'hinh_anh_phu' => ['nullable', 'array'],
            'hinh_anh_phu.*' => ['nullable'],
            'anh_chinh_index' => ['nullable', 'integer'],
            'la_tuoi_song' => ['boolean'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        if (empty($validated['duong_dan_seo'])) {
            $validated['duong_dan_seo'] = Str::slug($validated['ten_san_pham']).'-'.time();
        }

        if (empty($validated['ma_va_vach'])) {
            $validated['ma_va_vach'] = 'SP'.str_pad((string) (SanPham::max('id') + 1), 6, '0', STR_PAD_LEFT);
        }

        $validated['dieu_kien_bao_quan'] = $this->normalizeStorageCondition($validated['dieu_kien_bao_quan'] ?? null);

        // Thu thập toàn bộ ảnh được gửi lên
        $collectedImages = [];

        // 1. Ảnh chính ban đầu nếu có tải lên hoặc nhập URL
        if ($request->hasFile('hinh_anh_chinh')) {
            $mainUrl = $this->handleImageSource($request->file('hinh_anh_chinh'));
            if ($mainUrl) {
                $collectedImages[] = $mainUrl;
            }
        } elseif ($request->filled('hinh_anh_chinh') && is_string($request->input('hinh_anh_chinh'))) {
            $collectedImages[] = trim($request->input('hinh_anh_chinh'));
        }

        // 2. Danh sách ảnh upload nhiều
        if ($request->hasFile('hinh_anh_phu')) {
            foreach ($request->file('hinh_anh_phu') as $file) {
                if ($file instanceof UploadedFile) {
                    $url = $this->handleImageSource($file);
                    if ($url) {
                        $collectedImages[] = $url;
                    }
                }
            }
        } elseif ($request->filled('hinh_anh_phu') && is_array($request->input('hinh_anh_phu'))) {
            foreach ($request->input('hinh_anh_phu') as $item) {
                if (is_string($item) && ! empty(trim($item))) {
                    $collectedImages[] = trim($item);
                }
            }
        }

        $collectedImages = array_values(array_unique($collectedImages));

        $primaryIndex = (int) $request->input('anh_chinh_index', 0);
        if (! isset($collectedImages[$primaryIndex]) && ! empty($collectedImages)) {
            $primaryIndex = 0;
        }

        $primaryImageUrl = $collectedImages[$primaryIndex] ?? null;
        $validated['hinh_anh_chinh'] = $primaryImageUrl;

        unset($validated['hinh_anh_phu'], $validated['anh_chinh_index']);

        $product = SanPham::create($validated);

        // Lưu danh sách thư viện ảnh vào bảng hinh_anh_san_pham
        foreach ($collectedImages as $idx => $imgUrl) {
            HinhAnhSanPham::create([
                'san_pham_id' => $product->id,
                'duong_dan_anh' => $imgUrl,
                'thu_tu' => $idx + 1,
                'la_anh_chinh' => ($idx === $primaryIndex),
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', "Đã tạo sản phẩm {$product->ten_san_pham} thành công!");
    }

    public function edit(SanPham $sanPham): Response
    {
        $sanPham->load(['danhMuc', 'giaTheoCuaHang.cuaHang', 'hinhAnhs']);
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
            'hinh_anh_chinh' => ['nullable'],
            'hinh_anh_phu' => ['nullable', 'array'],
            'hinh_anh_phu.*' => ['nullable'],
            'la_tuoi_song' => ['boolean'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        if (empty($validated['duong_dan_seo'])) {
            $validated['duong_dan_seo'] = Str::slug($validated['ten_san_pham']);
        }

        $validated['dieu_kien_bao_quan'] = $this->normalizeStorageCondition($validated['dieu_kien_bao_quan'] ?? null);

        // Xử lý các ảnh mới được tải lên
        $newImages = [];
        if ($request->hasFile('hinh_anh_phu')) {
            foreach ($request->file('hinh_anh_phu') as $file) {
                if ($file instanceof UploadedFile) {
                    $url = $this->handleImageSource($file);
                    if ($url) {
                        $newImages[] = $url;
                    }
                }
            }
        }

        // Nếu có upload ảnh chính trực tiếp
        if ($request->hasFile('hinh_anh_chinh')) {
            $mainUrl = $this->handleImageSource($request->file('hinh_anh_chinh'));
            if ($mainUrl) {
                $validated['hinh_anh_chinh'] = $mainUrl;
                HinhAnhSanPham::where('san_pham_id', $sanPham->id)->update(['la_anh_chinh' => false]);
                HinhAnhSanPham::create([
                    'san_pham_id' => $sanPham->id,
                    'duong_dan_anh' => $mainUrl,
                    'thu_tu' => 0,
                    'la_anh_chinh' => true,
                ]);
            }
        } elseif ($request->filled('hinh_anh_chinh') && is_string($request->input('hinh_anh_chinh'))) {
            $validated['hinh_anh_chinh'] = trim($request->input('hinh_anh_chinh'));
        }

        // Thêm các ảnh phụ mới vào thư viện
        $currentMaxThuTu = (int) $sanPham->hinhAnhs()->max('thu_tu');
        foreach ($newImages as $idx => $imgUrl) {
            HinhAnhSanPham::create([
                'san_pham_id' => $sanPham->id,
                'duong_dan_anh' => $imgUrl,
                'thu_tu' => $currentMaxThuTu + $idx + 1,
                'la_anh_chinh' => false,
            ]);
        }

        // Nếu sản phẩm chưa có ảnh chính mà có ảnh trong thư viện, lấy ảnh đầu tiên làm ảnh chính
        if (empty($validated['hinh_anh_chinh'])) {
            $firstImg = $sanPham->hinhAnhs()->first();
            if ($firstImg) {
                $firstImg->update(['la_anh_chinh' => true]);
                $validated['hinh_anh_chinh'] = $firstImg->duong_dan_anh;
            }
        }

        unset($validated['hinh_anh_phu'], $validated['anh_chinh_index']);

        $sanPham->update($validated);

        return redirect()->route('admin.products.index')->with('success', "Đã cập nhật sản phẩm {$sanPham->ten_san_pham} thành công!");
    }

    public function destroy(Request $request, SanPham $sanPham): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Chỉ Quản trị viên cấp cao (Admin) mới có quyền xóa sản phẩm khỏi hệ thống.');
        }

        // Xóa các file ảnh liên quan khỏi storage
        foreach ($sanPham->hinhAnhs as $img) {
            if (str_starts_with($img->duong_dan_anh, '/storage/')) {
                $storagePath = str_replace('/storage/', '', $img->duong_dan_anh);
                Storage::disk('public')->delete($storagePath);
            }
        }

        $sanPham->delete();

        return back()->with('success', 'Đã xóa sản phẩm thành công!');
    }

    public function deleteImage(HinhAnhSanPham $hinhAnh): RedirectResponse
    {
        $productId = $hinhAnh->san_pham_id;
        $product = SanPham::findOrFail($productId);

        if (str_starts_with($hinhAnh->duong_dan_anh, '/storage/')) {
            $storagePath = str_replace('/storage/', '', $hinhAnh->duong_dan_anh);
            Storage::disk('public')->delete($storagePath);
        }

        $wasPrimary = $hinhAnh->la_anh_chinh;

        if ($hinhAnh->duong_dan_anh) {
            app(CloudinaryService::class)->deleteByUrl($hinhAnh->duong_dan_anh);
        }

        $hinhAnh->delete();

        if ($wasPrimary) {
            $next = HinhAnhSanPham::where('san_pham_id', $productId)->first();
            if ($next) {
                $next->update(['la_anh_chinh' => true]);
                $product->update(['hinh_anh_chinh' => $next->duong_dan_anh]);
            } else {
                $product->update(['hinh_anh_chinh' => null]);
            }
        }

        return back()->with('success', 'Đã xóa ảnh khỏi thư viện!');
    }

    public function setPrimaryImage(HinhAnhSanPham $hinhAnh): RedirectResponse
    {
        $product = SanPham::findOrFail($hinhAnh->san_pham_id);

        HinhAnhSanPham::where('san_pham_id', $product->id)->update(['la_anh_chinh' => false]);
        $hinhAnh->update(['la_anh_chinh' => true]);
        $product->update(['hinh_anh_chinh' => $hinhAnh->duong_dan_anh]);

        return back()->with('success', 'Đã đặt ảnh này làm ảnh chính của sản phẩm!');
    }

    /**
     * Lưu trữ một file hoặc URL ảnh vào hệ thống và trả về đường dẫn public
     */
    protected function handleImageSource(UploadedFile|string|null $imageSource): ?string
    {
        if (! $imageSource) {
            return null;
        }

        if ($imageSource instanceof UploadedFile) {
            /** @var CloudinaryService $cloudinary */
            $cloudinary = app(CloudinaryService::class);
            if ($cloudinary->isConfigured()) {
                $cloudUrl = $cloudinary->upload($imageSource, 'products');
                if ($cloudUrl) {
                    return $cloudUrl;
                }
            }

            $path = $imageSource->store('products', 'public');

            return Storage::url($path);
        }

        if (is_string($imageSource) && ! empty(trim($imageSource))) {
            return trim($imageSource);
        }

        return null;
    }

    /**
     * Chuẩn hóa điều kiện bảo quản về giá trị enum hợp lệ ('thuong', 'mat', 'dong_lanh').
     */
    protected function normalizeStorageCondition(?string $condition): string
    {
        if (empty($condition)) {
            return 'thuong';
        }

        $lower = mb_strtolower(trim($condition));

        if (in_array($lower, ['thuong', 'mat', 'dong_lanh'], true)) {
            return $lower;
        }

        if (str_contains($lower, 'đông') || str_contains($lower, 'dong') || str_contains($lower, '-18')) {
            return 'dong_lanh';
        }

        if (str_contains($lower, 'phòng') || str_contains($lower, 'phong') || str_contains($lower, 'thường') || str_contains($lower, 'thuong') || str_contains($lower, 'khô') || str_contains($lower, 'kho')) {
            return 'thuong';
        }

        if (str_contains($lower, 'mát') || str_contains($lower, 'mat') || str_contains($lower, 'lạnh') || str_contains($lower, 'lanh') || str_contains($lower, 'tủ')) {
            return 'mat';
        }

        return 'thuong';
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
