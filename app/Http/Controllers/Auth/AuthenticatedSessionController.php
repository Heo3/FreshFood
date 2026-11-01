<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\GioHang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $previousSessionId = $request->session()->getId();

        $request->authenticate();

        $request->session()->regenerate();

        $request->session()->forget('url.intended');

        $user = $request->user();
        if ($user && $previousSessionId) {
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

        return to_route('home');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
