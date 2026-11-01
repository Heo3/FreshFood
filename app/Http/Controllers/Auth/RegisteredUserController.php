<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GioHang;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:15|unique:users,phone',
            'email' => 'required|string|lowercase|email|max:100|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'khach_hang',
            'status' => 1,
        ]);

        $sessionId = $request->session()->getId();

        event(new Registered($user));

        Auth::login($user);

        if ($sessionId) {
            $guestItems = GioHang::where('session_id', $sessionId)->whereNull('user_id')->get();
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

        $request->session()->forget('url.intended');

        return to_route('home');
    }
}
