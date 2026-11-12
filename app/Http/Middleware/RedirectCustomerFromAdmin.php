<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectCustomerFromAdmin
{
    /**
     * Jika pengguna hanya memiliki role customer (bukan staff), arahkan ke Portal Pelanggan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->is_active === false) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
            }

            if ($user->isCustomer() && !$user->hasAnyRole(['super-admin', 'admin', 'teknisi'])) {
                return redirect()->route('portal.dashboard');
            }
        }

        return $next($request);
    }
}

