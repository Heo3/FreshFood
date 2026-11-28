<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    /**
     * Danh sách banner quảng cáo trang chủ.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = Banner::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tieu_de', 'like', "%{$search}%")
                    ->orWhere('phu_de', 'like', "%{$search}%")
                    ->orWhere('duong_dan', 'like', "%{$search}%");
            });
        }

        $banners = $query->orderBy('thu_tu', 'asc')->latest('id')->paginate(10)->withQueryString();

        return Inertia::render('admin/banners/Index', [
            'banners' => $banners,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Tạo banner mới.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tieu_de' => ['required', 'string', 'max:255'],
            'phu_de' => ['nullable', 'string', 'max:255'],
            'hinh_anh' => ['nullable'],
            'duong_dan' => ['nullable', 'string', 'max:500'],
            'nhan_nut' => ['nullable', 'string', 'max:50'],
            'vi_tri' => ['nullable', 'string', 'max:50'],
            'thu_tu' => ['nullable', 'integer', 'min:0'],
            'trang_thai' => ['required', 'in:0,1'],
        ]);

        if ($request->hasFile('hinh_anh')) {
            $validated['hinh_anh'] = app(CloudinaryService::class)->upload($request->file('hinh_anh'), 'banners')
                ?? Storage::url($request->file('hinh_anh')->store('banners', 'public'));
        }

        if (empty($validated['hinh_anh'])) {
            return back()->withErrors(['hinh_anh' => 'Vui lòng cung cấp link ảnh hoặc tải ảnh lên.']);
        }

        Banner::create([
            'tieu_de' => $validated['tieu_de'],
            'phu_de' => $validated['phu_de'] ?? null,
            'hinh_anh' => $validated['hinh_anh'],
            'duong_dan' => $validated['duong_dan'] ?? '/san-pham',
            'nhan_nut' => $validated['nhan_nut'] ?? 'Mua Ngay',
            'vi_tri' => $validated['vi_tri'] ?? 'home_slider',
            'thu_tu' => $validated['thu_tu'] ?? 0,
            'trang_thai' => (int) $validated['trang_thai'],
        ]);

        return back()->with('success', 'Đã thêm banner quảng cáo mới thành công!');
    }

    /**
     * Cập nhật banner.
     */
    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'tieu_de' => ['required', 'string', 'max:255'],
            'phu_de' => ['nullable', 'string', 'max:255'],
            'hinh_anh' => ['nullable'],
            'duong_dan' => ['nullable', 'string', 'max:500'],
            'nhan_nut' => ['nullable', 'string', 'max:50'],
            'vi_tri' => ['nullable', 'string', 'max:50'],
            'thu_tu' => ['nullable', 'integer', 'min:0'],
            'trang_thai' => ['required', 'in:0,1'],
        ]);

        if ($request->hasFile('hinh_anh')) {
            $validated['hinh_anh'] = app(CloudinaryService::class)->upload($request->file('hinh_anh'), 'banners')
                ?? Storage::url($request->file('hinh_anh')->store('banners', 'public'));
        }

        if (empty($validated['hinh_anh'])) {
            $validated['hinh_anh'] = $banner->hinh_anh;
        }

        $banner->update([
            'tieu_de' => $validated['tieu_de'],
            'phu_de' => $validated['phu_de'] ?? null,
            'hinh_anh' => $validated['hinh_anh'],
            'duong_dan' => $validated['duong_dan'] ?? '/san-pham',
            'nhan_nut' => $validated['nhan_nut'] ?? 'Mua Ngay',
            'vi_tri' => $validated['vi_tri'] ?? 'home_slider',
            'thu_tu' => $validated['thu_tu'] ?? 0,
            'trang_thai' => (int) $validated['trang_thai'],
        ]);

        return back()->with('success', 'Đã cập nhật banner thành công!');
    }

    /**
     * Xóa banner.
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return back()->with('success', 'Đã xóa banner thành công!');
    }

    /**
     * Bật/tắt trạng thái hiển thị banner.
     */
    public function toggle(Banner $banner): RedirectResponse
    {
        $banner->update([
            'trang_thai' => $banner->trang_thai === 1 ? 0 : 1,
        ]);

        return back()->with('success', 'Đã thay đổi trạng thái hiển thị banner!');
    }
}
