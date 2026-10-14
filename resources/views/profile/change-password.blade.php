@extends('layouts.app')

@section('title', 'Ubah Password Saya')
@section('page-title', 'Keamanan & Password')

@section('breadcrumb')
<span>Akun Saya</span>
<span>/</span>
<span>Ubah Password</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Ubah Password Akun</h2>
        <p>Perbarui password akun Anda secara berkala untuk menjaga keamanan sistem MANTIK</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:14px 18px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

@if($errors->any())
<div class="alert alert-error mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:14px 18px;border-radius:10px;">
    <strong>Gagal memperbarui password:</strong>
    <ul style="margin:8px 0 0 20px;line-height:1.6;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid" style="grid-template-columns: 1.5fr 1fr; gap: 24px; align-items: start;">
    {{-- Form Ubah Password --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#ffb300;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Ganti Password
            </div>
        </div>

        <form action="{{ route('profile.password.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group mb-20">
                <label class="form-label" for="current_password">Password Saat Ini *</label>
                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Masukkan password yang Anda gunakan saat ini" required autofocus>
                @error('current_password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group mb-20">
                <label class="form-label" for="password">Password Baru *</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                @error('password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group mb-24">
                <label class="form-label" for="password_confirmation">Konfirmasi Password Baru *</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password baru Anda" required minlength="6">
            </div>

            <div class="d-flex gap-12" style="display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Simpan Password Baru
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline btn-lg">Kembali ke Dashboard</a>
            </div>
        </form>
    </div>

    {{-- Info Card Akun Anda --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
                    Informasi Akun Anda
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" style="width: 56px; height: 56px; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">{{ auth()->user()->name }}</h3>
                    <div style="color: var(--text-secondary); font-size: 13px; font-family: monospace;">{{ auth()->user()->email }}</div>
                    <div style="margin-top: 6px;">
                        <span class="badge" style="background: rgba(0, 212, 255, 0.15); color: #00d4ff;">{{ strtoupper(auth()->user()->getRoleNames()->first() ?? 'User') }}</span>
                    </div>
                </div>
            </div>

            <div style="font-size: 12px; color: var(--text-secondary); line-height: 1.6; padding-top: 16px; border-top: 1px solid var(--border);">
                <strong style="color: var(--text-primary); display: block; margin-bottom: 4px;">Catatan:</strong>
                Setelah mengganti password, sesi login Anda tetap aktif pada perangkat ini, namun perangkat lain harus menggunakan password baru saat login berikutnya.
            </div>
        </div>
    </div>
</div>
@endsection
