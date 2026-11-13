<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NhaCungCap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = NhaCungCap::withCount(['phieuNhaps', 'loHangs']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ten_nha_cung_cap', 'like', "%{$search}%")
                    ->orWhere('ma_ncc', 'like', "%{$search}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('admin/suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_ncc' => ['required', 'string', 'max:50', 'unique:nha_cung_cap,ma_ncc'],
            'ten_nha_cung_cap' => ['required', 'string', 'max:255'],
            'nguoi_lien_he' => ['nullable', 'string', 'max:255'],
            'so_dien_thoai' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'dia_chi' => ['nullable', 'string', 'max:500'],
            'ma_so_thue' => ['nullable', 'string', 'max:50'],
            'ghi_chu' => ['nullable', 'string'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        $validated['ma_ncc'] = strtoupper(trim($validated['ma_ncc']));

        NhaCungCap::create($validated);

        return back()->with('success', "Đã thêm nhà cung cấp {$validated['ten_nha_cung_cap']} thành công!");
    }

    public function update(Request $request, NhaCungCap $nhaCungCap): RedirectResponse
    {
        $validated = $request->validate([
            'ma_ncc' => ['required', 'string', 'max:50', 'unique:nha_cung_cap,ma_ncc,'.$nhaCungCap->id],
            'ten_nha_cung_cap' => ['required', 'string', 'max:255'],
            'nguoi_lien_he' => ['nullable', 'string', 'max:255'],
            'so_dien_thoai' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'dia_chi' => ['nullable', 'string', 'max:500'],
            'ma_so_thue' => ['nullable', 'string', 'max:50'],
            'ghi_chu' => ['nullable', 'string'],
            'trang_thai' => ['integer', 'in:0,1'],
        ]);

        $validated['ma_ncc'] = strtoupper(trim($validated['ma_ncc']));

        $nhaCungCap->update($validated);

        return back()->with('success', "Đã cập nhật nhà cung cấp {$nhaCungCap->ten_nha_cung_cap} thành công!");
    }

    public function destroy(Request $request, NhaCungCap $nhaCungCap): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Chỉ Quản trị viên cấp cao (Admin) mới có quyền xóa nhà cung cấp.');
        }

        if ($nhaCungCap->phieuNhaps()->exists() || $nhaCungCap->loHangs()->exists()) {
            $nhaCungCap->update(['trang_thai' => 0]);

            return back()->with('success', 'Nhà cung cấp đã có giao dịch nhập hàng nên được chuyển sang trạng thái Ngừng giao dịch!');
        }

        $nhaCungCap->delete();

        return back()->with('success', 'Đã xóa nhà cung cấp thành công!');
    }
}
