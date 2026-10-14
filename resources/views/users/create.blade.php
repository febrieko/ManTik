@extends('layouts.app')

@section('title', 'Tambah Pengguna Baru')
@section('page-title', 'Tambah Pengguna')

@section('breadcrumb')
<a href="{{ route('users.index') }}">Pengguna</a>
<span>/</span>
<span>Tambah Pengguna</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Tambah Pengguna Baru</h2>
        <p>Tambahkan pengguna baru dengan hak akses Super Admin, Admin, atau Teknisi</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Daftar
    </a>
</div>

@if($errors->any())
<div class="alert alert-error mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:14px 18px;border-radius:10px;">
    <strong>Terdapat kesalahan pengisian formulir:</strong>
    <ul style="margin:8px 0 0 20px;line-height:1.6;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid" style="grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: start;">
    {{-- Form Tambah Pengguna --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
                Formulir Akun Pengguna
            </div>
        </div>

        <form action="{{ route('users.store') }}" method="POST" id="userForm">
            @csrf

            {{-- 1. Data Utama --}}
            <div class="form-group mb-20">
                <label class="form-label" for="name">Nama Lengkap *</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}"
                       placeholder="e.g. John Doe / Teknisi Lapangan" required autofocus>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group mb-20">
                <label class="form-label" for="email">Alamat Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}"
                       placeholder="nama@domain.com" required>
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            {{-- 2. Hak Akses (Role) --}}
            <div class="form-group mb-24">
                <label class="form-label" style="margin-bottom: 12px;">Pilihan Hak Akses (Role) *</label>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($roles as $roleKey => $roleData)
                    <label class="role-card-option" style="display: flex; align-items: flex-start; gap: 14px; padding: 14px 16px; border: 1px solid var(--border); border-radius: var(--border-radius-sm); cursor: pointer; transition: var(--transition); background: var(--bg-card);">
                        <input type="radio" name="role" value="{{ $roleKey }}" {{ old('role', 'teknisi') === $roleKey ? 'checked' : '' }} style="margin-top: 3px; accent-color: var(--primary);">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <strong style="color: var(--text-primary); font-size: 14px;">{{ $roleData['name'] }}</strong>
                                <span class="badge {{ $roleData['badge'] }}">{{ strtoupper($roleKey) }}</span>
                            </div>
                            <p style="color: var(--text-secondary); font-size: 12px; margin: 0; line-height: 1.4;">{{ $roleData['description'] }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('role') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            {{-- 3. Password --}}
            <div class="grid grid-2 mb-20" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="password_confirmation">Konfirmasi Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                </div>
            </div>

            {{-- 4. Kontak Notifikasi --}}
            <div class="card mb-20" style="background: rgba(255,255,255,0.02); border: 1px dashed var(--border); padding: 16px;">
                <div style="font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:#00e676;"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                    Kontak Penerima Notifikasi (Opsional)
                </div>
                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="phone">WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="Contoh: 628123456789">
                        <small class="text-muted" style="font-size: 11px; margin-top: 4px; display: block;">Format 62... tanpa tanda + atau spasi</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="telegram_chat_id">Telegram Chat ID</label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control" value="{{ old('telegram_chat_id') }}" placeholder="Contoh: 123456789">
                        <small class="text-muted" style="font-size: 11px; margin-top: 4px; display: block;">Dapatkan dari @userinfobot di Telegram</small>
                    </div>
                </div>
            </div>

            {{-- 5. Status Aktif --}}
            <div class="form-group mb-24">
                <label style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} style="accent-color: var(--primary); width: 16px; height: 16px;">
                    <span style="font-weight: 500; color: var(--text-primary);">Aktifkan Akun Pengguna Ini</span>
                </label>
                <div class="text-muted" style="font-size: 12px; margin-left: 26px;">Pengguna yang dinonaktifkan tidak dapat login ke sistem.</div>
            </div>

            <div class="d-flex gap-12" style="display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Pengguna
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-outline btn-lg">Batal</a>
            </div>
        </form>
    </div>

    {{-- Penjelasan Hak Akses --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Panduan Hak Akses (Role)
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 16px; font-size: 13px;">
                <div style="padding: 12px; background: rgba(255, 68, 68, 0.05); border-left: 3px solid #ff4444; border-radius: 4px;">
                    <strong style="color: #ff4444; display: block; margin-bottom: 4px;">👑 Super Admin</strong>
                    <span style="color: var(--text-secondary); line-height: 1.5;">
                        Memiliki wewenang mutlak atas seluruh fitur sistem, manajemen akun pengguna, pengaturan global sistem, kredensial bot WhatsApp/Telegram, dan hak hapus.
                    </span>
                </div>

                <div style="padding: 12px; background: rgba(0, 212, 255, 0.05); border-left: 3px solid #00d4ff; border-radius: 4px;">
                    <strong style="color: #00d4ff; display: block; margin-bottom: 4px;">🛡️ Administrator</strong>
                    <span style="color: var(--text-secondary); line-height: 1.5;">
                        Mengelola data operasional harian: Router, PPPoE Users, Hotspot Vouchers, Paket Layanan, Invoice/Billing, dan Laporan Keuangan. Tidak dapat mengubah konfigurasi sensitif sistem.
                    </span>
                </div>

                <div style="padding: 12px; background: rgba(255, 179, 0, 0.05); border-left: 3px solid #ffb300; border-radius: 4px;">
                    <strong style="color: #ffb300; display: block; margin-bottom: 4px;">🔧 Teknisi</strong>
                    <span style="color: var(--text-secondary); line-height: 1.5;">
                        Fokus pada operasional teknis lapangan dan monitoring. Dapat melihat live traffic, status router yang di-assign, troubleshoot PPPoE/Hotspot, serta menerima notifikasi alert gangguan.
                    </span>
                </div>
            </div>
        </div>

        <div class="card" style="background: rgba(0, 212, 255, 0.03); border: 1px solid rgba(0, 212, 255, 0.15);">
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(0,212,255,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); line-height: 1.5;">
                    <strong style="color: var(--text-primary); display: block; margin-bottom: 2px;">Tips Keamanan</strong>
                    Gunakan password yang kuat dengan kombinasi huruf, angka, dan simbol. Anda dapat mengubah kembali password pengguna kapan saja melalui menu daftar pengguna.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
