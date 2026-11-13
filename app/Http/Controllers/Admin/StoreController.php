<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = CuaHang::withCount(['donHangs', 'users']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ten_cua_hang', 'like', "%{$search}%")
                    ->orWhere('dia_chi', 'like', "%{$search}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$search}%");
            });
        }

        $stores = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('admin/stores/Index', [
            'stores' => $stores,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ten_cua_hang' => ['required', 'string', 'max:255'],
            'so_dien_thoai' => ['required', 'string', 'max:20'],
            'dia_chi' => ['required', 'string', 'max:500'],
            'gio_mo_cua' => ['nullable', 'string', 'max:20'],
            'gio_dong_cua' => ['nullable', 'string', 'max:20'],
            'kinh_do' => ['nullable', 'numeric'],
            'vi_do' => ['nullable', 'numeric'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        CuaHang::create($validated);

        return back()->with('success', 'Đã thêm chi nhánh mới thành công!');
    }

    public function update(Request $request, CuaHang $cuaHang): RedirectResponse
    {
        $validated = $request->validate([
            'ten_cua_hang' => ['required', 'string', 'max:255'],
            'so_dien_thoai' => ['required', 'string', 'max:20'],
            'dia_chi' => ['required', 'string', 'max:500'],
            'gio_mo_cua' => ['nullable', 'string', 'max:20'],
            'gio_dong_cua' => ['nullable', 'string', 'max:20'],
            'kinh_do' => ['nullable', 'numeric'],
            'vi_do' => ['nullable', 'numeric'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        $cuaHang->update($validated);

        return back()->with('success', 'Đã cập nhật thông tin chi nhánh thành công!');
    }

    public function destroy(CuaHang $cuaHang): RedirectResponse
    {
        if ($cuaHang->donHangs()->exists() || $cuaHang->users()->exists()) {
            // Đổi trạng thái sang ngừng hoạt động thay vì xóa cứng
            $cuaHang->update(['trang_thai' => 0]);

            return back()->with('success', 'Chi nhánh có dữ liệu liên quan nên đã được chuyển sang trạng thái Tạm đóng cửa!');
        }

        $cuaHang->delete();

        return back()->with('success', 'Đã xóa chi nhánh thành công!');
    }
}
