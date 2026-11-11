<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerPortal
{
    /**
     * Pastikan hanya user dengan role customer dan akun pelanggan aktif yang dapat mengakses portal pelanggan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('portal.login');
        }

        $user = Auth::user();

        if (!$user->isCustomer() || !$user->customer) {
            if ($user->hasAnyRole(['super-admin', 'admin', 'teknisi'])) {
                return redirect()->route('dashboard');
            }

            Auth::logout();
            return redirect()->route('portal.login')
                ->withErrors(['phone' => 'Akun Anda tidak memiliki akses ke Portal Pelanggan.']);
        }

        if (!$user->is_active || $user->customer->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login')
                ->withErrors(['phone' => 'Akun pelanggan Anda sedang non-aktif atau disuspend. Silakan hubungi admin.']);
        }

        return $next($request);
    }
}

