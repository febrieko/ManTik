@extends('layouts.app')

@section('title', 'Pengguna Telegram')
@section('page-title', 'Pengguna Telegram & Saldo')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2 style="display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;width:36px;height:36px;background:linear-gradient(135deg,#0088cc,#00b0f0);border-radius:10px;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="white" width="20" height="20">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
            </span>
            Daftar Pengguna Bot
        </h2>
        <p>Kelola pengunjung yang menggunakan bot Telegram dan isi saldo mereka.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24">✅ {{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Chat ID</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Saldo Saat Ini</th>
                    <th>Terdaftar Sejak</th>
                    <th>Aksi (Top Up)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td><code>{{ $user->chat_id }}</code></td>
                    <td style="font-weight:600;">{{ $user->full_name ?: '-' }}</td>
                    <td>
                        @if($user->username)
                            <a href="https://t.me/{{ $user->username }}" target="_blank" style="color:#00d4ff;">@{{ $user->username }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td style="font-weight:bold;color:#25d366;">
                        Rp {{ number_format($user->balance, 0, ',', '.') }}
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">
                        {{ $user->created_at->format('d M Y, H:i') }}
                    </td>
                    <td>
                        <form action="{{ route('telegram.users.topup', $user->id) }}" method="POST" style="display:flex;gap:8px;align-items:center;">
                            @csrf
                            <input type="number" name="amount" class="form-control form-control-sm" style="width:120px;" placeholder="Nominal" required min="1">
                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Top up saldo pengguna ini?')">
                                + Top Up
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">
                        Belum ada pengguna yang berinteraksi dengan bot Anda.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border-color);">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
