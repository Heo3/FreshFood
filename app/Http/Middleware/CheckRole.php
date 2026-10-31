<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->status !== 1) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.',
            ]);
        }

        // Admin luôn có quyền truy cập vào các phân hệ quản lý
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Kiểm tra vai trò của người dùng có nằm trong danh sách cho phép
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Bạn không có quyền truy cập vào khu vực này.');
    }
}
