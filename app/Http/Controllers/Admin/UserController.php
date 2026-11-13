<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $storeId = $request->input('store_id');
        $status = $request->input('status');

        $query = User::with('cuaHang');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $users = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $stores = CuaHang::where('trang_thai', 1)->get(['id', 'ten_cua_hang']);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'stores' => $stores,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'store_id' => $storeId,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', 'in:admin,quan_ly,nhan_vien,khach_hang'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
            'password' => ['required', Password::defaults()],
            'status' => ['integer', 'in:0,1'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => ! empty($validated['phone']) ? $validated['phone'] : null,
            'role' => $validated['role'],
            'store_id' => ! empty($validated['store_id']) ? (int) $validated['store_id'] : null,
            'password' => Hash::make($validated['password']),
            'status' => isset($validated['status']) ? (int) $validated['status'] : 1,
        ]);

        return back()->with('success', "Đã tạo tài khoản {$validated['name']} thành công!");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,'.$user->id],
            'role' => ['required', 'in:admin,quan_ly,nhan_vien,khach_hang'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
            'status' => ['integer', 'in:0,1'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => ! empty($validated['phone']) ? $validated['phone'] : null,
            'role' => $validated['role'],
            'store_id' => ! empty($validated['store_id']) ? (int) $validated['store_id'] : null,
            'status' => isset($validated['status']) ? (int) $validated['status'] : 1,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return back()->with('success', "Đã cập nhật thông tin tài khoản {$user->name} thành công!");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình!');
        }

        // Thay vì xóa cứng làm mất toàn vẹn lịch sử đơn hàng, kho và chứng từ, chuyển trạng thái về Đã khóa / Ẩn
        $user->update(['status' => 0]);

        return back()->with('success', "Để tránh lỗi khi xem lịch sử giao dịch và chứng từ, tài khoản {$user->name} đã được khóa / ẩn thay vì xóa.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình!');
        }

        $newStatus = $user->status === 1 ? 0 : 1;
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 1
            ? "Đã mở khóa tài khoản {$user->name} hoạt động trở lại thành công!"
            : "Đã khóa / ẩn tài khoản {$user->name}. Người dùng này sẽ không thể đăng nhập, nhưng toàn bộ lịch sử đơn hàng và chứng từ vẫn được bảo toàn.";

        return back()->with('success', $message);
    }
}
