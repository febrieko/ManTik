@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('page-title', 'Edit Pengguna: ' . $user->name)

@section('breadcrumb')
<a href="{{ route('users.index') }}">Pengguna</a>
<span>/</span>
<span>Edit Pengguna</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Edit Pengguna</h2>
        <p>Perbarui informasi akun, hak akses, atau status pengguna</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Daftar
    </a>
</div>

@if($errors->any())
<div class="alert alert-error mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:14px 18px;border-radius:10px;">
    <strong>Terdapat kesalahan pengisian:</strong>
    <ul style="margin:8px 0 0 20px;line-height:1.6;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid" style="grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: start;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Informasi Pengguna
            </div>
        </div>

        <form action="{{ route('users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group mb-20">
                <label class="form-label" for="name">Nama Lengkap *</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group mb-20">
                <label class="form-label" for="email">Alamat Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group mb-24">
                <label class="form-label" style="margin-bottom: 12px;">Pilihan Hak Akses (Role) *</label>
                @php $userCurrentRole = $user->roles->first()?->name ?? 'teknisi'; @endphp
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($roles as $roleKey => $roleData)
                    <label style="display: flex; align-items: flex-start; gap: 14px; padding: 14px 16px; border: 1px solid var(--border); border-radius: var(--border-radius-sm); cursor: pointer; background: var(--bg-card);">
                        <input type="radio" name="role" value="{{ $roleKey }}" {{ old('role', $userCurrentRole) === $roleKey ? 'checked' : '' }} style="margin-top: 3px; accent-color: var(--primary);">
                        <div style="flex: 1;">
                            <strong style="color: var(--text-primary); font-size: 14px;">{{ $roleData['name'] }}</strong>
                            <p style="color: var(--text-secondary); font-size: 12px; margin: 4px 0 0; line-height: 1.4;">{{ $roleData['description'] }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('role') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="card mb-20" style="background: rgba(255,255,255,0.02); border: 1px dashed var(--border); padding: 16px;">
                <div style="font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">Kontak Penerima Notifikasi</div>
                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="phone">WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="628123456789">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="telegram_chat_id">Telegram Chat ID</label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control" value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}" placeholder="123456789">
                    </div>
                </div>
            </div>

            <div class="form-group mb-24">
                <label style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} style="accent-color: var(--primary); width: 16px; height: 16px;">
                    <span style="font-weight: 500; color: var(--text-primary);">Status Akun Aktif</span>
                </label>
            </div>

            <div class="d-flex gap-12" style="display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
                <button type="submit" class="btn btn-primary btn-lg">Simpan Perubahan</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline btn-lg">Batal</a>
            </div>
        </form>
    </div>

    {{-- Kartu Ubah Password Langsung --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#ffb300;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Ubah Password Pengguna Ini
            </div>
        </div>
        <form action="{{ route('users.update-password', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group mb-16">
                <label class="form-label" for="new_password">Password Baru *</label>
                <input type="password" name="password" id="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group mb-20">
                <label class="form-label" for="new_password_confirmation">Ulangi Password Baru *</label>
                <input type="password" name="password_confirmation" id="new_password_confirmation" class="form-control" placeholder="Konfirmasi password baru" required>
            </div>
            <button type="submit" class="btn btn-warning" style="width: 100%; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                Perbarui Password Pengguna
            </button>
        </form>
    </div>
</div>
@endsection
