<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GioHang;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Chuyển hướng người dùng sang trang xác thực Google OAuth.
     */
    public function redirect(): RedirectResponse
    {
        if (! empty(config('services.google.client_id')) && ! empty(config('services.google.client_secret'))) {
            return Socialite::driver('google')
                ->with(['prompt' => 'select_account'])
                ->redirect();
        }

        // Trong môi trường Local hoặc Testing khi chưa cấu hình Google API Key:
        // Hỗ trợ đăng nhập trực tiếp một chạm để kiểm thử giao diện và luồng tài khoản.
        if (app()->environment('local', 'testing')) {
            return $this->handleLocalDevLogin();
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Hệ thống chưa cấu hình GOOGLE_CLIENT_ID và GOOGLE_CLIENT_SECRET trong file .env.',
        ]);
    }

    /**
     * Tự động đăng nhập chế độ Local Test khi chưa có Google Client ID.
     */
    protected function handleLocalDevLogin(): RedirectResponse
    {
        $request = request();
        $previousSessionId = $request->session()->getId();

        $email = env('MAIL_USERNAME', 'tidi36999@gmail.com');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Duy Thiên (Google)',
                'email' => $email,
                'google_id' => '109876543219876543210',
                'avatar' => 'https://lh3.googleusercontent.com/a/default-user=s96-c',
                'password' => bcrypt(Str::random(24)),
                'role' => 'khach_hang',
                'status' => 1,
                'loyalty_points' => 10,
            ]);
        } else {
            $user->update([
                'google_id' => $user->google_id ?: '109876543219876543210',
                'avatar' => $user->avatar ?: 'https://lh3.googleusercontent.com/a/default-user=s96-c',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        // Đồng bộ giỏ hàng từ session vãng lai
        if ($previousSessionId) {
            $guestItems = GioHang::where('session_id', $previousSessionId)->whereNull('user_id')->get();
            foreach ($guestItems as $guestItem) {
                $existing = GioHang::where('user_id', $user->id)
                    ->where('cua_hang_id', $guestItem->cua_hang_id)
                    ->where('san_pham_id', $guestItem->san_pham_id)
                    ->first();
                if ($existing) {
                    $existing->so_luong += $guestItem->so_luong;
                    $existing->save();
                    $guestItem->delete();
                } else {
                    $guestItem->update(['user_id' => $user->id]);
                }
            }
        }

        if ($user->isGiaoHang()) {
            return redirect()->route('delivery.index');
        }

        return redirect()->route('home')->with(
            'success',
            "Đăng nhập Google thành công với tài khoản {$user->name} ({$user->email})!"
        );
    }

    /**
     * Nhận callback từ Google và đăng nhập/đăng ký tài khoản tự động.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Không thể kết nối với tài khoản Google. Vui lòng thử lại sau.',
            ]);
        }

        // 1. Tìm theo google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        // 2. Nếu chưa có google_id, tìm theo email
        if (! $user && $googleUser->getEmail()) {
            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                ]);
            }
        }

        // 3. Nếu chưa tồn tại tài khoản nào, tạo tài khoản khách hàng mới
        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Khách hàng Google',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => bcrypt(Str::random(24)),
                'role' => 'khach_hang',
                'status' => 1,
                'loyalty_points' => 0,
            ]);
        }

        if ((int) $user->status !== 1) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.',
            ]);
        }

        $request = request();
        $previousSessionId = $request->session()->getId();

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        // Đồng bộ giỏ hàng từ session vãng lai
        if ($previousSessionId) {
            $guestItems = GioHang::where('session_id', $previousSessionId)->whereNull('user_id')->get();
            foreach ($guestItems as $guestItem) {
                $existing = GioHang::where('user_id', $user->id)
                    ->where('cua_hang_id', $guestItem->cua_hang_id)
                    ->where('san_pham_id', $guestItem->san_pham_id)
                    ->first();
                if ($existing) {
                    $existing->so_luong += $guestItem->so_luong;
                    $existing->save();
                    $guestItem->delete();
                } else {
                    $guestItem->update(['user_id' => $user->id]);
                }
            }
        }

        if ($user->isGiaoHang()) {
            return redirect()->route('delivery.index');
        }

        // Nhảy trực tiếp về trang chủ website
        return redirect()->route('home')->with(
            'success',
            "Đăng nhập thành công! Chào mừng {$user->name} đến với FreshFood."
        );
    }
}
