<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PortalAuthController extends Controller
{
    /**
     * Tampilkan halaman login khusus pelanggan.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isCustomer() && $user->customer) {
                return redirect()->route('portal.dashboard');
            }
            if ($user->hasAnyRole(['super-admin', 'admin', 'teknisi'])) {
                return redirect()->route('dashboard');
            }
        }

        return view('portal.auth.login');
    }

    /**
     * Proses login pelanggan menggunakan Nomor WhatsApp dan Password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => ['required', 'string', 'max:25'],
            'password' => ['required', 'string'],
        ], [
            'phone.required'    => 'Nomor WhatsApp harus diisi.',
            'password.required' => 'Password harus diisi.',
        ]);

        $normalizedPhone = Customer::normalizePhoneNumber($request->input('phone'));
        if (!$normalizedPhone) {
            throw ValidationException::withMessages([
                'phone' => 'Format nomor WhatsApp tidak valid.',
            ]);
        }

        $throttleKey = 'portal_login:' . $normalizedPhone . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'phone' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp atau password salah.',
            ]);
        }

        if (!$user->isCustomer() || !$user->customer) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'phone' => 'Nomor ini tidak terdaftar sebagai akun Portal Pelanggan.',
            ]);
        }

        if (!$user->is_active || $user->customer->status !== 'active') {
            throw ValidationException::withMessages([
                'phone' => 'Akun pelanggan Anda sedang non-aktif atau disuspend. Hubungi admin.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    /**
     * Logout pelanggan dari Portal.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}

