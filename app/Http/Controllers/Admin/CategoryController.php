<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = DanhMuc::with(['danhMucCha'])
            ->withCount('sanPhams');

        if ($search) {
            $query->where('ten_danh_muc', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $parentCategories = DanhMuc::whereNull('danh_muc_cha_id')->get(['id', 'ten_danh_muc']);

        return Inertia::render('admin/categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ten_danh_muc' => ['required', 'string', 'max:255'],
            'duong_dan_seo' => ['nullable', 'string', 'max:255', 'unique:danh_muc,duong_dan_seo'],
            'hinh_anh' => ['nullable', 'string', 'max:500'],
            'danh_muc_cha_id' => ['nullable', 'exists:danh_muc,id'],
        ]);

        if (empty($validated['duong_dan_seo'])) {
            $validated['duong_dan_seo'] = Str::slug($validated['ten_danh_muc']).'-'.time();
        }

        DanhMuc::create($validated);

        return back()->with('success', 'Đã tạo danh mục thành công!');
    }

    public function update(Request $request, DanhMuc $danhMuc): RedirectResponse
    {
        $validated = $request->validate([
            'ten_danh_muc' => ['required', 'string', 'max:255'],
            'duong_dan_seo' => ['nullable', 'string', 'max:255', 'unique:danh_muc,duong_dan_seo,'.$danhMuc->id],
            'hinh_anh' => ['nullable', 'string', 'max:500'],
            'danh_muc_cha_id' => ['nullable', 'different:id', 'exists:danh_muc,id'],
        ]);

        if (empty($validated['duong_dan_seo'])) {
            $validated['duong_dan_seo'] = Str::slug($validated['ten_danh_muc']);
        }

        $danhMuc->update($validated);

        return back()->with('success', 'Đã cập nhật danh mục thành công!');
    }

    public function destroy(Request $request, DanhMuc $danhMuc): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Chỉ Quản trị viên cấp cao (Admin) mới có quyền xóa danh mục sản phẩm.');
        }

        if ($danhMuc->sanPhams()->exists()) {
            return back()->with('error', 'Không thể xóa danh mục đang có sản phẩm kinh doanh!');
        }

        if ($danhMuc->danhMucCon()->exists()) {
            return back()->with('error', 'Không thể xóa danh mục đang có danh mục con!');
        }

        $danhMuc->delete();

        return back()->with('success', 'Đã xóa danh mục thành công!');
    }
}
