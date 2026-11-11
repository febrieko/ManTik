@extends('layouts.app')

@section('title', 'Edit Pelanggan - ' . $customer->name)
@section('page-title', 'Edit Pelanggan')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div class="page-header-title">
        <h2>Edit Data Pelanggan: {{ $customer->name }}</h2>
        <p>Perbarui informasi profil, nomor WhatsApp, dan akses akun portal pelanggan</p>
    </div>
    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline">
        &larr; Kembali ke Detail
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;">
    <strong>Periksa kembali input Anda:</strong>
    <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('customers.update', $customer) }}" method="POST">
    @csrf
    @method('PUT')
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
        {{-- Kolom Kiri: Data Utama --}}
        <div class="card" style="padding:24px;">
            <h3 style="margin-bottom:16px;font-size:16px;">Informasi Pelanggan</h3>

            <div class="form-group mb-16">
                <label class="form-label">Nama Lengkap <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control" required>
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Nomor WhatsApp (Unik per Pelanggan)</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control" placeholder="081234567890 atau 6281234567890">
                <small style="color:var(--text-muted);font-size:11px;">Otomatis dinormalisasi ke format 62...</small>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="mb-16">
                <div class="form-group">
                    <label class="form-label">Tipe Pelanggan</label>
                    <select name="customer_type" class="form-control">
                        <option value="regular" {{ old('customer_type', $customer->customer_type) === 'regular' ? 'selected' : '' }}>Regular (PPPoE / Umum)</option>
                        <option value="reseller" {{ old('customer_type', $customer->customer_type) === 'reseller' ? 'selected' : '' }}>Reseller / Agen Voucher</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Akun</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $customer->status) === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                        <option value="suspended" {{ old('status', $customer->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="mb-16">
                <div class="form-group">
                    <label class="form-label">Email (Opsional)</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">NIK / No. KTP (Opsional)</label>
                    <input type="text" name="nik" value="{{ old('nik', $customer->nik) }}" class="form-control">
                </div>
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Telegram Chat ID (Opsional)</label>
                <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $customer->telegram_chat_id) }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Lengkap (Opsional)</label>
                <textarea name="address" rows="3" class="form-control">{{ old('address', $customer->address) }}</textarea>
            </div>
        </div>

        {{-- Kolom Kanan: Kelola Akun Portal --}}
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card" style="padding:24px;">
                <h3 style="margin-bottom:12px;font-size:16px;">Akun Login Portal Pelanggan</h3>
                @if($customer->user)
                    <div style="padding:12px;background:rgba(0,230,118,0.08);border:1px solid rgba(0,230,118,0.25);border-radius:8px;margin-bottom:16px;font-size:12px;">
                        <strong style="color:#00e676;">✓ Akun Portal Sudah Terhubung</strong><br>
                        <span style="color:var(--text-muted);">Login WA: {{ $customer->user->phone ?: '-' }} | Status: {{ $customer->user->is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reset Password Portal (Opsional)</label>
                        <input type="password" name="portal_password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password" autocomplete="new-password">
                    </div>
                @else
                    <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px;">
                        Pelanggan ini belum memiliki akun login portal web. Centang di bawah untuk membuatkannya.
                    </p>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px;">
                        <input type="checkbox" name="create_portal_account" id="create_portal_account" value="1" {{ old('create_portal_account') ? 'checked' : '' }} onchange="togglePortalPassword()">
                        <span style="font-weight:600;font-size:13px;">Buatkan Akun Login Portal</span>
                    </label>
                    <div id="portal_password_box" style="{{ old('create_portal_account') ? '' : 'display:none;' }}">
                        <div class="form-group">
                            <label class="form-label">Password Portal <span style="color:#ef4444;">*</span></label>
                            <input type="password" name="portal_password" class="form-control" placeholder="Minimal 6 karakter" autocomplete="new-password">
                        </div>
                    </div>
                @endif
            </div>

            <div class="card" style="padding:24px;">
                <h3 style="margin-bottom:8px;font-size:16px;">Informasi Saldo</h3>
                <div style="font-size:24px;font-weight:700;color:#00e676;margin-bottom:8px;">
                    Rp {{ number_format((float)$customer->balance, 0, ',', '.') }}
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin:0;">
                    Top-up dan penyesuaian saldo dapat dilakukan dari halaman <a href="{{ route('customers.show', $customer) }}" style="color:#00d4ff;">Detail Pelanggan</a> agar tercatat rapi di buku mutasi.
                </p>
            </div>

            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</form>

<script>
function togglePortalPassword() {
    const el = document.getElementById('create_portal_account');
    if (el) {
        document.getElementById('portal_password_box').style.display = el.checked ? 'block' : 'none';
    }
}
</script>
@endsection

